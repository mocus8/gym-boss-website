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
        PaymentStatusSyncService $paymentStatusSyncService
    ) {
        $this->authSession = $authSession;
        $this->orderService = $orderService;
        $this->CancelOrderUseCase = $CancelOrderUseCase;
        $this->cartSession = $cartSession;
        $this->cartService = $cartService;
        $this->paymentService = $paymentService;
        $this->paymentStatusSyncService = $paymentStatusSyncService;
    }

    // Будующий конструктор (с логером)
    // public function __construct(
    //     AuthSession $authSession,
    //     OrderService $orderService,
    //     CancelOrderUseCase $CancelOrderUseCase,
    //     CartSession $cartSession,
    //     CartService $cartService,
    //     PaymentService $paymentService,
    //     PaymentStatusSyncService $paymentStatusSyncService
    //     Logger $logger
    // ) {
    //     $this->authSession = $authSession;
    //     $this->orderService = $orderService;
    //     $this->CancelOrderUseCase = $CancelOrderUseCase;
    //     $this->cartSession = $cartSession;
    //     $this->cartService = $cartService;
    //     $this->paymentService = $paymentService;
    //     $this->paymentStatusSyncService = $paymentStatusSyncService;
    //     parent::__controller($logger);
    // }

    // Метод для создания заказа на основе корзины, корзину помечает как конвертированную, возвращает id заказа
    // Обработчик запроса POST /api/orders/create-from-cart
    public function createFromCart(): void {
        try {
            // Подготавливаем переменные для использования в методе createFromCart
            $cartSessionId = $this->cartSession->getId();
            $userId = $this->authSession->getUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            // Разбираем поля из массива полученных данных data
            $deliveryTypeId = isset($data['deliveryTypeId']) ? (int) $data['deliveryTypeId'] : 0;
            $deliveryAddressText = isset($data['deliveryAddressText']) ? (string) $data['deliveryAddressText'] : null;
            $deliveryPostalCode = isset($data['deliveryPostalCode']) ? (string) $data['deliveryPostalCode'] : null;
            $storeId = isset($data['storeId']) ? (int) $data['storeId'] : null;

            if ($deliveryTypeId <= 0) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid deliveryTypeId');
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

            // Возвращаем успех через приватную функцию
            $this->success(201, ['order_id' => $orderId,]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart getCart failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для получения заказа по его id, возвращает массив с инфой о заказе и товарах в нем
    // Обработчик запроса GET /api/orders/{id}
    public function getById(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $data = $this->orderService->getById($orderId, $userId);

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
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\RuntimeException $e) {
            // Заказ не найден
            $this->error(404, 'ORDER_NOT_FOUND', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart getCart failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для получения всех заказов пользователя, возвращает массив с инфой о заказах
    // Обработчик запроса GET /api/orders
    public function getUserOrders(): void {
        try {
            $userId = $this->authSession->getUserId();

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

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart getCart failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для пометки заказа как отмененного
    // Обработчик запроса POST /api/orders/{id}/cancel
    public function markCancel(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $this->CancelOrderUseCase->markCancelByUser($orderId, $userId);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\RuntimeException $e) {
            $this->error(400, 'ORDER_CANCEL_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart getCart failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для попытки оплаты заказа
    // Обработчик запроса POST /api/orders/{id}/start-payment
    public function startPayment(int $orderId): void {
        try {
            $userId = $this->authSession->getUserId();

            $confirmationUrl = $this->paymentService->getOrCreatePayment($orderId, $userId);

            // Возвращаем успех через приватную функцию
            $this->success(200, ['confirmation_url' => $confirmationUrl]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\RuntimeException $e) {
            $this->error(400, 'PAYMENT_CREATION_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Order startPayment failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для попытки синхронизации статуса платежа и заказа между бд и юкассой
    // Обработчик запроса POST /api/orders/{id}/sync-payment
    public function syncPayment(int $orderId): void {
        try {
            $this->paymentStatusSyncService->syncByOrderId($orderId);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\RuntimeException $e) {
            $this->error(400, 'PAYMENT_STATUS_SYNC_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Order syncPayment failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }
}