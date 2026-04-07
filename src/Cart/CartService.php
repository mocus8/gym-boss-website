<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Cart;

use App\Cart\CartRepository;
use App\Cart\CartItemRepository;
use App\Products\ProductService;

// Класс для управления корзинами пользователей
class CartService {
    private CartRepository $cartRepository;
    private CartItemRepository $cartItemRepository;
    private ProductService $productService;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository,
        ProductService $productService
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
        $this->productService = $productService;
    }

    // Поиск корзины по cart_session_id или user_id
    public function getCart(?string $cartSessionId, ?int $userId): ?int {
        // Выбираем по чему искать исходя из аргументов
        if ($userId !== null) {
            return $this->cartRepository->findActiveCartIdByUserId($userId);

        } elseif ($cartSessionId !== null && $cartSessionId !== '') {
            return $this->cartRepository->findActiveCartIdBySessionId($cartSessionId);
            
        } else {
            return null;
        }
    }

    // Поиск корзины по cart_session_id или user_id, нашли - возвращаем ее $cartId, нет - создаем и возвращаем $cartId новой
    public function getOrCreateCart(?string $cartSessionId, ?int $userId): int {
        // Проверяем аргументы 
        if ($userId === null && ($cartSessionId === null || $cartSessionId === '')) {
            throw new \InvalidArgumentException('Empty cartSessionId and userId');
        }

        $cartId = $this->getCart($cartSessionId, $userId);
        if ($cartId !== null) {
            return $cartId;
        }

        // Не нашли - заносим по user id если есть 
        if ($userId !== null) {
            return $this->cartRepository->createByUserId($userId);
        }

        // Если нет user id то создаем по cart session id
        return $this->cartRepository->createBySessionId($cartSessionId);
    }

    // Метод для привязки гостевой корзины к пользователю (по cart_session_id и user_id)
    public function attachGuestCartToUser(string $cartSessionId, int $userId): void {
        // Проверяем аргументы 
        if ($userId <= 0 || $cartSessionId === '') {
            throw new \InvalidArgumentException('Invalid userId or empty cartSessionId');
        }

        // Смотрим, есть ли уже у этого пользователя корзина
        $cartId = $this->cartRepository->findActiveCartIdByUserId($userId);

        // Если есть - просто выходим, корзину не трогаем
        if ($cartId) return;

        // Если нет - находим гостевую по session_id
        $cartId = $this->cartRepository->findActiveCartIdBySessionId($cartSessionId);
       
        // Если строчка не нашлась - выходим
        if (!$cartId) return;

        // У гостевой карзины записываем user id и удаляем session id
        $this->cartRepository->attachToUser($userId, $cartId);
    }

    // Метод получения кол-ва всех товаров в корзине
    public function getItemsCount(int $cartId): int {
        return $this->cartItemRepository->findCount($cartId);
    }

    // Метод для подсчета общей стоимости корзины
    public function getItemsTotal(int $cartId): float {
        $items = $this->cartItemRepository->findItemsByCartId($cartId);
        if ($items === []) {
            return 0.0;
        }

        // Вытаскиваем product_id из items и находим цены товаров
        $productIds = array_keys($items);
        $prices = $this->productService->getPricesByIds($productIds);

        // Объявляем переменную с общей суммой и заполняем
        $total = 0;
        foreach ($items as $productId => $quantity) {
            // Если цены для товара нет то пропускаем итерацию
            if (!isset($prices[$productId])) {
                continue;
            }

            $total += (float)$prices[$productId] * $quantity;
        }

        return (float)$total;
    }

    // Метод для получения всех товаров в корзине (с первой фотографией)
    public function getItems(int $cartId): array {
        $cartItems = $this->cartItemRepository->findItemsByCartId($cartId);
        if ($cartItems === []) {
            return [];
        }
        
        // Получаем массив товаров через productService
        $productIds = array_keys($cartItems);
        $products = $this->productService->getByIds($productIds);

        // Собираем итоговый массив позиций корзины: товар + quantity
        $items = [];
        foreach ($cartItems as $productId => $quantity) {
            if (!isset($products[$productId])) continue; // товар мог быть удалён из каталога

            $items[] = array_merge($products[$productId], ['quantity' => $quantity]);
        }

        return $items;
    }

    // Метод для добавления определенного кол-ва (qty) товара в корзину / прибавление кол-ва к сущ-ему товару
    public function addItem(int $cartId, int $productId, int $qty): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    
        $this->cartItemRepository->addItem($cartId, $productId, $qty);

        // После успешного изменения товаров обновляем поле updated_at 
        $this->touchCart($cartId);
    }

    // Метод для удаления товара из корзины
    public function removeItem(int $cartId, int $productId): void {
        $this->cartItemRepository->removeItem($cartId, $productId);

        // После успешного изменения товаров обновляем поле updated_at 
        $this->touchCart($cartId);
    }

    // Метод для жёсткого установления нового количества товара в корзине
    public function updateItemQty(int $cartId, int $productId, int $qty): void {
        if ($qty < 0) {
            throw new \InvalidArgumentException('Quantity must be >= 0');
        }

        if ($qty === 0) {
            $this->cartItemRepository->removeItem($cartId, $productId);
            $this->touchCart($cartId);
            return;
        }

        $this->cartItemRepository->updateItemQty($cartId, $productId, $qty);

        // После успешного изменения товаров обновляем поле updated_at 
        $this->touchCart($cartId);
    }

    // Метод для очистки корзины
    public function clear(int $cartId): void {
        $this->cartItemRepository->clear($cartId);

        // После успешного изменения товаров обновляем поле updated_at 
        $this->touchCart($cartId);
    }

    // Метод для пометки корзины как конвертированной
    public function convert(int $cartId): void {
        $this->cartRepository->convert($cartId);
    }

    // Метод для обновления поля updated_at при действиях с cart_items
    private function touchCart(int $cartId): void {
        $this->cartRepository->touch($cartId);
    }
}