<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Account;

use App\Support\AppException;

// Класс для управления аккаунтами пользователей
class AccountService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(\mysqli $db) {
        $this->db = $db;
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

    // Метод для смены пароля
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): void {
        // Находим в бд по userId старый пароль
        $sql = "
            SELECT
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

        // Если пользователь не нашелся - ошибку
        if (!$row) {
            throw new \RuntimeException('User not found');
        }

        $password = $row["password"];

        // Сравниваем пароли из бд и введеный через password_verify (сравнивает введеный с хешем из бд)
        if (!password_verify($currentPassword, $password)) {
            throw new AppException('WRONG_PASSWORD', 'Wrong current password');
        }

        // Хэшируем пароль и проверям что удалось
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hashedNewPassword === false) {
            throw new \RuntimeException('Password hashing failed');
        }

        // Обновляем пароль в бд на новый
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

    // Метод для удаления пользователя 
    public function deleteUser(int $userId): void {
        // Удаляем пользователя
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
}