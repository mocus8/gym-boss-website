<?php
declare(strict_types=1);

namespace App\Auth;

// Класс-репозиторий для взаимодействия с бд
class LoginAttemptRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для добавления попытки входа
    public function addAttempt(string $email): void {
        $sql = "
            INSERT INTO login_attempts (
                email,
                attempted_at
            )
            VALUES (?, CURRENT_TIMESTAMP)
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

    // Метод для удаления старых попыток входа за время 
    public function deleteOldAttempts(int $ttl): void {
        $sql = "
            DELETE FROM login_attempts
            WHERE attempted_at < NOW() - INTERVAL ? SECOND
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('i', $ttl);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаления попыток входа для пользователя по email
    public function deleteAttemptsByEmail(string $email): void {
        $sql = "
            DELETE FROM login_attempts
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

    // Метод для нахождения кол-ва попыток входа по email за время
    public function findAttemptsInfoByEmail(string $email, int $window): array {
        $sql = "
            SELECT COUNT(*) AS attempts, MIN(attempted_at) AS first_attempt
            FROM login_attempts
            WHERE email = ?
                AND attempted_at > NOW() - INTERVAL ? SECOND             
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("si", $email, $window);

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

        if ($row === null) {
            $stmt->close();
            throw new \RuntimeException('DB fetch_row failed');
        }

        $stmt->close();

        return $row;
    }
}