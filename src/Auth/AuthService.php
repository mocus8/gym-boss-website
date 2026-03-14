<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Auth;
use App\Mail\MailService;

// Класс для управления авторизацией пользователей
class AuthService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private MailService $mailService;
    private string $baseUrl;

    private array $emailVerifiedCache = [];    // кэш с верификацией пользователей: userId -> bool
    private const RESEND_COOLDOWN_SECONDS = 60;    // константа с временем кулдауна на отправку писем
    private const VERIFY_TOKEN_TTL_SECONDS = 900;    // константа с временем жизни токена подверждения (15 минут)

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(\mysqli $db, MailService $mailService, string $baseUrl) {
        $this->db = $db;
        $this->mailService = $mailService;
        $this->baseUrl = $baseUrl;
    }

    // Приватный вспомагательный метод для генерации токена, его хеширования и записи в бд
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
        // Если есть старый токен для пользователя - он перезаписывается
        $sql = "
            INSERT INTO email_verification_tokens (
                user_id,
                token,
                created_at
            )
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                token = ?,
                created_at = CURRENT_TIMESTAMP
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iss', $userId, $hashedToken, $hashedToken);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();

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
            throw new AuthException('EMAIL_SEND_FAILURE', 'Failed to send verification email', previous: $e);
        }
    }

    // Метод для регистрации
    // Регистрирует пользователя и инициализирует verify-процесс
    public function register(string $email, string $password, string $name): int {
        // Ищем этот email в бд
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

        // Если строчка нашлась - ошибку
        if ($row) {
            throw new AuthException('EMAIL_TAKEN', 'User with this email already exists');
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
                    throw new AuthException('EMAIL_TAKEN', 'User with this email already exists');
                }

                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            // Получаем userId как AUTO_INCREMENT последней успешно вставленной строки для этого соединения
            $userId = $this->db->insert_id;

            $stmt->close();

            // Генерируем и получаем токен через вспомагательный метод, также хешированный токен записывается в бд
            $rawToken = $this->createEmailVerificationToken($userId);

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Собираем и отправляем ссылку для подтверждения почты через приватный метод
        $this->createAndsendEmailVerificationLink($rawToken, $email, $name);

        // Возвращаем userId
        return $userId;
    }

    // Метод для повторной отправки пользователю письма для подтверждения почты
    public function resendVerification(int $userId): void {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Получаем инфу о пользователе из бд
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

        // Пользователь не найден
        if ($row === null) {
            throw new \RuntimeException("User not found: $userId");
        }

        if ($row['email_verified_at'] !== null) {
            throw new AuthException('EMAIL_ALREADY_VERIFIED', 'Users email is already verified');
        }

        $email = $row['email'];
        $name = $row['name'];
        if (!$email || !$name) {
            throw new \RuntimeException('Empty user email or name');
        }

        // Получаем время создания прошлого токена 
        $sql = "
            SELECT created_at
            FROM email_verification_tokens
            WHERE user_id = ?
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

        // Запись с токеном не найдена
        if ($row === null) {
            // Создаем токен
            $rawToken = $this->createEmailVerificationToken($userId);
            // Отправляем письмо
            $this->createAndsendEmailVerificationLink($rawToken, $email, $name);

            return;
        }

        $tokenCreatedAtRaw = $row['created_at'];
        if (!$tokenCreatedAtRaw) {
            throw new \RuntimeException('Empty created_at');
        }

        // Получаем настоящее время и разницу в секундах
        $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

        // Считаем сколько осталось до снятия кулдауна
        $retryAfter = self::RESEND_COOLDOWN_SECONDS - $diffSeconds;

        // Если разница меньше заданного кулдауна - ошибку и понятный для фронта код
        if ($diffSeconds < self::RESEND_COOLDOWN_SECONDS) {
            throw new AuthException('RESEND_TOO_SOON', 'Email resend attempt too soon', $retryAfter);
        }
        
        // Генерируем и получаем токен через вспомагательный метод, также хешированный токен записывается в бд
        $rawToken = $this->createEmailVerificationToken($userId);

        // Собираем и отправляем ссылку для подтверждения почты через приватный метод
        $this->createAndsendEmailVerificationLink($rawToken, $email, $name);
    }

    // Метод для проверки верификации почты пользователя по его id 
    public function isEmailVerified(int $userId): bool {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Сначала проверяем запись в кеше (в свойстве объекта класса), если есть - возвращаем ее 
        if (array_key_exists($userId, $this->emailVerifiedCache)) {
            return $this->emailVerifiedCache[$userId];
        }

        // Проверяем по бд
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

        // Пользователь не найден
        if ($row === null) {
            throw new \RuntimeException("User not found: $userId");
        }

        // Проверяем полученное состояние почты, кешируем и возвращаем результат
        $isVerified = $row['email_verified_at'] !== null;
        $this->emailVerifiedCache[$userId] = $isVerified;
        return $isVerified;
    }

    // Метод для верификации почты пользователя 
    public function verify(string $rawToken): void {
        // Хэшируем токен через sha256
        $hashedToken = hash('sha256', $rawToken);

        // Получаем инфу о токене из бд
        $sql = "
            SELECT
                user_id,
                created_at
            FROM email_verification_tokens
            WHERE token = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $hashedToken);

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

        // Токен не найден
        if ($row === null) {
            throw new AuthException('TOKEN_INVALID', 'Token not found');
        }

        $userId = $row['user_id'];
        $tokenCreatedAtRaw = $row['created_at'];

        // Если у пользователя уже подтвержденная почта - ошибку
        if ($this->isEmailVerified($userId)) {
            throw new AuthException('EMAIL_ALREADY_VERIFIED', 'Users email already verified');
        }

        // Находим время, прошедшее с момента создания токена
        $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

        // Если ttl токена истекло - ошибку
        if ($diffSeconds > self::VERIFY_TOKEN_TTL_SECONDS ) {
            throw new AuthException('TOKEN_EXPIRED', 'Verify token has expired');
        }

        // Начинаем транзакцию (либо выполняются все sql запросы либо ни одного)
        $this->db->begin_transaction();

        try {
            // Помечаем почту пользователя как подтвержденную
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

            // Удаляем использованный токен подтверждения
            $sql = "
                DELETE FROM email_verification_tokens
                WHERE token = ?
            ";
        
            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }
        
            $stmt->bind_param('s', $hashedToken);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }
        
            $stmt->close();

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Обновляем кеш
        $this->emailVerifiedCache[$userId] = true;
    }
}