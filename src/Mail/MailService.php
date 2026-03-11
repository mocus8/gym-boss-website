<?php
// Класс для отправки писем, для этого он использует методы gateway-я
// Не имеет своего контроллера, вызывается из разных сервисов

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Mail\MailService;
use App\Integrations\Resend\EmailMessageDto;
use App\Integrations\Resend\ResendGateway;

class MailService {
    private ResendGateway $resendGateway;    // экземпляр ResendGateway для взаимодействия с sdk resend-а

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(ResendGateway $resendGateway) {
        $this->resendGateway = $resendGateway;
    }

    // Вспомогательный приватный метод для рендера шалона 
    // Подставляет переменные из data в шаблон и возвращает его в виде строки
    private function renderTemplate(string $templatePath, array $data = []): string {
        // Проверяем что файл шаблона существует
        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        // Через буфер начинаем записывать контент шаблона
        ob_start();

        // Пробуем передать инфу из data в шаблон записать его в переменную
        try {
            // Достаем из ассоц. массива удобные локальные переменные
            // EXTR_SKIP - не перезаписываем уже заданные переменные
            extract($data, EXTR_SKIP);

            // Подключаем шаблон письма
            require $templatePath;

            // Записываем все из буфера в переменную и проверяем что удалось
            $output = ob_get_clean();
            if ($output === false) {
                throw new \RuntimeException('Failed to render template: ' . $templatePath);
            }

            return $output;
        } catch (\Throwable $e) {
            // Если какая-либо ошибка - очищаем буфер и пробрасываем дальше
            ob_end_clean();
            throw $e;
        }
    }

    // Метод для отправки письма с ссылкой для верификации почты
    public function sendEmailVerificationLink(string $userEmail, string $userName, string $verifyUrl): void {
        if (trim($userEmail) === '' || trim($userName) === '' || trim($verifyUrl) === '') {
            throw new \InvalidArgumentException('Empty userEmail, userName or verifyUrl');
        }

        // Задаем тему письма
        $subject = 'Подтвердите ваш email для GymBoss';

        // Задаем html-версию письма
        $html = $this->renderTemplate(
            __DIR__ . '/../templates/email/verification.html.php', 
            [
                'userName' => $userName,
                'verifyUrl' => $verifyUrl,
            ]
        );

        // Задаем text-версию письма
        $text = $this->renderTemplate(
            __DIR__ . '/../templates/email/verification.text.php', 
            ['userName' => $userName, 'verifyUrl' => $verifyUrl]
        );

        // Оформляем инфу о письме в DTO
        $message = new EmailMessageDto($userEmail, $subject, $html, $text);

        // Через метод gateway-я пробуем отправить письмо
        try {
            $this->resendGateway->send($message);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Cannot send verification email', 0, $e);
        }
    }
}