<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Auth;

use App\Support\AppException;
use App\Users\UserRepository;
use App\Mail\MailService;
use App\Support\Logger;

// Класс для управления авторизацией пользователей
class AuthService {
    private \mysqli $db;
    private UserRepository $userRepository;
    private EmailVerificationTokenRepository $emailVerificationTokenRepository;
    private LoginAttemptRepository $loginAttemptRepository;
    private PasswordResetTokenRepository $passwordResetTokenRepository;
    private MailService $mailService;
    private string $baseUrl;
    private Logger $logger;

    private array $verifiedEmailsCache = [];    // кэш с верификацией пользователей: userId -> bool
    private const SEND_EMAIL_COOLDOWN_SECONDS = 60;    // время кулдауна на отправку писем
    private const VERIFY_TOKEN_TTL_SECONDS = 86400;    // время жизни токена подверждения (24 часа)
    private const LOGIN_ATTEMPTS_WINDOW = 900; // окно для ввода непраивльного пароля (15 минут)
    private const MAX_LOGIN_ATTEMPTS = 5;    // кол-во попыток ввода пароля
    private const LOGIN_ATTEMPTS_TTL = 86400;    // время жизни попыток входа, для аналитики
    private const RESET_PASSWORD_TOKEN_TTL_SECONDS = 3600;    // время жизни токена подверждения (60 минут)

    public function __construct(
        \mysqli $db, 
        UserRepository $userRepository,
        EmailVerificationTokenRepository $emailVerificationTokenRepository,
        LoginAttemptRepository $loginAttemptRepository,
        PasswordResetTokenRepository $passwordResetTokenRepository,
        MailService $mailService, 
        string $baseUrl, 
        Logger $logger
    ) {
        $this->db = $db;
        $this->userRepository = $userRepository;
        $this->emailVerificationTokenRepository = $emailVerificationTokenRepository;
        $this->loginAttemptRepository = $loginAttemptRepository;
        $this->passwordResetTokenRepository = $passwordResetTokenRepository;
        $this->mailService = $mailService;
        $this->baseUrl = $baseUrl;
        $this->logger = $logger;
    }

    // Метод для регистрации
    // Регистрирует пользователя и инициализирует verify-процесс
    public function register(string $email, string $password, string $name): array {
        $this->logger->info('User registration started');
        
        // Ищем этот email в бд
        $userId = $this->userRepository->findIdByEmail($email);

        // Если не нашелся - ошибку
        if ($userId) {
            throw new AppException('EMAIL_TAKEN', 'User with this email already exists');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Хэшируем пароль и проверям что удалось
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                throw new \RuntimeException('Password hashing failed');
            }

            // Создаем строку в бд с новым пользователем
            $userId = $this->userRepository->create($email, $hashedPassword, $name);

            // Генерируем и получаем токен через вспомагательный метод, также хешированный токен записывается в бд
            $rawToken = $this->createEmailVerificationToken($userId);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Пробуем собрать и отправить ссылку для подтверждения почты
        try {
            $this->createAndSendEmailVerificationLink($rawToken, $email, $name);

        } catch (\Throwable $e) {
            $this->logger->warning('User {user_id} created, but verification email sending failed', [
                'user_id' => $userId,
                'email' => $email,
                'exception' => $e,
            ]);

            // Если не удалось отправить письмо - все равно возвращаем user id и флаг о том что письмо не отправлено
            return [
                'user_id' => $userId,
                'email_sent' => false
            ];
        }

        $this->logger->info('User {user_id} created', [
            'user_id' => $userId,
        ]);

        // Возвращаем userId и флаг о том что письмо отправлено
        return [
            'user_id' => $userId,
            'email_sent' => true
        ];
    }

    // Метод для повторной отправки пользователю письма для подтверждения почты
    public function resendVerification(int $userId): void {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Получаем инфу о пользователе из бд
        $userVerificationInfo = $this->userRepository->findUserVerificationInfoById($userId);

        // Пользователь не найден
        if ($userVerificationInfo === null) {
            throw new \RuntimeException("User not found: $userId");
        }

        if ($userVerificationInfo['email_verified_at'] !== null) {
            throw new AppException('EMAIL_ALREADY_VERIFIED', 'Users email is already verified');
        }

        $email = $userVerificationInfo['email'];
        $name = $userVerificationInfo['name'];
        if (!$email || !$name) {
            throw new \RuntimeException('Empty user email or name');
        }

        // Получаем время создания прошлого токена
        $tokenInfo = $this->emailVerificationTokenRepository->findByUserId($userId);

        // Если прошлый токен найден и кулдаун не прошел - исключение по лимиту отправки
        if ($tokenInfo !== null) {
            $tokenCreatedAtRaw = $tokenInfo['created_at'];
            if (!$tokenCreatedAtRaw) {
                throw new \RuntimeException('Empty created_at');
            }

            // Получаем настоящее время и разницу в секундах
            $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
            $now = new \DateTimeImmutable('now');
            $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

            // Считаем сколько осталось до снятия кулдауна
            $retryAfter = self::SEND_EMAIL_COOLDOWN_SECONDS - $diffSeconds;

            // Если разница меньше заданного кулдауна - ошибку и понятный для фронта код
            if ($diffSeconds < self::SEND_EMAIL_COOLDOWN_SECONDS) {
                throw new AppException('EMAIL_RATE_LIMIT', 'Email resend attempt too soon', $retryAfter);
            }
        }
        
        // Генерируем и получаем токен через вспомагательный метод, также хешированный токен записывается в бд
        $rawToken = $this->createEmailVerificationToken($userId);

        // Собираем и отправляем ссылку для подтверждения почты через приватный метод
        $this->createAndSendEmailVerificationLink($rawToken, $email, $name);
    }

    // Метод для проверки верификации почты пользователя по его id 
    public function isEmailVerified(int $userId): bool {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid userId');
        }
    
        if (array_key_exists($userId, $this->verifiedEmailsCache)) {
            return $this->verifiedEmailsCache[$userId];
        }
    
        $profile = $this->userRepository->findProfileById($userId);
    
        if ($profile === null) {
            throw new \RuntimeException("User not found: $userId");
        }
    
        $isVerified = $profile['email_verified_at'] !== null;
        $this->verifiedEmailsCache[$userId] = $isVerified;
    
        return $isVerified;
    }

    // Метод для верификации почты пользователя 
    public function verify(string $rawToken): void {
        // Валидируем токен
        $this->validateToken($rawToken);

        // Хэшируем токен через sha256
        $hashedToken = hash('sha256', $rawToken);

        // Получаем инфу о токене из бд
        $tokenInfo = $this->emailVerificationTokenRepository->findVerificationTokenInfo($hashedToken);

        // Токен не найден
        if ($tokenInfo === null) {
            throw new AppException('TOKEN_INVALID', 'Token not found');
        }

        $userId = $tokenInfo['user_id'];
        $tokenCreatedAtRaw = $tokenInfo['created_at'];

        // Если у пользователя уже подтвержденная почта - ошибку
        if ($this->isEmailVerified($userId)) {
            throw new AppException('EMAIL_ALREADY_VERIFIED', 'Users email already verified');
        }

        // Находим время, прошедшее с момента создания токена
        $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

        // Если ttl токена истекло - ошибку
        if ($diffSeconds > self::VERIFY_TOKEN_TTL_SECONDS ) {
            throw new AppException('TOKEN_EXPIRED', 'Verify token has expired');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Помечаем почту пользователя как подтвержденную
            $this->userRepository->markEmailAsVerified($userId);

            // Удаляем использованный токен подтверждения
            $this->emailVerificationTokenRepository->delete($hashedToken);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Обновляем кеш
        $this->verifiedEmailsCache[$userId] = true;

        // Логируем успех
        $logger->info('Email verification succeeded', [
            'user_id' => $userId,
        ]);
    }

    // Метод для входа пользователя в аккаунт, сверяется пароль и возвращается инфа о пользователе
    public function login(string $email, string $inputPassword): array {
        // Проверяем лимит неудачных попыток
        $this->checkLoginLimit($email);

        // Находим в бд по email инфу о пользователе
        $authInfo = $this->userRepository->findAuthInfoByEmail($email);

        // Если пользователь не нашелся - ошибку
        if (!$authInfo) {
            throw new AppException('INVALID_CREDENTIALS', 'Wrong password or email');
        }

        $id = $authInfo["id"];
        $password = $authInfo["password"];
        $name = $authInfo["name"];
        $isVerified = $authInfo["email_verified_at"] !== null;

        // Сравниваем пароли из бд и введеный через password_verify (сравнивает введеный с хешем из бд)
        if (!password_verify($inputPassword, $password)) {
            // Если пароль не подходит - записываем неудачную попытку и выкидываем исключение
            $this->loginAttemptRepository->addAttempt($email);
            throw new AppException('INVALID_CREDENTIALS', 'Wrong password or email');
        }

        // Удаляем записи о попытках для этого email
        $this->loginAttemptRepository->deleteAttemptsByEmail($email);

        // Возвращаем инфу о пользователе
        return [
            'id' => $id,
            'name' => $name,
            'is_verified' => $isVerified,
        ];
    }

    // Метод для удаления устаревших попыток входа
    public function deleteOldLoginAttempts(): void {
        $ttl = self::LOGIN_ATTEMPTS_TTL;
        $this->loginAttemptRepository->deleteOldAttempts($ttl);
    }

    // Метод для получения информации о пользователе по id
    public function getUserInfo(int $userId): array {
        // Находим в бд по id инфу о пользователе
        $profileInfo = $this->userRepository->findProfileById($userId);

        // Если пользователь не нашелся - ошибку
        if (!$profileInfo) {
            throw new \RuntimeException('User not found');
        }

        $email = $profileInfo["email"];
        $name = $profileInfo["name"];
        $isVerified = $profileInfo["email_verified_at"] !== null;

        // Возвращаем инфу о пользователе
        return [
            'email' => $email,
            'name' => $name,
            'is_verified' => $isVerified,
        ];
    }

    // Метод для начала сброса пароля
    // Создает токен, собирает ссылку и отправляет письмо через метод MailService
    // Не выкидываем исключений, который могут раскрыть существование пользователя (user-enumeration)
    public function sendPasswordResetLink(string $email): void {
        // Находим в бд по email имя пользователя 
        $name = $this->userRepository->findNameByEmail($email);

        // Если пользователь не нашелся - тихо выходим
        if (!$name) return;

        // Получаем время создания прошлого токена 
        $tokenCreatedAtRaw = $this->passwordResetTokenRepository->findPreviousTokenCreatedAt($email);

        if ($tokenCreatedAtRaw) {
            // Получаем настоящее время и разницу в секундах
            $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
            $now = new \DateTimeImmutable('now');
            $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();
    
            // Считаем сколько осталось до снятия кулдауна
            $retryAfter = self::SEND_EMAIL_COOLDOWN_SECONDS - $diffSeconds;
    
            // Если разница меньше заданного кулдауна - ошибку и понятный для фронта код
            if ($diffSeconds < self::SEND_EMAIL_COOLDOWN_SECONDS) {
                throw new AppException('EMAIL_RATE_LIMIT', 'Email resend password attempt too soon', $retryAfter);
            }
        }
        
        // Генерируем и получаем токен через вспомагательный метод, также хешированный токен записывается в бд
        $rawToken = $this->createPasswordResetToken($email);

        // Собираем и отправляем ссылку для подтверждения почты через приватный метод
        $this->createAndSendPasswordResetLink($rawToken, $email, $name);
    }

    // Метод сброса пароля
    public function resetPassword(string $rawToken, string $password): void {
        // Валидируем токен
        $this->validateToken($rawToken);
        
        // Хэшируем токен через sha256
        $hashedToken = hash('sha256', $rawToken);

        // Получаем инфу о токене из бд
        $tokenInfo = $this->passwordResetTokenRepository->findTokenInfo($hashedToken);

        // Токен не найден
        if ($tokenInfo === null) {
            throw new AppException('TOKEN_INVALID', 'Token not found');
        }

        $email = $tokenInfo['email'];
        $tokenCreatedAtRaw = $tokenInfo['created_at'];

        // Находим время, прошедшее с момента создания токена
        $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

        // Если ttl токена истекло - ошибку
        if ($diffSeconds > self::RESET_PASSWORD_TOKEN_TTL_SECONDS ) {
            throw new AppException('TOKEN_EXPIRED', 'Password reset token has expired');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Хэшируем пароль и проверям что удалось
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                throw new \RuntimeException('Password hashing failed');
            }

            // Меняем пароль пользователя на новый
            $this->userRepository->setPasswordByEmail($hashedPassword, $email);

            // Удаляем использованный токен
            $this->passwordResetTokenRepository->deleteByEmail($email);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        } 
    }

    // Приватный вспомагательный метод для генерации токена подтверждения почты, его хеширования и записи в бд
    private function createEmailVerificationToken(int $userId): string {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Генерируем случайный токен для подтверждения пользователем почты (64 hex-символа)
        // random_bytes даёт криптографически безопасные случайные байты
        // bin2hex превращает их в URL‑безопасную строку
        $rawToken = bin2hex(random_bytes(32));
        // Хэшируем токен через sha256
        $hashedToken = hash('sha256', $rawToken);

        // Записываем в бд новую строку с токеном для подтверждения почты
        $this->emailVerificationTokenRepository->create($userId, $hashedToken);

        return $rawToken;
    }

    // Приватный вспомагательный метод для создания ссылки подтверждения и отправки письма с ней
    private function createAndSendEmailVerificationLink(string $rawToken, string $email, string $name): void {
        // Собираем ссылку вида http://localhost/auth/email/verify?token=...
        $verifyUrl = $this->baseUrl . '/auth/email/verify?' . http_build_query( ['token' => $rawToken], '', '&', PHP_QUERY_RFC3986);

        // Отправляем письмо со ссылкой через метод mailService 
        try {
            $this->mailService->sendEmailVerificationLink($email, $name, $verifyUrl);
        } catch (\Throwable $e) {
            // Создаем класс используя именованные аргументы (можно пропустить один, не по порядку)
            throw new AppException('EMAIL_SEND_FAILURE', 'Failed to send verification email', previous: $e);
        }
    }

    // Приватный вспомагательный метод для валидации токена
    private function validateToken(string $token): void {
        if (strlen($token) !== 64 || !preg_match('/^[0-9a-f]{64}$/i', $token)) {
            throw new AppException('TOKEN_INVALID', 'Invalid token format');
        }
    }

    // Приватный вспомагательный метод для проверки лимита на попытки ввода пароля по email
    private function checkLoginLimit(string $email): void {
        $window = self::LOGIN_ATTEMPTS_WINDOW;

        // Находим кол-во попыток за время
        $attemptsInfo = $this->loginAttemptRepository->findAttemptsInfoByEmail($email, $window);
        
        $count = (int)$attemptsInfo['attempts'];        

        // Если превысили лимит - ошибку 
        if ($count >= self::MAX_LOGIN_ATTEMPTS) {
            $firstAttemptAt = strtotime($attemptsInfo['first_attempt']);    // переводится в unix-timestamp
            $fromFirstAttempt = time() - $firstAttemptAt;    // сколько секунд прошло с первой попытки
            $retryAfter = max($window - $fromFirstAttempt, 1);    // сколько ещё ждать (минимум 1 секунда)

            throw new AppException('LOGIN_ATTEMPTS_EXCEEDED', 'Too many login attempts', $retryAfter);
        }
    }

    // Приватный вспомагательный метод для генерации токена для сброса пароля, его хеширования и записи в бд
    private function createPasswordResetToken(string $email): string {
        // Генерируем случайный токен (64 hex-символа)
        // random_bytes даёт криптографически безопасные случайные байты
        // bin2hex превращает их в URL‑безопасную строку
        $rawToken = bin2hex(random_bytes(32));
        // Хэшируем токен через sha256
        $hashedToken = hash('sha256', $rawToken);

        // Записываем в бд новую строку с токеном для сброса пароля
        $this->passwordResetTokenRepository->create($email, $hashedToken);

        return $rawToken;
    }

    // Приватный вспомагательный метод для создания ссылки сброса пароля и отправки письма с ней
    private function createAndSendPasswordResetLink(string $rawToken, string $email, string $name): void {
        // Собираем ссылку вида http://localhost/auth/password/reset?token=...
        $resetUrl = $this->baseUrl . '/auth/password/reset?' . http_build_query( ['token' => $rawToken], '', '&', PHP_QUERY_RFC3986);

        // Отправляем письмо со ссылкой через метод mailService
        try {
            $this->mailService->sendPasswordResetLink($email, $name, $resetUrl);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to send password reset email', 0, $e);
        }
    }
}