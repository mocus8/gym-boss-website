<?php

namespace App\Orders;

// Класс-репозиторий для взаимодействия с бд
class OrderItemRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для заполнения товаров в заказе
    public function fill(int $orderId, array $items): void {
        // Строим плейсхолдеры ?,?,?, ... и массив params для заполнения order_items
        $placeholders = [];
        $params = [];

        foreach ($items as $item) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?)';

            $params[] = $orderId;
            $params[] = $item["id"];
            $params[] = $item["name"];
            $params[] = $item["quantity"];
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
                quantity,
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
    }

    public function findItems(int $orderId): array {
        $sql = "
            SELECT 
                product_id,
                quantity,
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

        // Объявляем и заполняем массив с товарами
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $quantity = (int)$row['quantity'];
            $price = (float)$row['price'];

            $items[$productId] = [
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        $stmt->close();

        return $items;
    }

    // Метод для получения всей инфы о товарах для составления чека
    public function findItemsForReceipt(int $orderId): array {
        $sql = "
            SELECT
                order_id,
                product_id,
                product_name,
                quantity,
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
}