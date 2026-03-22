<?php
// Контроллер для взаимодействия с аккаунтами пользователй (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Auth\AuthSession;
use App\Account\AccountService;

// Класс для управления классами пользователей (через методы сервиса)
class AccountController extends BaseController {
    private AuthSession $authSession;
    private AccountService $accountService;

    public function __construct(AuthSession $authSession, AccountService $accountService) {
        $this->authSession = $authSession;
        $this->accountService = $accountService;
    }

    // Будующий конструктор (с логером)
    // public function __construct(AuthSession $authSession, AccountService $accountService, Logger $logger) {
    //     $this->authSession = $authSession;
    //     $this->accountService = $accountService;
    //     parent::__construct($logger);    // передаем логгер в родительский класс
    // }

    // Метод для изменения данных аккаунта (имени)
    public function updateProfile(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
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

            $this->success(200, ['name' => $name]);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Auth register failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
        }
    }

    // Метод для смены пароля, зная текущий
    public function updatePassword(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
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

            $this->success();

        } catch (AppException $e) {
            // Кастомный класс для ошибки в бизнес логике
            $this->error(422, $e->getErrorCode(), $e->getMessage());

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Auth register failed', [
            //     'exception' => $e,
            // ]);

            $this->error();
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
            $this->error(422, 'SAME_PASSWORD', 'New and current passwords are the same');
            return null;
        }

        $passwordConfirmation = isset($data['new_password_confirmation']) ? (string)$data['new_password_confirmation'] : null;

        // Проверяем что пароли совпадают
        if ($newPassword !== $passwordConfirmation) {
            $this->error(422, 'PASSWORD_MISMATCH', 'Passwords do not match');
            return null;
        }

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'current_password' => $currentPassword,
            'new_password' => $newPassword
        ];
    }
}