<?php
// Контроллер для управления заказами (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/order/cancel и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Order\OrderService;    // используем класс CartService из пространства имен App\Order
use App\Cart\CartSession;    // используем класс CartSession из пространства имен App\Cart
use App\Cart\CartService;    // используем класс CartService из пространства имен App\Cart
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для управления заказами пользователей (через методы сервиса)
class OrderController {
    // Приватные свойства (переменные класса), привязанные к объекту
    private OrderService $orderService;    
    private CartSession $cartSession;
    private CartService $cartService;
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший экземпляр OrderService в переменные создоваемого объекта
    public function __construct(OrderService $orderService, CartSession $cartSession, CartService $cartService) {
        $this->orderService = $orderService;
        $this->cartSession = $cartSession;
        $this->cartService = $cartService;
    }

    // Будующий конструктор (с логером)
    // public function __construct(OrderService $orderService, CartSession $cartSession, CartService $cartService, Logger $logger) {
    //     $this->orderService = $orderService;
    //     $this->cartSession = $cartSession;
    //     $this->cartService = $cartService;
    //     $this->logger = $logger;
    // }

    // Приватный метод для получения, декодирования и проверки json входных данных
    private function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            $this->error(400, 'INVALID_REQUEST', 'Invalid JSON body');
            return null;
        }
    
        return $data;
    }

    // Приватная функция для отправки успеха
    private function success(int $status = 200, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    // Приватная функция для отправки ошибки
    // Возможно логгер сюда переместить
    private function error(
        int $status = 500,
        string $code = 'INTERNAL_SERVER_ERROR',
        string $message = 'Internal server error'
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // Метод для создания заказа на основе корзины, корзину помечает как конвертированную, возвращает id заказа
    // Обработчик запроса POST /api/order/create-from-cart
    public function createFromCart(): void {
        try {
            // Подготавливаем переменные для использования в методе createFromCart
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();
            if ($userId === null) {
                $this->error(401, 'UNAUTHORIZED', 'User is not authorized');
                return;
            }

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
            $this->success(201, ['orderId' => $orderId,]);

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
    // Обработчик запроса GET /api/order/{id}
    public function getById(int $orderId): void {
        try {
            $userId = getCurrentUserId();
            if ($userId === null) {
                $this->error(401, 'UNAUTHORIZED', 'User is not authorized');
                return;
            }

            $data = $this->orderService->getById($orderId, $userId);

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

    // Реализовать методы:
    // getUserOrders
    // markCancel
}