<?php
// Базовый класс для всех контроллеров, включает в себя нужные для дочерних классов методы

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Support\Logger;

// Базовый класс для контроллеров, abstract - нельзя создать экземпляр напрямую, только через наследников
abstract class BaseController {
    protected Logger $logger;    // Логгер для передачи в зависимость в конструкторе

    // Будующий конструктор (с логером)
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    // Защищенный метод для получения, декодирования и проверки json входных данных
    // Protected не позволяет пользоваться методом из вне, но позволет использовать его наследникам
    protected function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            $this->error(400, 'INVALID_REQUEST', 'Invalid JSON body');
            return null;
        }
    
        return $data;
    }

    // Метод для отправки успеха
    protected function success(int $status = 200, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    // Метод для отправки ошибки
    // Возможно логгер сюда переместить
    protected function error(
        int $status = 500,
        string $code = 'INTERNAL_SERVER_ERROR',
        string $message = 'Internal server error',
        array $context = []
    ): void {
        // Определяем уровень по статусу
        $level = $status >= 500 ? 'error' : 'warning';

        // Логируем
        // {$level} - variable function - динамический вызов метода (подставляется переменная)
        $this->logger->{$level}($message, array_merge([
            'status' => $status,
            'code'   => $code,
            'path'   => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        ], $context));

        // Даем ответ
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // Метод для валидации почты, возвращает null или валидированный email
    protected function validateEmail(mixed $rawEmail): ?string {
        $email = trim((string)$rawEmail);

        // Проверяем наличие email
        if (!$email) {
            $this->error(422, 'EMAIL_REQUIRED', 'Email is required');
            return null;
        }

        // Защита от скрытых символов и пробелов внутри
        if (preg_match("/[\r\n\t\0]/", $email) || strpos($email, ' ') !== false) {
            $this->error(422, 'EMAIL_INVALID', 'Email contains invalid characters');
            return null;
        }

        // Проверяем общую длинну email
        if (strlen($email) > 254) {
            $this->error(422, 'EMAIL_TOO_LONG', 'Email is too long');
            return null;
        }

        // Проверяем синтаксис email
        $validatedEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($validatedEmail === false) {
            $this->error(422, 'EMAIL_INVALID', 'Invalid email');
            return null;
        }

        // Проверяем длинну local-части email
        [$local, $domain] = explode('@', $validatedEmail, 2);
        if ($local === '' || strlen($local) > 63) {
            $this->error(422, 'EMAIL_INVALID', 'Email local part empty or too long');
            return null;
        }

        // Возвращаем валидированный email
        return $validatedEmail;
    }

    // Метод для проверки валидности пароля
    protected function validatePassword(mixed $rawPassword): ?string {
        $password = (string)$rawPassword;

        // Проверяем наличие пароля
        if (!$password) {
            $this->error(422, 'PASSWORD_REQUIRED', 'Password is required');
            return null;
        }

        // Проверяем длинну пароля
        if (strlen($password) < 8) {
            $this->error(422, 'PASSWORD_TOO_SHORT', 'Password is too short');
            return null;
        } elseif (strlen($password) > 64) {
            $this->error(422, 'PASSWORD_TOO_LONG', 'Password is too long');
            return null;
        }

        // Проверяем, что пароль содержит только печатаемые ASCII-символы
        if (!preg_match('/^[\x20-\x7E]+$/', $password)) {
            $this->error(422, 'PASSWORD_INVALID_CHARS', 'Password contains invalid characters');
            return null;
        }

        // Возвращаем валидированный пароль
        return $password;
    }

    // Метод для проверки валидности имени
    protected function validateName(mixed $rawName): ?string {
        $name = trim((string)$rawName);

        // Проверяем наличие имени
        if (!$name) {
            $this->error(422, 'NAME_REQUIRED', 'Name is required');
            return null;
        }

        // Проверяем длинну имени
        if (mb_strlen($name, 'UTF-8') > 100) {
            $this->error(422, 'NAME_TOO_LONG', 'Name is too long');
            return null;
        }

        // Проверяем допустимые символы в имени (буквы (любой язык), пробел, точка, дефис, апостроф)
        if (!preg_match("/^[\p{L}\s\.\'-]+$/u", $name)) {
            $this->error(422, 'NAME_INVALID_CHARS', 'Name contains invalid characters');
            return null;
        }

        // Возвращаем валидированное имя
        return $name;
    }
}