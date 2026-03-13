<?php
// Контроллер для взаимодействия с аутентификацией пользователй (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Auth\AuthSession;
use App\Auth\AuthService;
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для управления аутентификацией пользователей (через методы сервиса)
class AuthController {
    private AuthSession $authSession;    // приватное свойство (переменная класса), привязанная к объекту
    private AuthService $authService;
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    public function __construct(AuthSession $authSession, AuthService $authService) {
        $this->authSession = $authSession;
        $this->authService = $authService;
    }

    // Будующий конструктор (с логером)
    // public function __construct(CartService $cartService, Logger $logger) {
    //    $this->authSession = $authSession;
    //    $this->authService = $authService;
    //     $this->logger = $logger;
    // }

    // Приватный метод для получения, декодирования и проверки json входных данных
    private function getJsonBody(): ?array {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
    
        if (!is_array($data)) {
            $this->error(400, 'INVALID_REQUEST', 'Invalid JSON body');
            return null;
        }
    
        return $data;
    }

    // Приватная функция для отправки успеха
    private function success(int $status = 200, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    // Приватная функция для отправки ошибки
    // Возможно логгер сюда переместить
    private function error(
        int $status = 500,
        string $code = 'INTERNAL_SERVER_ERROR',
        string $message = 'Internal server error'
    ): void {
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

    // Метод для валидации входных полей при регистрации
    // Возвращает проверенный массив либо null
    private function validateRegisterInput(array $data): ?array {
        // Проверяем наличие email
        $email = isset($data['email']) ? trim((string)$data['email']) : null;
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

        // Проверяем наличие пароля
        $password = isset($data['password']) ? (string)$data['password'] : null;
        if (!$password) {
            $this->error(422, 'PASSWORD_REQUIRED', 'Password is required');
            return null;
        }

        // Проверяем длинну пароля
        if (strlen($password) < 8) {
            $this->error(422, 'PASSWORD_TOO_SHORT', 'Password is too short');
            return null;
        } else if (strlen($password) > 64) {
            $this->error(422, 'PASSWORD_TOO_LONG', 'Password is too long');
            return null;
        }

        // Проверяем, что пароль содержит только печатаемые ASCII-символы
        if (!preg_match('/^[\x20-\x7E]+$/', $password)) {
            $this->error(422, 'PASSWORD_INVALID_CHARS', 'Password contains invalid characters');
            return null;
        }

        // Проверяем наличие имени
        $name = isset($data['name']) ? trim((string)$data['name']) : null;
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

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'email' => $validatedEmail,
            'password' => $password,
            'name' => $name
        ];
    }

    // Метод для старта регистрации:
    // Проверяет что пользователь не залогинен
    // Принимает и валидирует поля пользователя (email, password, name)
    // Вызывает метод сервиса для регистрации
    // Формирует ответ
    // Обработчик запроса POST /api/auth/register
    public function register(): void {
        try {
            if ($this->authSession->getUserId() !== null) {
                // Пользователь уже залогинен
                $this->error(409, 'ALREADY_AUTHENTICATED', 'User already authenticated');
                return;
            }

            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) return;

            // Проверяем входные поля через приватный метод
            $validData = $this->validateRegisterInput($data);
            if ($validData === null) return;

            $email = $validData['email'];
            $password = $validData['password'];
            $name = $validData['name'];

            // Вызываем метод регистрации в auth сервисе
            $userId = $this->authService->register($email, $password, $name);

            // Если получилось - логиним пользователя
            $this->authSession->login($userId);

            $this->success(201, [
                'userId' => $userId,
                'email' => $email,
                'name' => $name,
                'emailVerified' => false
            ]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());
        
        } catch (AuthException $e) {
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

    // Метод для повторной отправки письма для подтверждения почты
    public function resendEmailVerification(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
                return;
            }

            // Вызываем метод повторной отправки письма для подтерждения
            $this->authService->resendEmailVerification($userId); // TODO сделать метод сервиса и универсальны метод проверки авторизации 

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (AuthException $e) {
            // Кастомный класс для ошибки в бизнес логике

            // Если есть значение кулдауна - передаем его в заголовке
            if ($e->getRetryAfter() !== null) {
                header('Retry-After: ' . $e->getRetryAfter());
            }

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
}