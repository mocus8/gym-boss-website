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

    // Константы для стоимости доставки и порога бесплатной доставки
    private float $deliveryCourierPrice;
    private float $deliveryFreeThreshold;

    // Константы для времени доставки/сборки заказа
    private int $pickupReadyFromHours;
    private int $pickupReadyToHours;
    private int $courierDeliveryFromHours;
    private int $courierDeliveryToHours;

    // Актуальные id для статусов заказа (ассоциативный массив code => id), живет в течении одного запроса
    private array $statusIdCache = [];

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        ProductService $productService,
        CartService $cartService,
        float $deliveryCourierPrice,
        float $deliveryFreeThreshold,
        int $pickupReadyFromHours,
        int $pickupReadyToHours,
        int $courierDeliveryFromHours,
        int $courierDeliveryToHours,
    ) {
        $this->db = $db;
        $this->productService = $productService;
        $this->cartService = $cartService;
        $this->deliveryCourierPrice = $deliveryCourierPrice;
        $this->deliveryFreeThreshold = $deliveryFreeThreshold;
        $this->pickupReadyFromHours = $pickupReadyFromHours;
        $this->pickupReadyToHours = $pickupReadyToHours;
        $this->courierDeliveryFromHours = $courierDeliveryFromHours;
        $this->courierDeliveryToHours = $courierDeliveryToHours;
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

        // Если курьерский тип доставки и стоимость товаров меньше порога - возвращаем стоимость доставки
        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER && $totalPrice < $this->deliveryFreeThreshold) {
            return $this->deliveryCourierPrice;
        }

        // Иначе стоимость ноль
        return 0.00;
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
                $placeholders[] = '(?, ?, ?, ?, ?, ?)';

                $params[] = $orderId;
                $params[] = $item["product_id"];
                $params[] = $item["name"];
                $params[] = $item["amount"];
                $params[] = $item["price"];
                $params[] = $item["vat_code"];
            }

            if ($placeholders === []) {
                throw new \InvalidArgumentException('Empty placeholders while filling order_items');
            }

            // Собираем строку типов для вставки params
            $types = str_repeat('iisidi', count($placeholders));

            // Конвертируем массив placeholders в строку, разделяем запятыми
            $placeholders = implode(',', $placeholders);

            $sql = "
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    amount,
                    price,
                    vat_code
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

        // Получаем всю инфу о заказе (таблицы order, delivery_types, order_statuses)
        // Вместе с id статуса и типа доставки возвращаем поля code и name из других таблиц
        $sql = "
            SELECT
                o.order_id,
                o.user_id,
                o.total_qty,
                o.total_price,
                o.delivery_type_id,
                dt.code AS delivery_type_code,
                dt.name AS delivery_type_name,
                o.delivery_cost,
                o.delivery_address_text,
                o.delivery_postal_code,
                o.store_id,
                s.address AS store_address,
                o.courier_delivery_from,
                o.courier_delivery_to,
                o.ready_for_pickup_from,
                o.ready_for_pickup_to,
                o.status_id,
                os.code AS status_code,
                os.name AS status_name,
                o.created_at,
                o.updated_at,
                o.paid_at,
                o.cancelled_at
            FROM orders AS o
            LEFT JOIN delivery_types AS dt ON o.delivery_type_id = dt.id
            LEFT JOIN stores AS s ON o.store_id = s.id
            LEFT JOIN order_statuses AS os ON o.status_id = os.id
            WHERE o.order_id = ? AND o.user_id = ?
            LIMIT 1
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

        // Объявляем примерные/фактические сроки доставки/самовывоза
        $deliveryFrom = null;
        $deliveryTo = null;

        $statusCode = $order["status_code"];
        $deliveryTypeId = (int)$order["delivery_type_id"];

        if ($statusCode === 'pending_payment') {
            // Заказ не оплачен, считаем от now
            $now = new \DateTimeImmutable('now');

            if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
                $deliveryFrom = $now->modify('+' . $this->courierDeliveryFromHours . ' hours')->format('Y-m-d H:i:s');
                $deliveryTo = $now->modify('+' . $this->courierDeliveryToHours . ' hours')->format('Y-m-d H:i:s');
            } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
                $deliveryFrom = $now->modify('+' . $this->pickupReadyFromHours . ' hours')->format('Y-m-d H:i:s');
                $deliveryTo = $now->modify('+' . $this->pickupReadyToHours . ' hours')->format('Y-m-d H:i:s');
            }

        } elseif ($statusCode === 'paid' || $statusCode === 'shipped' || $statusCode === 'ready_for_pickup') {
            // Заказ оплачен, используем значения из бд
            if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
                $deliveryFrom = $order["courier_delivery_from"];
                $deliveryTo = $order["courier_delivery_to"];
            } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
                $deliveryFrom = $order["ready_for_pickup_from"];
                $deliveryTo = $order["ready_for_pickup_to"];
            }
        }

        // Удаляем сырые значения из бд из массива order
        unset(
            $order['courier_delivery_from'],
            $order['courier_delivery_to'],
            $order['ready_for_pickup_from'],
            $order['ready_for_pickup_to']
        );

        // Добавляем новые поля в массив order
        $order['delivery_from'] = $deliveryFrom;
        $order['delivery_to'] = $deliveryTo;

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

        // Получаем всю инфу о заказах (таблицы order, delivery_types, order_statuses)
        // Вместе с id статуса и типа доставки возвращаем поля code и name из других таблиц
        $sql = "
            SELECT
                o.order_id,
                o.user_id,
                o.total_qty,
                o.total_price,
                o.delivery_type_id,
                dt.code AS delivery_type_code,
                dt.name AS delivery_type_name,
                o.delivery_cost,
                o.delivery_address_text,
                o.delivery_postal_code,
                o.store_id,
                s.address AS store_address,
                o.courier_delivery_from,
                o.courier_delivery_to,
                o.ready_for_pickup_from,
                o.ready_for_pickup_to,
                o.status_id,
                os.code AS status_code,
                os.name AS status_name,
                o.created_at,
                o.updated_at,
                o.paid_at,
                o.cancelled_at
            FROM orders AS o
            LEFT JOIN delivery_types AS dt ON o.delivery_type_id = dt.id
            LEFT JOIN stores AS s ON o.store_id = s.id
            LEFT JOIN order_statuses AS os ON o.status_id = os.id
            WHERE o.user_id = ?
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

    // Метод пометки заказа как оплаченного: только логика, должен вызываться только внутри открытой транзакции
    public function markPaidInTx(int $orderId): void {
        // Получаем статус, тип доставкии и время оплаты заказа с блокировкой строки (FOR UPDATE)
        $sql = "
            SELECT 
                status_id,
                delivery_type_id,
                paid_at
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
        $deliveryTypeId = (int)$row["delivery_type_id"];
        $paidAtRow = $row["paid_at"];

        $pendingStatusId = $this->getStatusIdByCode('pending_payment');
        $paidStatusId = $this->getStatusIdByCode('paid');

        // Уже оплачен (тихо выходим из метода)
        if ($orderStatusId === $paidStatusId) {
            return;
        }

        // Статус не pending_payment
        if ($orderStatusId !== $pendingStatusId) {
            throw new \RuntimeException('Order status is not pending_payment');
        }

        // Проверяем поле paid_at
        if ($paidAtRow !== null) {
            // Повторный вызов или особый кейс: используем уже сохранённое время
            $paidAt = new \DateTimeImmutable($paidAtRow);
        } else {
            // Первый раз помечаем как оплаченный: берем текущее время
            $paidAt = new \DateTimeImmutable('now');
        }
        $paidAtFormatted = $paidAt->format('Y-m-d H:i:s');

        // Считаем время доставки/готовности
        $courierFrom = null;
        $courierTo = null;
        $readyForPickupFrom = null;
        $readyForPickupTo = null;

        if ($deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            $courierFrom = $paidAt->modify('+' . $this->courierDeliveryFromHours . ' hours')->format('Y-m-d H:i:s');
            $courierTo = $paidAt->modify('+' . $this->courierDeliveryToHours . ' hours')->format('Y-m-d H:i:s');
        } elseif ($deliveryTypeId === self::DELIVERY_TYPE_PICKUP) {
            $readyForPickupFrom = $paidAt->modify('+' . $this->pickupReadyFromHours . ' hours')->format('Y-m-d H:i:s');
            $readyForPickupTo = $paidAt->modify('+' . $this->pickupReadyToHours . ' hours')->format('Y-m-d H:i:s');
        }

        // Обновляем статус заказа на paid
        $sql = "
            UPDATE orders
            SET 
                status_id = ?,
                courier_delivery_from = ?,
                courier_delivery_to = ?,
                ready_for_pickup_from = ?,
                ready_for_pickup_to = ?,
                paid_at = ?
            WHERE order_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param(
            "isssssi",
            $paidStatusId,
            $courierFrom,
            $courierTo,
            $readyForPickupFrom,
            $readyForPickupTo,
            $paidAtFormatted,
            $orderId
        );

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод пометки заказа как оплаченного (оболочка метода markPaidInTx с транзакцией)
    public function markPaid(int $orderId): void {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();
        
        try {
            $this->markPaidInTx($orderId);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }
    }

    // Метод для получения базовой инфы о заказе и блокировки строки заказа на момент создания оплаты
    public function lockForPayment(int $orderId, int $userId): array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Получаем базовую инфу о заказе с блокировкой строки (FOR UPDATE)
        $sql = "
            SELECT
                order_id,
                user_id,
                total_price,
                delivery_type_id,
                delivery_cost,
                status_id
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

        $order = $result->fetch_assoc();

        $stmt->close();

        if (!$order) {
            throw new \InvalidArgumentException('Order not found');
        }

        return $order;
    }

    // Метод для получения позиций заказа 
    public function getItemsForReceipt(int $orderId): array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем инфу о составе заказа
        $sql = "
            SELECT
                order_id,
                product_id,
                product_name,
                amount,
                price,
                vat_code
            FROM order_items
            WHERE order_id = ?
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

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();

        if (empty($items)) {
            throw new \InvalidArgumentException('Empty order items');
        }

        return $items;
    }

    // Метод для пометки заказа как отменненого (отмены от провайдера/юкассы или из вебхука)
    public function markCancelFromPaymentProvider(int $orderId): void {

        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
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
                    cancelled_at = NOW()
                WHERE order_id = ?
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("ii", $cancelledStatusId, $orderId);

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