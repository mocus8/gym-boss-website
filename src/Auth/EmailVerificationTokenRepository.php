<?php

namespace App\Auth;

// Класс-репозиторий для взаимодействия с бд
class EmailVerificationTokenRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для записи токена
    public function create(int $userId, string $hashedToken): void {
        // Если есть старый токен для пользователя - он перезаписывается
        $sql = "
            INSERT INTO email_verification_tokens (
                user_id,
                token,
                created_at
            )
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                token = ?,
                created_at = CURRENT_TIMESTAMP
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iss', $userId, $hashedToken, $hashedToken);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаление токена
    public function delete(string $hashedToken): void {
        $sql = "
            DELETE FROM email_verification_tokens
            WHERE token = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('s', $hashedToken);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для поиска токена по id пользователя
    public function findByUserId(int $userId): ?array {
        $sql = "
            SELECT created_at
            FROM email_verification_tokens
            WHERE user_id = ?
            LIMIT 1;        
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

        return $row;
    }

    // Получение данных о токене по самому токену
    public function findVerificationTokenInfo(string $hashedToken): ?array {
        $sql = "
            SELECT
                user_id,
                created_at
            FROM email_verification_tokens
            WHERE token = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $hashedToken);

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

        return $row;
    }
}