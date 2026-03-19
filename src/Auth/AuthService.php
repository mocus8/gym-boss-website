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

    private array $verifiedEmailsCache = [];    // кэш с верификацией пользователей: userId -> bool
    private const SEND_EMAIL_COOLDOWN_SECONDS = 60;    // константа с временем кулдауна на отправку писем
    private const VERIFY_TOKEN_TTL_SECONDS = 86400;    // константа с временем жизни токена подверждения (24 часа)
    private const LOGIN_ATTEMPTS_WINDOW = 900; // константа с окном для ввода непраивльного пароля (15 минут)
    private const MAX_LOGIN_ATTEMPTS = 5;    // константа с кол-вом попыток ввода пароля
    private const LOGIN_ATTEMPTS_TTL = 86400;    // константа с временем жизни попыток входа, для аналитики
    private const RESET_PASSWORD_TOKEN_TTL_SECONDS = 3600;    // константа с временем жизни токена подверждения (60 минут)

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(\mysqli $db, MailService $mailService, string $baseUrl) {
        $this->db = $db;
        $this->mailService = $mailService;
        $this->baseUrl = $baseUrl;
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
        $this->createAndSendEmailVerificationLink($rawToken, $email, $name);

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

        // Если прошлый токен найден и кулдаун не прошел - исключение по лимиту отправки
        if ($row !== null) {
            $tokenCreatedAtRaw = $row['created_at'];
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
                throw new AuthException('EMAIL_RATE_LIMIT', 'Email resend attempt too soon', $retryAfter);
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

        // Сначала проверяем запись в кеше (в свойстве объекта класса), если есть - возвращаем ее 
        if (array_key_exists($userId, $this->verifiedEmailsCache)) {
            return $this->verifiedEmailsCache[$userId];
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
        $this->verifiedEmailsCache[$userId] = true;
    }

    // Метод для входа пользователя в аккаунт, сверяется пароль и возвращается инфа о пользователе
    public function login(string $email, string $inputPassword): array {
        // Проверяем лимит неудачных попыток
        $this->checkLoginLimit($email);

        // Находим в бд по email инфу о пользователе
        $sql = "
            SELECT
                id,
                email_verified_at,
                password,
                name
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

        // Если пользователь не нашелся - ошибку
        if (!$row) {
            throw new AuthException('INVALID_CREDENTIALS', 'Wrong password or email');
        }

        $id = $row["id"];
        $password = $row["password"];
        $name = $row["name"];
        $isVerified = $row["email_verified_at"] !== null;

        // Сравниваем пароли из бд и введеный через password_verify (сравнивает введеный с хешем из бд)
        if (!password_verify($inputPassword, $password)) {
            // Если пароль не подходит - записываем неудачную попытку и выкидываем исключение
            $this->addLoginAttempt($email);
            throw new AuthException('INVALID_CREDENTIALS', 'Wrong password or email');
        }

        // Удаляем записи о попытках для этого email
        $this->deleteLoginAttemptsOnEmail($email);

        // Возвращаем инфу о пользователе
        return [
            'id' => $id,
            'name' => $name,
            'is_verified' => $isVerified,
        ];
    }

    // Метод для удаления устаревших попыток входа
    public function deleteOldLoginAttempts(): void {
        $sql = "
            DELETE FROM login_attempts
            WHERE attempted_at < NOW() - INTERVAL ? SECOND
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $ttl = self::LOGIN_ATTEMPTS_TTL;
        $stmt->bind_param('i', $ttl);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для получения информации о пользователе по id
    public function getUserInfo(int $userId): array {
        // Находим в бд по email инфу о пользователе
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

        // Если пользователь не нашелся - ошибку
        if (!$row) {
            throw new \RuntimeException('User not found');
        }

        $email = $row["email"];
        $name = $row["name"];
        $isVerified = $row["email_verified_at"] !== null;

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
        $sql = "
            SELECT
                name
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

        // Если пользователь не нашелся - тихо выходим
        if (!$row) return;

        $name = $row["name"];

        // Получаем время создания прошлого токена 
        $sql = "
            SELECT created_at
            FROM password_reset_tokens
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

        // Если прошлый токен найден и кулдаун не прошел - исключение по лимиту отправки
        if ($row !== null) {
            $tokenCreatedAtRaw = $row['created_at'];
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
                throw new AuthException('EMAIL_RATE_LIMIT', 'Email resend password attempt too soon', $retryAfter);
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
        $sql = "
            SELECT
                email,
                created_at
            FROM password_reset_tokens
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

        $email = $row['email'];
        $tokenCreatedAtRaw = $row['created_at'];

        // Находим время, прошедшее с момента создания токена
        $tokenCreatedAt = new \DateTimeImmutable($tokenCreatedAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $tokenCreatedAt->getTimestamp();

        // Если ttl токена истекло - ошибку
        if ($diffSeconds > self::RESET_PASSWORD_TOKEN_TTL_SECONDS ) {
            throw new AuthException('TOKEN_EXPIRED', 'Password reset token has expired');
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
            $sql = "
                UPDATE users
                SET password = ?
                WHERE email = ?
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("ss", $hashedPassword, $email);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            // Проверяем, что пароль действительно изменен
            if ($stmt->affected_rows === 0) {
                $stmt->close();
                throw new \RuntimeException('User not found for token email');
            }

            $stmt->close();

            // Удаляем использованный токен
            $this->deletePasswordResetToken($email);

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

    // Приватный вспомагательный метод для валидации токена
    private function validateToken(string $token): void {
        if (strlen($token) !== 64 || !preg_match('/^[0-9a-f]{64}$/i', $token)) {
            throw new AuthException('TOKEN_INVALID', 'Invalid token format');
        }
    }

    // Приватный вспомагательный метод для проверки лимита на попытки ввода пароля по email
    private function checkLoginLimit(string $email): void {
        // Находим кол-во попыток за время (задано в константе)
        $sql = "
            SELECT COUNT(*)
            FROM login_attempts
            WHERE email = ?
                AND attempted_at > NOW() - INTERVAL ? SECOND             
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $window = self::LOGIN_ATTEMPTS_WINDOW;
        $stmt->bind_param("si", $email, $window);

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

        $row = $result->fetch_row();

        if ($row === null) {
            $stmt->close();
            throw new \RuntimeException('DB fetch_row failed');
        }

        $stmt->close();

        // Кол-во попыток за последние LOGIN_ATTEMPTS_WINDOW секунд
        $count = (int)$row[0];

        // Если превысили лимит - ошибку 
        if ($count >= self::MAX_LOGIN_ATTEMPTS) {
            throw new AuthException('LOGIN_ATTEMPTS_EXCEEDED', 'Too many login attempts');
        }
    }

    // Приватный вспомагательный метод для фиксирования неудачной попытки
    private function addLoginAttempt(string $email): void {
        $sql = "
            INSERT INTO login_attempts (
                email,
                attempted_at
            )
            VALUES (?, CURRENT_TIMESTAMP)
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('s', $email);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Приватный вспомагательный метод для удаления записи о попытках входах по email
    private function deleteLoginAttemptsOnEmail(string $email): void {
        $sql = "
            DELETE FROM login_attempts
            WHERE email = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('s', $email);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
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
        // Если есть старый токен для почты - он перезаписывается
        $sql = "
            INSERT INTO password_reset_tokens (
                email,
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
    
        $stmt->bind_param('sss', $email, $hashedToken, $hashedToken);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();

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

    // Приватный вспомагательный метод для удаления токена по email
    private function deletePasswordResetToken(string $email): void {
        $sql = "
            DELETE FROM password_reset_tokens
            WHERE email = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('s', $email);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }
}