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

            // Комитим транзакцию
            $this->db->commit();

        } catch (\Throwable $e) {
            // Если где-то выпало исключение откатываем изменения в бд и выкидываем исключения дальше
            $this->db->rollback();
            throw $e;
        }

        // Собираем ссылку вида http://localhost/auth/email/verify?token=...
        $verifyUrl = $this->baseUrl . '/auth/email/verify?' . http_build_query( ['token' => $rawToken], '', '&', PHP_QUERY_RFC3986);
        // Отправляем письмо со ссылкой через метод mailService 
        try {
            $this->mailService->sendEmailVerificationLink($email, $name, $verifyUrl);
        } catch (\Throwable $e) {
            throw new AuthException(
                'EMAIL_SEND_FAILURE',
                'Failed to send verification email',
                0,
                $e
            );
        }

        // Возвращаем userId
        return $userId;
    }
}