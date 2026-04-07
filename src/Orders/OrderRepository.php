<?php
declare(strict_types=1);

namespace App\Orders;

// Класс-репозиторий для взаимодействия с бд
class OrderRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для создания заказа из корзины
    public function createFromCart(
        int $userId,
        int $totalQty,
        float $totalPrice,
        int $deliveryTypeId,
        float $deliveryCost,
        ?string $deliveryAddressText,
        ?string $deliveryPostalCode,
        ?int $storeId,
        int $statusId
    ): int {
        $sql = "
            INSERT INTO orders (
                user_id,
                total_quantity,
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

        return $orderId;
    }

    // Метод для пометки заказа как оплаченного
    public function markAsPaid(
        int $paidStatusId,
        ?string $courierFrom,
        ?string $courierTo,
        ?string $readyForPickupFrom,
        ?string $readyForPickupTo,
        string $paidAtFormatted,
        int $orderId
    ): void {
        $sql = "
            UPDATE orders
            SET 
                status_id = ?,
                courier_delivery_from = ?,
                courier_delivery_to = ?,
                ready_for_pickup_from = ?,
                ready_for_pickup_to = ?,
                paid_at = ?
            WHERE id = ?
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

    // Метод для пометки заказа как отмененного
    public function markAsCanceled(int $canceledStatusId, int $orderId, ?int $userId): void {
        $where = $userId !== null ? 'WHERE id = ? AND user_id = ?' : 'WHERE id = ?';

        $sql = "
            UPDATE orders
            SET 
                status_id = ?,
                canceled_at = NOW()
            "
            . $where
        ;

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        if ($userId !== null) {
            $stmt->bind_param("iii", $canceledStatusId, $orderId, $userId);
        } else {
            $stmt->bind_param("ii", $canceledStatusId, $orderId);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для смены статуса заказа
    public function setStatus(int $statusId, int $orderId): void {
        $sql = "
            UPDATE orders
            SET status_id = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ii", $statusId, $orderId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для получения базовой инфы о заказе и блокировки строки заказа на момент создания оплаты
    public function lockForPayment(int $orderId, int $userId): array {
        $sql = "
            SELECT
                id,
                user_id,
                total_price,
                delivery_type_id,
                delivery_cost,
                status_id
            FROM orders
            WHERE id = ? AND user_id = ?
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

    // Метод для получения id статуса по id заказа с блокировкой строки
    public function findForStatusUpdate(int $orderId, ?int $userId): int {
        $where = $userId !== null ? 'WHERE id = ? AND user_id = ?' : 'WHERE id = ?';

        $sql = "
            SELECT status_id
            FROM orders
            " . $where . "
            FOR UPDATE
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        if ($userId !== null) {
            $stmt->bind_param("ii", $orderId, $userId);
        } else {
            $stmt->bind_param('i', $orderId);
        }

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

        return (int)$row["status_id"];
    }

    // Метод для получения инфы для обновления оплаты заказа с блокировкой строки
    public function findForPaymentUpdate(int $orderId): array {
        $sql = "
            SELECT 
                status_id,
                delivery_type_id,
                paid_at
            FROM orders
            WHERE id = ?
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

        return $row;
    }

    // Метод для получения id статуса по коду статуса
    // Обращается к таблице-справочнику без отдельного репозитория
    public function findOrderStatusIdByCode(string $code): int {
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

        return (int)$row['id'];
    }

    // Метод для поиска всей инфы по заказу по order id и по user id если он не null
    public function findInfo(int $orderId, ?int $userId): array {
        $where = $userId !== null ? 'WHERE o.id = ? AND o.user_id = ?' : 'WHERE o.id = ?';

        $sql = "
            SELECT
                o.id,
                o.user_id,
                u.name AS user_name,
                u.email AS user_email,
                o.total_quantity,
                o.total_price,
                o.delivery_type_id,
                dt.code AS delivery_type_code,
                dt.name AS delivery_type_name,
                o.delivery_cost,
                o.delivery_address_text,
                o.delivery_postal_code,
                o.store_id,
                s.address AS store_address,
                s.work_hours AS store_work_hours,
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
                o.canceled_at
            FROM orders AS o
            LEFT JOIN users AS u ON o.user_id = u.id
            LEFT JOIN delivery_types AS dt ON o.delivery_type_id = dt.id
            LEFT JOIN stores AS s ON o.store_id = s.id
            LEFT JOIN order_statuses AS os ON o.status_id = os.id
            " . $where . "
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        if ($userId !== null) {
            $stmt->bind_param('ii', $orderId, $userId);
        } else {
            $stmt->bind_param('i', $orderId);
        }

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

        if (!$row) {
            $stmt->close();
            throw new \InvalidArgumentException('Order not found');
        }

        $stmt->close();

        return $row;
    }

    // Метод для поиска инфы о всех заказах пользователя
    public function findUserOrders(int $userId): array {
        // Получаем всю инфу о заказах (таблицы order, delivery_types, order_statuses)
        // Вместе с id статуса и типа доставки возвращаем поля code и name из других таблиц
        $sql = "
            SELECT
                o.id,
                o.user_id,
                o.total_quantity,
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
                o.canceled_at
            FROM orders AS o
            LEFT JOIN delivery_types AS dt ON o.delivery_type_id = dt.id
            LEFT JOIN stores AS s ON o.store_id = s.id
            LEFT JOIN order_statuses AS os ON o.status_id = os.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
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
}