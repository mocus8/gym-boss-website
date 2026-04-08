<?php
declare(strict_types=1);

// Контроллер для управления корзиной (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

namespace App\Api;

use App\Cart\CartSession;
use App\Auth\AuthSession;
use App\Cart\CartService;
use App\Support\Logger;

// Класс для управления корзинами пользователей (через методы сервиса)
class CartController extends BaseController {
    private CartSession $cartSession;
    private AuthSession $authSession;
    private CartService $cartService;

    // Конструктор (магический метод), присваиваем внеший экземпляр CartService и CartSession в переменные создоваемого объекта
    public function __construct(CartSession $cartSession, AuthSession $authSession, CartService $cartService, Logger $logger) {
        $this->cartSession = $cartSession;
        $this->authSession = $authSession;
        $this->cartService = $cartService;
        parent::__construct($logger);
    }

    // Метод для получения данных о корзине, возвращает массив - список товаров, общее кол-во, стоимость
    // Использует сразу три метода CartService
    // Обработчик запроса GET /api/cart
    public function getCart(): void {
        try {
            $cartSessionId = $this->cartSession->getId();
            $userId = $this->authSession->getUserId();

            $cartId = $this->cartService->getCart($cartSessionId, $userId);
            if ($cartId === null) {
                // Если корзина не найдена возвращаем пустую и выходим
                $this->success(200, [
                    'items' => [],
                    'count' => 0,
                    'total' => 0.00
                ]);
                return;
            }

            // Собираем состояние корзины
            $data = $this->buildCartData($cartId);

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get cart',
                context: [
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для добавления товара в корзину, обработчик запроса POST /api/cart/add-item 
    public function addItem(): void {
        try {
            $cartId = null;

            $cartSessionId = $this->cartSession->getId();
            $userId = $this->authSession->getUserId();

            $cartId = $this->cartService->getOrCreateCart($cartSessionId, $userId);

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
            $qty = isset($data['qty']) ? (int) $data['qty'] : 0;

            if ($productId <= 0 || $qty <= 0) {
                $this->error(
                    422, 
                    'VALIDATION_ERROR', 
                    'Invalid product_id or qty'
                );

                return;
            }

            // Через метод класса CartService добавляем в бд добавляем/прибавляем опр-ое кол-во товара в корзину
            $this->cartService->addItem($cartId, $productId, $qty);

            $data = $this->buildCartData($cartId);

            $this->success(201, $data);    // ресурс создан

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
                message: 'Failed to add item to {cart_id}',
                context: [
                    'cart_id' => $cartId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для удаления товара из корзины, обработчик запроса POST /api/cart/remove-item 
    public function removeItem(): void {
        try {
            $cartId = null;

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

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;

            if ($productId <= 0) {
                $this->error(
                    422, 
                    'VALIDATION_ERROR', 
                    'Invalid product_id'
                );

                return;
            }

            // Через метод класса CartService удаляем товар из корзины в бд
            $this->cartService->removeItem($cartId, $productId);

            $data = $this->buildCartData($cartId);

            $this->success(200, $data);

        } catch (\InvalidArgumentException $e) {
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\Throwable $e) {
            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to remove item from {cart_id}',
                context: [
                    'cart_id' => $cartId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для обновления в бд опр-ого кол-ва товара в корзине, обработчик запроса POST /api/cart/update-item-qty
    public function updateItemQty(): void {
        try {
            $cartId = null;

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

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
            $qty = isset($data['qty']) ? (int) $data['qty'] : 0;

            if ($productId <= 0 || $qty < 0) {
                $this->error(
                    422, 
                    'VALIDATION_ERROR', 
                    'Invalid product_id or qty'
                );

                return;
            }

            // Через метод класса CartService обновляем в бд опр-ое кол-во товара в корзине
            $this->cartService->updateItemQty($cartId, $productId, $qty);

            $data = $this->buildCartData($cartId);
            
            $this->success(200, $data);

        } catch (\InvalidArgumentException $e) {
            $this->error(
                422, 
                'VALIDATION_ERROR', 
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

        } catch (\Throwable $e) {
            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to update item quantity in {cart_id}',
                context: [
                    'cart_id' => $cartId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для очистки корзины, обработчик запроса POST /api/cart/clear
    public function clear(): void {
        try {
            $cartId = null;

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

            // Через метод класса CartService очищаем в бд корзину
            $this->cartService->clear($cartId);

            $data = $this->buildCartData($cartId);

            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to clear {cart_id}',
                context: [
                    'cart_id' => $cartId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Приватный метод для сборки состояния корзины 
    private function buildCartData(int $cartId): array {
        $items = $this->cartService->getItems($cartId);
        $count = $this->cartService->getItemsCount($cartId);
        $total = $this->cartService->getItemsTotal($cartId);

        return [
            'items' => $items,
            'count' => $count,
            'total' => $total
        ];
    }
}