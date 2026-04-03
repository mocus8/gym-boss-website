<?php

namespace App\Auth;

// Класс-репозиторий для взаимодействия с бд
class PasswordResetTokenRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
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
}