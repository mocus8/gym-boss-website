<?php
// Контроллер для взаимодействия с аккаунтами пользователй (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

namespace App\Api;

use App\Auth\AuthSession;
use App\Account\AccountService;
use App\Support\Flash;
use App\Support\Logger;

// Класс для управления классами пользователей (через методы сервиса)
class AccountController extends BaseController {
    private AuthSession $authSession;
    private AccountService $accountService;
    private Flash $flash;

    public function __construct(
        AuthSession $authSession,
        AccountService $accountService,
        Flash $flash,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->accountService = $accountService;
        $this->flash = $flash;
        parent::__construct($logger);
    }

    // Метод для изменения данных аккаунта (имени)
    public function updateProfile(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(
                    401,
                    'UNAUTHENTICATED',
                    'Authentication required',
                );

                return;
            }

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) return;

            // Валидируем имя
            $name = isset($data['name']) ? (string)($data['name']) : null;
            $name = $this->validateName($name);
            if ($name === null) return;

            // Меняем имя через метод сервиса
            $this->accountService->updateProfile($userId, $name);

            $this->logger->info('Name updated for {user_id}', [
                'user_id' => $userId,
            ]);

            $this->flash->set(
                'Данные профиля успешно изменены'
            );

            $this->success(200, ['name' => $name]);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to update profile for {user_id}',
                context: [
                    'user_id' => $userId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для смены пароля, зная текущий
    public function updatePassword(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(
                    401,
                    'UNAUTHENTICATED',
                    'Authentication required',
                );

                return;
            }

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) return;

            // Проверяем входные поля через приватный метод
            $validData = $this->validateUpdatePasswordInput($data);
            if ($validData === null) return;

            $currentPassword = $validData['current_password'];
            $newPassword = $validData['new_password'];

            // Меняем пароль через метод сервиса
            $this->accountService->updatePassword($userId, $currentPassword, $newPassword);

            // Защищаем от фиксаций на сессии по регенерации id
            $this->authSession->regenerateId();

            $this->logger->info('Password updated for {user_id}', [
                'user_id' => $userId,
            ]);

            $this->success();

        } catch (AppException $e) {
            // Кастомный класс для ошибки в бизнес логике

            $this->error(
                422,
                $e->getErrorCode(),
                $e->getMessage(),
                context: [
                    'exception' => $e,
                ]
            );

            return;

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to update password for {user_id}',
                context: [
                    'user_id' => $userId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для удаления аккаунта пользователя
    public function delete(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(
                    401,
                    'UNAUTHENTICATED',
                    'Authentication required',
                );

                return;
            }

            // Удаляем аккаунт через метод сервиса
            $this->accountService->deleteUser($userId);

            // Очищаем сессию
            $this->authSession->logout();

            $this->logger->info('Account deleted for user {user_id}', [
                'user_id' => $userId,
            ]);

            $this->success(204);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to delete {user_id}',
                context: [
                    'user_id' => $userId,
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для валидации входных полей при смене пароля
    // Возвращает проверенный массив либо null
    private function validateUpdatePasswordInput(array $data): ?array {
        // Проверяем валидность старого пароля
        $currentPassword = isset($data['current_password']) ? (string)$data['current_password'] : null;
        $currentPassword = $this->validatePassword($currentPassword);
        if ($currentPassword === null) {
            return null;
        }

        // Проверяем валидность нового пароля
        $newPassword = isset($data['new_password']) ? (string)$data['new_password'] : null;
        $newPassword = $this->validatePassword($newPassword);
        if ($newPassword === null) {
            return null;
        }

        // Проверяем что новый и старый пароли разные
        if ($currentPassword === $newPassword) {
            $this->error(
                422,
                'SAME_PASSWORD',
                'New and current passwords are the same',
            );

            return null;
        }

        $passwordConfirmation = isset($data['new_password_confirmation']) ? (string)$data['new_password_confirmation'] : null;

        // Проверяем что пароли совпадают
        if ($newPassword !== $passwordConfirmation) {
            $this->error(
                422,
                'PASSWORD_MISMATCH',
                'Passwords do not match',
            );

            return null;
        }

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'current_password' => $currentPassword,
            'new_password' => $newPassword
        ];
    }
}