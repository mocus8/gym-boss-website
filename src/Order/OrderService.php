<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Order;
use App\Product\ProductService;    // используем класс ProductService из пространства имен App\Product
use App\Cart\CartService;    // Используем класс CartService из пространства имен App\Cart

// Класс для управления корзинами пользователей
class OrderService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private ProductService $productService;    // экземпляр сервиса для товаров (dependency injection)
    private CartService $cartService;    // экземпляр сервиса для корзины (dependency injection)

    // Константы для типов доставки
    private const DELIVERY_TYPE_COURIER = 1;
    private const DELIVERY_TYPE_PICKUP = 2;

    // Актуальные id для статусов заказа (ассоциативный массив code => id), живет в течении одного запроса
    private array $statusIdCache = [];

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(\mysqli $db, ProductService $productService, CartService $cartService) {
        $this->db = $db;
        $this->productService = $productService;
        $this->cartService = $cartService;
    }

    // Вспомогательный приватный метод для получения id статуса заказа по code (с кэшем)
    private function getStatusIdByCode(string $code): int {
        if (isset($this->statusIdCache[$code])) {
            return $this->statusIdCache[$code];
        }

        $sql = "SELECT id FROM order_statuses WHERE code = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('s', $code);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $result = $stmt->get_result();

        if (!$result) {
            $stmt->close();
            throw new \RuntimeException('DB get_result failed: ' . $this->db->error);
        }

        $row = $result->fetch_assoc();

        $stmt->close();

        if (!$row) {
            throw new \RuntimeException('Order status id not found: ' . $code);
        }

        $id = (int)$row['id'];

        $this->statusIdCache[$code] = $id;

        return $id;
    }

    // Вспомогательный приватный метод для получения цены доставки
    private function getDeliveryPrice(int $deliveryTypeId, float $totalPrice): float {
        $deliveryCost = 0.00;

        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER && $totalPrice < 5000) {
            $deliveryCost = 750.00;
        }

        return $deliveryCost;
    }

    // Метод создания order на основе cart (возвращает id созданного заказа)
    public function createFromCart(
        int $cartId,
        int $userId,
        int $deliveryTypeId,
        ?string $deliveryAddressText = null,
        ?string $deliveryPostalCode = null,
        ?int $storeId = null
    ): int {
        // Базовые проверки id
        if ($cartId <= 0) {
            throw new \InvalidArgumentException('Invalid cartId');
        }
    
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }
    
        if ($deliveryTypeId <= 0) {
            throw new \InvalidArgumentException('Invalid deliveryTypeId');
        }

        // Проверка, что deliveryTypeId это строго одно из двух валидных значений
        if (!in_array($deliveryTypeId, [self::DELIVERY_TYPE_COURIER, self::DELIVERY_TYPE_PICKUP], true)) {
            throw new \InvalidArgumentException('Unknown deliveryTypeId');
        }

        // Проверка на полноту данных при выбранном типе доставки
        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            if ($deliveryAddressText === null || trim($deliveryAddressText) === '') {
                throw new \InvalidArgumentException(
                    'Empty deliveryAddressText while courier delivery'
                );
            }
            if ($deliveryPostalCode === null || trim($deliveryPostalCode) === '') {
                throw new \InvalidArgumentException(
                    'Empty deliveryPostalCode while courier delivery'
                );
            }
        } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
            if ($storeId === null || $storeId <= 0) {
                throw new \InvalidArgumentException('Empty or invalid storeId while pickup');
            }
        }

        // Получаем недостающие поля для создания заказа
        $totalQty = $this->cartService->getItemsCount($cartId);
        $totalPrice = $this->cartService->getItemsTotal($cartId);
        $deliveryCost = $this->getDeliveryPrice($deliveryTypeId, $totalPrice);
        $statusId = $this->getStatusIdByCode('pending_payment');

        // Перед созданием заказа проверяем что корзина не пустая
        $items = $this->cartService->getItems($cartId);
        if ($items === []) {
            throw new \InvalidArgumentException('Empty cart while creating order');
        }
    
        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            $sql = "
                INSERT INTO orders (
                    user_id,
                    total_qty,
                    total_price,
                    delivery_type_id,
                    delivery_cost,
                    delivery_address_text,
                    delivery_postal_code,
                    store_id,
                    status_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param(
                'iididssii',
                $userId,
                $totalQty,
                $totalPrice,
                $deliveryTypeId,
                $deliveryCost,
                $deliveryAddressText,
                $deliveryPostalCode,
                $storeId,
                $statusId
            );

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            // Получаем order_id как AUTO_INCREMENT последней успешно вставленной строки для этого соединения
            $orderId = $this->db->insert_id;

            $stmt->close();

            // Строим плейсхолдеры ?,?,?, ... и массив params для заполнения order_items
            $placeholders = [];
            $params = [];

            foreach ($items as $item) {
                $placeholders[] = '(?, ?, ?, ?)';

                $params[] = $orderId;
                $params[] = $item["product_id"];
                $params[] = $item["amount"];
                $params[] = $item["price"];
            }

            if ($placeholders === []) {
                throw new \InvalidArgumentException('Empty placeholders while filling order_items');
            }

            // Собираем строку типов для вставки params
            $types = str_repeat('iiid', count($placeholders));

            // Конвертируем массив placeholders в строку, разделяем запятыми
            $placeholders = implode(',', $placeholders);

            $sql = "
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    amount,
                    price
                )
                VALUES" . $placeholders
            ;

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }
        
            $stmt->bind_param($types, ...$params);
    
            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }
        
            $stmt->close();

            // Помечаем корзину как конвертированную
            $this->cartService->convert($cartId);

            // Комитим транзакцию
            $this->db->commit();

            return $orderId;

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Реализовать методы
    // markPaid
    // getById
    // getByPaymentId
    // getUserOrders
    // cancel


}