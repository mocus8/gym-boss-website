<?php
// Контроллер для взаимодействия с аутентификацией пользователй (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты (POST /api/cart/add-item и т.д.).

// Тут добавить логирование и документацию для этого api

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Support\AppException;
use App\Auth\AuthSession;
use App\Auth\AuthService;

// Класс для управления аутентификацией пользователей (через методы сервиса)
class AuthController extends BaseController {
    private AuthSession $authSession;    // приватное свойство (переменная класса), привязанная к объекту
    private AuthService $authService;

    public function __construct(AuthSession $authSession, AuthService $authService) {
        $this->authSession = $authSession;
        $this->authService = $authService;
    }

    // Будующий конструктор (с логером)
    // public function __construct(AuthSession $authSession, AuthService $authService, Logger $logger) {
    //    $this->authSession = $authSession;
    //    $this->authService = $authService;
    //    parent::__construct($logger);    // передаем логгер в родительский класс
    // }

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
                'user_id' => $userId,
                'email' => $email,
                'name' => $name,
                'is_email_verified' => false
            ]);

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());
        
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

    // Метод для повторной отправки письма для подтверждения почты
    // Обработчик запроса POST /api/auth/email/resend
    public function resendVerification(): void {
        try {
            // Получаем id пользователя
            $userId = $this->authSession->getUserId();

            // Если null - ошибку
            if ($userId === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
                return;
            }

            // Вызываем метод повторной отправки письма для подтерждения
            $this->authService->resendVerification($userId);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (AppException $e) {
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

    // Метод для входа в аккаунт
    // Обработчик запроса POST /api/auth/login
    public function login(): void {
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
            $validData = $this->validateLoginInput($data);
            if ($validData === null) return;

            $email = $validData['email'];
            $password = $validData['password'];

            // Вызываем метод логина в auth сервисе (получаем инфу о пользователе)
            $userInfo = $this->authService->login($email, $password);

            // Если получилось - логиним пользователя
            $this->authSession->login($userInfo['id']);

            $this->success(200, [
                'user_id' => $userInfo['id'],
                'email' => $email,
                'name' => $userInfo['name'],
                'is_email_verified' => $userInfo['is_verified']
            ]);

        } catch (AppException $e) {
            // Кастомный класс для ошибки в бизнес логике

            // Если есть значение кулдауна - передаем его в заголовке
            if ($e->getRetryAfter() !== null) {
                header('Retry-After: ' . $e->getRetryAfter());
            }

            $this->error(401, $e->getErrorCode(), $e->getMessage());

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

    // Метод для логаута
    // Обработчик запроса POST /api/auth/logout
    public function logout(): void {
        try {
            // Если пользователь не залогинен
            if ($this->authSession->getUserId() === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
                return;
            }

            // Чистим сессию
            $this->authSession->logout();

            // Возвращаем успех через приватную функцию
            $this->success(204);

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

    // Метод для получения информации о текущем пользователе в сессии
    // Обработчик запроса GET /api/auth/me
    public function me(): void  {
        try {
            $userId = $this->authSession->getUserId();

            // Если пользователь не залогинен
            if ($userId === null) {
                $this->error(401, 'UNAUTHENTICATED', 'Authentication required');
                return;
            }

            // Получаем информацию о пользователе через метод сервиса
            $userInfo = $this->authService->getUserInfo($userId);

            // Возвращаем информацию
            $this->success(200, [
                'user_id' => $userId,
                'email' => $userInfo['email'],
                'name' => $userInfo['name'],
                'is_email_verified' => $userInfo['is_verified']
            ]);

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

    // Метод для начала сброса пароля, принимает email, ответ одинаковый для всех исходов (защита от user-enumeration)
    // Обработчик запроса POST /api/auth/password/forgot
    public function forgotPassword(): void {
        try {
            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) return;

            // Валидируем email через метод
            $email = isset($data['email']) ? (string)$data['email'] : null;
            $email = $this->validateEmail($email);
            if ($email === null) return;
    
            // Начинаем процесс сброса пароля через метод сервиса
            $this->authService->sendPasswordResetLink($email);

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

        } catch (AppException $e) {
            // Кастомный класс для ошибки в бизнес логике
            // Для защиты от user-enumeration тут показываем только ошибку по лимиту отправки писем

            $this->error(429, 'EMAIL_RATE_LIMIT', $e->getMessage());

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

    // Метод для сброса пароля 
    // Обработчик запроса POST /api/auth/password/reset
    public function resetPassword(): void {
        try {
            // Получаем json тело запроса и декодируем его через приватный метод
            $data = $this->getJsonBody();
            if ($data === null) return;

            // Проверяем входные поля через приватный метод
            $validData = $this->validateResetPasswordInput($data);
            if ($validData === null) return;

            $token = $validData['token'];
            $password = $validData['password'];
    
            // Начинаем процесс сброса пароля через метод сервиса
            $this->authService->resetPassword($token, $password);

            // Защищаем от фиксаций на сессии по регенерации id
            $this->authSession->regenerateId();

            // Возвращаем успех через приватную функцию
            $this->success();

        } catch (\InvalidArgumentException $e) {
            // Ошибка пользователя/некорректные данные - 422 + честное описание
            $this->error(422, 'VALIDATION_ERROR', $e->getMessage());

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

    // Приватный метод для валидации входных полей при регистрации
    // Возвращает проверенный массив либо null
    private function validateRegisterInput(array $data): ?array {
        // Проверяем email через метод
        $email = isset($data['email']) ? (string)$data['email'] : null;
        $email = $this->validateEmail($email);
        if ($email === null) {
            return null;
        }

        // Проверяем валидность пароля через метод
        $password = isset($data['password']) ? (string)$data['password'] : null;
        $password = $this->validatePassword($password);
        if ($password === null) {
            return null;
        }

        // Проверяем валидность имени через метод
        $name = isset($data['name']) ? (string)$data['name'] : null;
        $name = $this->validateName($name);
        if ($name === null) {
            return null;
        }

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'email' => $email,
            'password' => $password,
            'name' => $name
        ];
    }

    // Метод для валидации входных полей при входе в аккаунт
    // Возвращает проверенный массив либо null
    private function validateLoginInput(array $data): ?array {
        // Проверяем email через метод
        $email = isset($data['email']) ? (string)$data['email'] : null;
        $email = $this->validateEmail($email);
        if ($email === null) {
            return null;
        }

        // Проверяем валидность пароля через метод
        $password = isset($data['password']) ? (string)$data['password'] : null;
        $password = $this->validatePassword($password);
        if ($password === null) {
            return null;
        }

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'email' => $email,
            'password' => $password
        ];
    }

    // Метод для валидации входных полей при сбросе пароля
    // Возвращает проверенный массив либо null
    private function validateResetPasswordInput(array $data): ?array {
        // Проверяем что токен не пустой
        $token = isset($data['token']) ? (string)$data['token'] : null;
        if ($token === null || $token === '') {
            $this->error(422, 'TOKEN_INVALID', 'Token is required');
            return null;
        }

        // Проверяем валидность пароля через метод
        $password = isset($data['password']) ? (string)$data['password'] : null;
        $password = $this->validatePassword($password);
        if ($password === null) {
            return null;
        }

        $passwordConfirmation = isset($data['password_confirmation']) ? (string)$data['password_confirmation'] : null;

        // Проверяем что пароли совпадают
        if ($password !== $passwordConfirmation) {
            $this->error(422, 'PASSWORD_MISMATCH', 'Passwords do not match');
            return null;
        }

        // Если все проверки прошли - возвращаем проверенный ассоциативный массив
        return [
            'token' => $token,
            'password' => $password
        ];
    }
}