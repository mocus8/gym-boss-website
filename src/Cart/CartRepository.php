<?php
declare(strict_types=1);

namespace App\Cart;

// Класс-репозиторий для взаимодействия с бд
class CartRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для создаения корзины по user id
    public function createByUserId(int $userId): int {
        $sql = "
            INSERT INTO carts (user_id)
            VALUES (?)
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

        // Получаем id как последний вставленный в бд и возвращаем его
        $cartId = $this->db->insert_id;

        $stmt->close();

        return $cartId;
    }

    // Метод для создаения корзины по session id
    public function createBySessionId(string $sessionId): int {
        $sql = "
            INSERT INTO carts (session_id)
            VALUES (?)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $sessionId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        // Получаем id как последний вставленный в бд и возвращаем его
        $cartId = $this->db->insert_id;

        $stmt->close();

        return $cartId;
    }

    // Метод для добавления к корзине user id и удаления у нее session id
    public function attachToUser(int $userId, int $cartId): void {
        $sql = "
            UPDATE carts
            SET user_id = ?, session_id = NULL
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ii", $userId, $cartId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для пометки корзины как конвертированной
    public function convert(int $cartId): void {
        $sql = "
            UPDATE carts
            SET is_converted = true
            WHERE id = ?
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

    // Метод для обновления поля updated_at
    public function touch(int $cartId): void {
        $sql = "
            UPDATE carts
            SET updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

        // Выполняем
        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для поиска корзины по user id 
    public function findActiveCartIdByUserId(int $userId): ?int {
        $sql = "
            SELECT id
            FROM carts
            WHERE user_id = ? AND is_converted = false
            LIMIT 1
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

        $row = $result->fetch_assoc();

        $stmt->close();

        $cartId = $row ? (int)$row['id'] : null;

        return $cartId;
    }

    // Метод для поиска корзины по session id
    public function findActiveCartIdBySessionId(string $sessionId): ?int {
        $sql = "
            SELECT id
            FROM carts
            WHERE session_id = ? AND is_converted = false
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $sessionId);

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

        $cartId = $row ? (int)$row['id'] : null;

        return $cartId;
    }
}