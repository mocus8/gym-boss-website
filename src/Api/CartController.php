<?php
// Контроллер для управления корзиной (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут только добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Cart\CartService;    // используем класс CartService из пространства имен App\Cart
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для управления корзинами пользователей (через методы сервиса)
class CartController {
    private CartService $cart;    // приватное свойство (переменная класса), привязанная к объекту
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший созданный экземпляр CartService в переменную создоваемого объекта
    public function __construct(CartService $cart) {
        $this->cart = $cart;
    }

    // Будующий онструктор (магический метод), просто присваиваем внешюю $db в переменную создоваемого объекта
    // public function __construct(CartService $cart, Logger $logger) {
    //     $this->cart = $cart;
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

    // Метод для получения данных о корзине, возвращает массив - список товаров, общее кол-во, стоимость
    // Использует сразу три метода CartService
    // Обработчик запроса GET /api/cart
    public function getCart(): void {
        try {
            $cartSessionId = $_COOKIE['cart_session_id'] ?? '';
            $userId = getCurrentUserId();

            $cartId = $this->cart->getOrCreateCartId($cartSessionId, $userId);

            // Используем методы класса CartService 
            $items = $this->cart->getItems($cartId);
            $count = $this->cart->getItemsCount($cartId);
            $total = $this->cart->getItemsTotal($cartId);

            // Возвращаем успех через приватную функцию
            $this->success(200, [
                'items' => $items,
                'count' => $count,
                'total' => $total,
            ]);

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
            $cartSessionId = $_COOKIE['cart_session_id'] ?? '';
            $userId = getCurrentUserId();

            $cartId = $this->cart->getOrCreateCartId($cartSessionId, $userId);

            // Как $productId = $_POST['product_id'] ?? ''; только со строгой валидацией, другой синтаксис
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0 || $qty === false || $qty <= 0) {
                // Unprocessable entity, невалидные данные
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id or qty');
                return;
            }

            // Через метод класса CartService добавляем в бд добавляем/прибавляем опр-ое кол-во товара в корзину
            $this->cart->addItem($cartId, $productId, $qty);

            $this->success(201);    // ресурс создан

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
            $cartSessionId = $_COOKIE['cart_session_id'] ?? '';
            $userId = getCurrentUserId();

            $cartId = $this->cart->getOrCreateCartId($cartSessionId, $userId);

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id');
                return;
            }

            // Через метод класса CartService удаляем товар из корзины в бд
            $this->cart->removeItem($cartId, $productId);

            $this->success();

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
            $cartSessionId = $_COOKIE['cart_session_id'] ?? '';
            $userId = getCurrentUserId();

            $cartId = $this->cart->getOrCreateCartId($cartSessionId, $userId);

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

            if ($productId === false || $productId <= 0 || $qty === false || $qty < 0) {
                $this->error(422, 'VALIDATION_ERROR', 'Invalid product_id or qty');
                return;
            }

            // Через метод класса CartService обновляем в бд опр-ое кол-во товара в корзине
            $this->cart->updateItemQty($cartId, $productId, $qty);

            $this->success();

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
            $cartSessionId = $_COOKIE['cart_session_id'] ?? '';
            $userId = getCurrentUserId();

            $cartId = $this->cart->getOrCreateCartId($cartSessionId, $userId);

            // Через метод класса CartService очищаем в бд корзину
            $this->cart->clearCart($cartId);

            $this->success();

        } catch (\Throwable $e) {
            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Cart clear failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }
}