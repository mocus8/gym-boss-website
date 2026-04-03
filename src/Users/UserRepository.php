<?php

namespace App\Users;

use App\Support\AppException;

// Класс-репозиторий для взаимодействия с бд
class UserRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для создания пользователя
    public function create(string $email, string $hashedPassword, string $name): int {
        $sql = "
            INSERT INTO users (
                email,
                password,
                name
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('sss', $email, $hashedPassword, $name);

        if (!$stmt->execute()) {
            $errno = $stmt->errno ?: $this->db->errno;    // код ошибки
            $error = $stmt->error ?: $this->db->error;    // текст ошибки
            $stmt->close();

            // Защита от гонок по созданию пользователя с одинаковым email (при duplicate entry)
            if ($errno === 1062) {
                throw new AppException('EMAIL_TAKEN', 'User with this email already exists');
            }

            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        // Получаем userId как AUTO_INCREMENT последней успешно вставленной строки для этого соединения
        $userId = $this->db->insert_id;

        $stmt->close();

        return $userId;
    }

    // Метод для пометки почты пользователя как подтержденной
    public function markEmailAsVerified(int $userId) {
        $sql = "
            UPDATE users
            SET email_verified_at = CURRENT_TIMESTAMP
            WHERE id = ?
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

        $stmt->close();
    }

    // Метод для изменения данных аккаунта пользователя по user id
    public function updateProfile(int $userId, string $name): void {
        $sql = "
            UPDATE users
            SET name = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('si', $name, $userId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для обновления пароля в бд на новый по user id
    public function setPasswordById(string $hashedNewPassword, int $userId): void {
        $sql = "
            UPDATE users
            SET password = ?
            WHERE id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('si', $hashedNewPassword, $userId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для обновления пароля в бд на новый по email
    public function setPasswordByEmail(string $hashedNewPassword, string $email): void {
        $sql = "
            UPDATE users
            SET password = ?
            WHERE email = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ss", $hashedNewPassword, $email);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        // Проверяем, что пароль действительно изменен
        if ($stmt->affected_rows === 0) {
            $stmt->close();
            throw new \RuntimeException('User not found for token email');
        }

        $stmt->close();
    }

    // Метод для удаление пользователя по id 
    public function delete(int $userId): void {
        $sql = "
            DELETE FROM users
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('i', $userId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            throw new \RuntimeException('User not found');
        }

        $stmt->close();
    }

    // Метод для получения инфорамиции о пользователе по его id
    public function findProfileById(int $userId): ?array {
        $sql = "
            SELECT
                id,
                email,
                email_verified_at,
                name
            FROM users
            WHERE id = ?
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

    // Метод для получения email, email_verified_at и имени пользователя по его id
    public function findUserVerificationInfoById(int $userId): ?array {
        $sql = "
            SELECT
                email,
                email_verified_at,
                name
            FROM users
            WHERE id = ?
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

    // Метод для поиска основной инфы о пользователе по email
    public function findAuthInfoByEmail(string $email): ?array {
        $sql = "
            SELECT
                id,
                email_verified_at,
                password,
                name
            FROM users
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

        return $row;
    }

    // Метод для нахождения email и пароля пользователя по id
    public function findCredentialsById(int $userId): ?array {
        $sql = "
            SELECT
                email,
                password
            FROM users
            WHERE id = ?
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

    // Метод для поиска пользователя в бд
    public function findIdByEmail(string $email): ?int {
        $sql = "
            SELECT id
            FROM users
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

        return $row['id'] ?? null;
    }

    // Метод для поиска имени пользователя по его email
    public function findNameByEmail(string $email): ?string {
        $sql = "
            SELECT
                name
            FROM users
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

        return $row['name'] ?? null;
    }

    // Метод для получения email пользователя по id
    public function findEmailById(int $userId): ?string {
        $sql = "
            SELECT email
            FROM users
            WHERE id = ?
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

        return $row['email'] ?? null;
    }

    // Метод для получения времени подтверждения email по user id
    public function findEmailVerifiedAtById(int $userId): ?string {
        $sql = "
            SELECT email_verified_at
            FROM users
            WHERE id = ?
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

        return $row['email_verified_at'] ?? null;
    }
}