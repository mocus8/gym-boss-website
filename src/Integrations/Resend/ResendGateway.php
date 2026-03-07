<?php
// Всё взаимодействие с sdk resend оборачивается в методы этого класса, удобная интеграционная обёртка над sdk

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Integrations\Resend;
use Resend;
use Resend\Client;

class ResendGateway {
    private Client $client;    // api клиент для использоания sdk resend
    private string $fromEmail;
    private string $fromName;
    private string $replyTo;


    public function __construct(string $secretKey, string $fromEmail, string $fromName, string $replyTo) {
        // Создаем api клиент и настраиваем его авторизацию для использования sdk
        $this->client = Resend::client($secretKey);

        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->replyTo = $replyTo;
    }

    public function send(EmailMessageDto $message): string {
        // Заполняем тело запроса обязательными параметрами
        $payload = [
            'from' => sprintf('%s <%s>', $this->fromName, $this->fromEmail),
            'to' => is_array($message->to) ? $message->to : [$message->to],
            'subject' => $message->subject,
        ];

        // Добавляем в тело параметры, которые есть

        if ($message->html !== null) {
            $payload['html'] = $message->html;
        }

        if ($message->text !== null) {
            $payload['text'] = $message->text;
        }

        if ($message->replyTo !== null) {
            $payload['replyTo'] = $message->replyTo;
        }

        // Отправляем письмо и сохраняем результат этого в $response
        $response = $this->client->emails->send($payload);

        // Возвращаем id отпрваленного письма
        return $response->id ?? '';
    }
}