<?php

namespace App\Cart;

// Класс-репозиторий для взаимодействия с бд
class CartItemRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для поиска кол-ва товаров в корзине
    public function findCount(int $cartId): int {
        $sql = "
            SELECT COALESCE(SUM(quantity), 0) AS count
            FROM cart_items
            WHERE cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

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

        return (int)($row['count'] ?? 0);
    }

    // Метод для поиска товаров в корзине
    public function findItemsByCartId(int $cartId): array {
        $sql = "
            SELECT
                product_id,
                quantity
            FROM cart_items
            WHERE cart_items.cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

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
            $productId = (int)$row['product_id'];
            $quantity = (int)$row['quantity'];

            $items[$productId] = $quantity;
        }
        
        $stmt->close();

        return $items;
    }

    // Добавление определонного кол-ва кол-ва товара в корзину
    public function addItem(int $cartId, int $productId, int $qty): void {
        // Запрос вставляет в таблицу строку, либо увел-ает кол-во товара (если уже в корзине)
        $sql = "
            INSERT INTO cart_items (cart_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iii', $cartId, $productId, $qty);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаления товара из корзины
    public function removeItem(int $cartId, int $productId): void {
        $sql = "
            DELETE FROM cart_items
            WHERE cart_id = ? AND product_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('ii', $cartId, $productId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для обновления кол-ва товара в корзине
    public function updateItemQty(int $cartId, int $productId, int $qty): void {
        $sql = "
            UPDATE cart_items
            SET quantity = ?
            WHERE cart_id = ? AND product_id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iii', $qty, $cartId, $productId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаления всех товаров из корзины 
    public function clear(int $cartId): void {
        $sql = "
            DELETE FROM cart_items
            WHERE cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('i', $cartId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }
}