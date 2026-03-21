<?php
// Контроллер для взаимодействия с аккаунтами пользователй (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Auth\AuthSession;
use App\Account\AccountService;
// use App\Support\Logger;    // пространство имен для логгера, на будующее

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

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

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
}