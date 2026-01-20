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

    // Метод для получения инфы о заказе и всех товаров в нем (с первой фотографией) по его id
    public function getById(int $orderId, int $userId): array {
        // Базовые проверки id
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        $sql = "
            SELECT *
            FROM orders
            WHERE order_id = ? AND user_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ii", $orderId, $userId);

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

        // order - ассоциативный массив с инфой о заказе (строка orders)
        $order = $result->fetch_assoc();

        if (!$order) {
            $stmt->close();
            throw new \InvalidArgumentException('Order not found');
        }

        $stmt->close();

        $sql = "
            SELECT 
                product_id,
                amount,
                price
            FROM order_items
            WHERE order_items.order_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $orderId);

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

        // Объявляем и заполняем массивы id => amount, id => price и id товаров из заказа
        $amountByIds = [];
        $priceByIds = [];
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $amount = (int)$row['amount'];
            $price = (float)$row['price'];

            $amountByIds[$productId] = $amount;
            $priceByIds[$productId] = $price;
            $ids[] = $productId;
        }

        if ($ids === []) {
            $stmt->close();
            return [
                'order' => $order,
                'items' => []
            ];
        }

        $stmt->close();

        // Получаем массив товаров через productService
        $products = $this->productService->getByIds($ids);

        // Собираем итоговый массив позиций корзины: товар + amount
        $items = [];
        foreach ($products as $productId => $product) {
            // Прибавляем к элементу product массива products поле, и все это вместе кладем в items
            $items[] = array_merge($product, [
                'amount' => $amountByIds[$productId] ?? 0,
                'price' => $priceByIds[$productId] ?? $product['price']
            ]);
        }

        return [
            'order' => $order,
            'items' => $items
        ];
    }

    // Метод для получения инфы о всех заказах пользователя по userId
    public function getUserOrders(int $userId): array {
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        $sql = "
            SELECT *
            FROM orders
            WHERE user_id = ?
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $userId);

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

        // orders - ассоциативный массив с инфой о заказах (подходящие строки из таблицы orders)
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        $stmt->close();

        return $orders;
    }

    // Метод для пометки заказа как оплаченного
    public function markPaid(int $orderId, string $yookassaPaymentId): void {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $yookassaPaymentId = trim($yookassaPaymentId);

        if ($yookassaPaymentId === '') {
            throw new \InvalidArgumentException('Empty yookassaPaymentId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();
        
        try {
            // Получаем статус заказа с блокировкой строки (FOR UPDATE)
            $sql = "
                SELECT status_id
                FROM orders
                WHERE order_id = ?
                FOR UPDATE
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("i", $orderId);

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
                throw new \InvalidArgumentException('Order not found');
            }

            $orderStatusId = (int)$row["status_id"];

            $pendingStatusId = $this->getStatusIdByCode('pending_payment');
            $paidStatusId = $this->getStatusIdByCode('paid');

            // Уже оплачен
            if ($orderStatusId === $paidStatusId) {
                throw new \RuntimeException('Order already paid');
            }

            // Статус не pending_payment
            if ($orderStatusId !== $pendingStatusId) {
                throw new \RuntimeException('Order status is not pending_payment');
            }

            // Обновляем статус заказа на paid и записываем yookassaPaymentId
            $sql = "
                UPDATE orders
                SET 
                    status_id = ?,
                    paid_at = NOW(),
                    yookassa_payment_id = ?
                WHERE order_id = ?
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("isi", $paidStatusId, $yookassaPaymentId, $orderId);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            $stmt->close();

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для пометки заказа как отменненого
    public function markCancel(int $orderId, int $userId): void {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Получаем статус заказа с блокировкой строки (FOR UPDATE)
            $sql = "
                SELECT status_id
                FROM orders
                WHERE order_id = ? AND user_id = ?
                FOR UPDATE
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("ii", $orderId, $userId);

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
                throw new \InvalidArgumentException('Order not found');
            }

            $orderStatusId = (int)$row["status_id"];

            $cancelledStatusId = $this->getStatusIdByCode('cancelled');
            $pendingStatusId = $this->getStatusIdByCode('pending_payment');

            // Уже отменен
            if ($orderStatusId === $cancelledStatusId) {
                throw new \RuntimeException('Order already cancelled');
            }

            // Статус не pending_payment
            if ($orderStatusId !== $pendingStatusId) {
                throw new \RuntimeException('Order cannot be cancelled from current status');
            }

            // Обновляем статус заказа на cancelled
            $sql = "
                UPDATE orders
                SET 
                    status_id = ?,
                    cancelled_at = NOW()
                WHERE order_id = ? AND user_id = ?
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("iii", $cancelledStatusId, $orderId, $userId);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            $stmt->close();

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для пометки заказа как отменненого
    public function markCancelledFromWebhook(int $orderId, string $yookassaPaymentId): void {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $yookassaPaymentId = trim($yookassaPaymentId);

        if ($yookassaPaymentId === '') {
            throw new \InvalidArgumentException('Empty yookassaPaymentId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Получаем статус заказа с блокировкой строки (FOR UPDATE)
            $sql = "
                SELECT status_id
                FROM orders
                WHERE order_id = ?
                FOR UPDATE
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("i", $orderId);

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
                throw new \InvalidArgumentException('Order not found');
            }

            $orderStatusId = (int)$row["status_id"];

            $cancelledStatusId = $this->getStatusIdByCode('cancelled');
            $pendingStatusId = $this->getStatusIdByCode('pending_payment');

            // Уже отменен (тихо выходим из метода)
            if ($orderStatusId === $cancelledStatusId) {
                $this->db->commit();
                return;
            }

            // Статус не pending_payment
            if ($orderStatusId !== $pendingStatusId) {
                throw new \RuntimeException('Order cannot be cancelled from current status');
            }

            // Обновляем статус заказа на cancelled
            $sql = "
                UPDATE orders
                SET 
                    status_id = ?,
                    cancelled_at = NOW(),
                    yookassa_payment_id = ?
                WHERE order_id = ?
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("isi", $cancelledStatusId, $yookassaPaymentId, $orderId);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            $stmt->close();

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }
}