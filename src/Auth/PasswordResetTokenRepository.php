<?php

namespace App\Auth;

// Класс-репозиторий для взаимодействия с бд
class PasswordResetTokenRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для создаения токена
    public function create(string $email, string $hashedToken): void {
        // Если есть старый токен для почты - он перезаписывается
        $sql = "
            INSERT INTO password_reset_tokens (
                email,
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
    
        $stmt->bind_param('sss', $email, $hashedToken, $hashedToken);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаление токена по email
    public function deleteByEmail(string $email): void {
        $sql = "
            DELETE FROM password_reset_tokens
            WHERE email = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('s', $email);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для нахождения времени создания прошлого токена
    public function findTokenInfo(string $hashedToken): ?array {
        $sql = "
            SELECT
                email,
                created_at
            FROM password_reset_tokens
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

    // Метод для нахождения времени создания прошлого токена
    public function findPreviousTokenCreatedAt(string $email): ?string {
        $sql = "
            SELECT created_at
            FROM password_reset_tokens
            WHERE email = ?
            LIMIT 1;        
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $email);

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

        return $row['created_at'] ?? null;
    }
}