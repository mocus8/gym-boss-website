<?php
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Account;

use App\Support\AppException;
use App\Users\UserRepository;
use App\Auth\PasswordResetTokenRepository;

// Класс для управления аккаунтами пользователей
class AccountService {
    private \mysqli $db;
    private UserRepository $userRepository;
    private PasswordResetTokenRepository $passwordResetTokenRepository;

    public function __construct(
        \mysqli $db, 
        UserRepository $userRepository, 
        PasswordResetTokenRepository $passwordResetTokenRepository
    ) {
        $this->db = $db;
        $this->userRepository = $userRepository;
        $this->passwordResetTokenRepository = $passwordResetTokenRepository;
    }

    // Метод для изменения данных аккаунта пользователя по user id
    public function updateProfile(int $userId, string $name): void {
        $this->userRepository->updateProfile($userId, $name);
    }

    // Метод для смены пароля
    public function updatePassword(int $userId, string $currentPassword, string $newPassword): void {
        // Находим в бд по userId старый пароль и email
        $credentials = $this->userRepository->findCredentialsById($userId);

        // Если пользователь не нашелся - ошибку
        if (!$credentials) {
            throw new \RuntimeException('User not found');
        }

        $email = $credentials["email"];
        $password = $credentials["password"];

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
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
            $this->userRepository->setPasswordById($hashedNewPassword, $userId);

            // Если остался токен для сброса пароля - удаляем его 
            $this->passwordResetTokenRepository->deleteByEmail($email);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        } 
    }

    // Метод для удаления пользователя 
    public function deleteUser(int $userId): void {
        $this->userRepository->delete($userId);
    }
}