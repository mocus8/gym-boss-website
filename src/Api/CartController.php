<?php
// Контроллер для управления корзиной (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут только добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Cart\CartService;    // используем класс CartService из пространства имен App\Cart
use App\Cart\CartSession;    // используем класс CartSession из пространства имен App\Cart
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для управления корзинами пользователей (через методы сервиса)
class CartController {
    private CartSession $cartSession;    // приватное свойство (переменная класса), привязанная к объекту
    private CartService $cartService;    // приватное свойство (переменная класса), привязанная к объекту
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший экземпляр CartService и CartSession в переменные создоваемого объекта
    public function __construct(CartSession $cartSession, CartService $cartService) {
        $this->cartSession = $cartSession;
        $this->cartService = $cartService;
    }

    // Будующий онструктор (магический метод), просто присваиваем внешюю $db в переменную создоваемого объекта
    // public function __construct(CartService $cartService, Logger $logger) {
    //     $this->cartService = $cartService;
    //     $this->logger = $logger;
    // }

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

    // Приватный метод для сборки состояния корзины 
    private function buildCartData(int $cartId): array {
        $items = $this->cartService->getItems($cartId);
        $count = $this->cartService->getItemsCount($cartId);
        $total = $this->cartService->getItemsTotal($cartId);

        return [
            'items' => $items,
            'count' => $count,
            'total' => $total,
        ];
    }

    // Метод для получения данных о корзине, возвращает массив - список товаров, общее кол-во, стоимость
    // Использует сразу три метода CartService
    // Обработчик запроса GET /api/cart
    public function getCart(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            // Собираем состояние корзины
            $data = $this->buildCartData($cartId);

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

    // Метод для добавления товара в корзину, обработчик запроса POST /api/cart/add-item 
    public function addItem(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            // Как $productId = $_POST['product_id'] ?? ''; только со строгой валидацией, другой синтаксис
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0 || $qty === false || $qty <= 0) {
                // Unprocessable entity, невалидные данные
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id or qty');
                return;
            }

            // Через метод класса CartService добавляем в бд добавляем/прибавляем опр-ое кол-во товара в корзину
            $this->cartService->addItem($cartId, $productId, $qty);

            $data = $this->buildCartData($cartId);

            $this->success(201, $data);    // ресурс создан

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart addItem failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }

    // Метод для удаления товара из корзины, обработчик запроса POST /api/cart/remove-item 
    public function removeItem(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id');
                return;
            }

            // Через метод класса CartService удаляем товар из корзины в бд
            $this->cartService->removeItem($cartId, $productId);

            $data = $this->buildCartData($cartId);

            $this->success(200, $data);

        } catch (\InvalidArgumentException $e) {
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart removeItem failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }

    // Метод для обновления в бд опр-ого кол-ва товара в корзине, обработчик запроса POST /api/cart/update-item-qty
    public function updateItemQty(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0 || $qty === false || $qty < 0) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id or qty');
                return;
            }

            // Через метод класса CartService обновляем в бд опр-ое кол-во товара в корзине
            $this->cartService->updateItemQty($cartId, $productId, $qty);

            $data = $this->buildCartData($cartId);
            
            $this->success(200, $data);

        } catch (\InvalidArgumentException $e) {
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (\Throwable $e) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart updateItemQty failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }

    // Метод для очистки корзины, обработчик запроса POST /api/cart/clear
    public function clear(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = getCurrentUserId();

            $cartId = $this->cartService->getOrCreateCartId($cartSessionId, $userId);

            // Через метод класса CartService очищаем в бд корзину
            $this->cartService->clearCart($cartId);

            $data = $this->buildCartData($cartId);

            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart clear failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }
}