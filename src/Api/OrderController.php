<?php
// Контроллер для управления заказами (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/order/cancel и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Auth\AuthSession;    // используем класс AuthSession из пространства имен App\Auth
use App\Orders\OrderService;    // используем класс OrderService из пространства имен App\Orders
use App\Orders\CancelOrderUseCase;    // используем класс CancelOrderUseCase из пространства имен App\Orders
use App\Cart\CartSession;    // используем класс CartSession из пространства имен App\Cart
use App\Cart\CartService;    // используем класс CartService из пространства имен App\Cart
use App\Payments\PaymentService;
use App\Payments\PaymentStatusSyncService;
use App\Support\Logger;

// Класс для управления заказами пользователей (через методы сервиса)
class OrderController extends BaseController {
    // Приватные свойства (переменные класса), привязанные к объекту
    private AuthSession $authSession;
    private OrderService $orderService;
    private CancelOrderUseCase $CancelOrderUseCase;
    private CartSession $cartSession;
    private CartService $cartService;
    private PaymentService $paymentService;
    private PaymentStatusSyncService $paymentStatusSyncService;

    // Конструктор (магический метод), присваиваем внеший экземпляр OrderService в переменные создоваемого объекта
    public function __construct(
        AuthSession $authSession,
        OrderService $orderService,
        CancelOrderUseCase $CancelOrderUseCase,
        CartSession $cartSession,
        CartService $cartService,
        PaymentService $paymentService,
        PaymentStatusSyncService $paymentStatusSyncService,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->orderService = $orderService;
        $this->CancelOrderUseCase = $CancelOrderUseCase;
        $this->cartSession = $cartSession;
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->paymentStatusSyncService = $paymentStatusSyncService;
        parent::__construct($logger);
    }

    // Метод для создания заказа на основе корзины, корзину помечает как конвертированную, возвращает id заказа
    // Обработчик запроса POST /api/orders/create-from-cart
    public function createFromCart(): void {
        try {
            $cartId = null;

            // Подготавливаем переменные для использования в методе createFromCart
            $cartSessionId = $this->cartSession->getId();
            $userId = $this->authSession->getUserId();

            $cartId = $this->cartService->getCart($cartSessionId, $userId);
            if ($cartId === null) {
                $this->error(
                    404, 
                    'CART_NOT_FOUND', 
                    'Cart not found'
                );

                return;
            }

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            // Разбираем поля из массива полученных данных data
            $deliveryTypeId = isset($data['delivery_type_id']) ? (int) $data['delivery_type_id'] : 0;
            $deliveryAddressText = isset($data['delivery_address_text']) ? (string) $data['delivery_address_text'] : null;
            $deliveryPostalCode = isset($data['delivery_postal_code']) ? (string) $data['delivery_postal_code'] : null;
            $storeId = isset($data['store_id']) ? (int) $data['store_id'] : null;

            if ($deliveryTypeId <= 0) {
                $this->error(
                    422, 
                    'VALIDATION_ERROR', 
                    'Invalid deliveryTypeId while checkout for {cart_id}',
                    context: [
                        'cart_id' => $cartId,
                    ]
                );

                return;
            }

            $orderId = $this->orderService->createFromCart(
                $cartId,
                $userId,
                $deliveryTypeId,
                $deliveryAddressText,
                $deliveryPostalCode,
                $storeId
            );

            $this->logger->info('Order {order_id} created from cart {cart_id}', [
                'order_id' => $orderId,
                'cart_id'  => $cartId,
            ]);

            // Возвращаем успех через приватную функцию
            $this->success(201, ['order_id' => $orderId,]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to create order from cart {cart_id}',
                context: [
                    'cart_id' => $cartId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для получения заказа по его id, возвращает массив с инфой о заказе и товарах в нем
    // Обработчик запроса GET /api/orders/{id}
    public function getById(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $data = $this->orderService->getByIdForUser($orderId, $userId);

            // Форматируем временные поля из SQL формата в ISO формат
            $data['order'] = mapIsoFields($data['order'], [
                'created_at',
                'updated_at',
                'paid_at',
                'canceled_at',
                'delivery_from',
                'delivery_to',
            ]);

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\RuntimeException $e) {
            // Заказ не найден
            $this->error(
                404,
                'ORDER_NOT_FOUND',
                $e->getMessage(),
                context: [
                    'order_id' => $orderId,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get order by id {order_id}',
                context: [
                    'order_id' => $orderId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для получения всех заказов пользователя, возвращает массив с инфой о заказах
    // Обработчик запроса GET /api/orders
    public function getUserOrders(): void {
        try {
            $userId = null;

            $userId = $this->authSession->getUserId();

            // Если пользователь не залогинен
            if ($userId === null) {
                $this->error(
                    401,
                    'UNAUTHENTICATED',
                    'Authentication required'
                );

                return;
            }

            $data = $this->orderService->getUserOrders($userId);

            // Форматируем временные поля в каждом заказе
            $data = array_map(
                fn(array $order) => mapIsoFields($order, [
                    'courier_delivery_from',
                    'courier_delivery_to',
                    'ready_for_pickup_from',
                    'ready_for_pickup_to',
                    'created_at',
                    'updated_at',
                    'paid_at',
                    'canceled_at',
                ]),
                $data
            );

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get orders by user id {user_id}',
                context: [
                    'user_id' => $userId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для пометки заказа как отмененного
    // Обработчик запроса POST /api/orders/{id}/cancel
    public function markCancel(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $this->CancelOrderUseCase->markCancelByUser($orderId, $userId);

            $this->logger->info('Order {order_id} marked as canceled', [
                'order_id' => $orderId,
            ]);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\RuntimeException $e) {
            $this->error(
                400,
                'ORDER_CANCEL_ERROR',
                $e->getMessage(),
                context: [
                    'order_id' => $orderId,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to mark order {order_id} as canceled',
                context: [
                    'order_id' => $orderId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для попытки оплаты заказа
    // Обработчик запроса POST /api/orders/{id}/start-payment
    public function startPayment(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $confirmationUrl = $this->paymentService->getOrCreatePayment($orderId, $userId);

            $this->logger->info('Payment started for order {order_id}', [
                'order_id' => $orderId,
            ]);

            // Возвращаем успех через приватную функцию
            $this->success(200, ['confirmation_url' => $confirmationUrl]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\RuntimeException $e) {
            $this->error(
                400,
                'PAYMENT_CREATION_ERROR',
                $e->getMessage(),
                context: [
                    'order_id' => $orderId,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to start payment for order {order_id}',
                context: [
                    'order_id' => $orderId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для попытки синхронизации статуса платежа и заказа между бд и юкассой
    // Обработчик запроса POST /api/orders/{id}/sync-payment
    public function syncPayment(int $orderId): void {
        try {
            $this->paymentStatusSyncService->syncByOrderId($orderId);

            $this->logger->info('Payment status was synced for order {order_id}', [
                'order_id' => $orderId,
            ]);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\RuntimeException $e) {
            $this->error(
                400,
                'PAYMENT_STATUS_SYNC_ERROR',
                $e->getMessage(),
                context: [
                    'order_id' => $orderId,
                ]
            );

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to sync payment for order {order_id}',
                context: [
                    'order_id' => $orderId,
                    'exception' => $e,
                ]
            );
        }
    }
}