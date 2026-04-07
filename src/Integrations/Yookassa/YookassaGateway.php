<?php
// Всё взаимодействие с sdk юкассы оборачивается в методы этого класса, удобная интеграционная обёртка над sdk

namespace App\Integrations\Yookassa;
use YooKassa\Client;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Notification\NotificationCanceled;


class YookassaGateway {
    // Приватное поле - api клиент для использоания sdk юкассы
    private Client $client;

    public function __construct(string $shopId, string $secretKey) {
        // Создаем api клиент и настраиваем его авторизацию для использования sdk
        $this->client = new Client();
        $this->client->setAuth($shopId, $secretKey);
    }

    // Метод создания платежа, возвращает объект класса DTO с нужной инфой о платеже из юкассы
    // Payload - основное "тело", вся полезная инфа для создания платежа
    public function createPayment(array $payload, string $idempotencyKey): CreatedPaymentDto {
        $payment = $this->client->createPayment($payload, $idempotencyKey);

        $paymentId = $payment->getId();

        $confirmation = $payment->getConfirmation();
        $confirmationUrl = $confirmation ? $confirmation->getConfirmationUrl() : null;
        if (!$confirmationUrl) {
            throw new \RuntimeException('YooKassa payment created without confirmationUrl');
        }

        $expiresAtDateTime = $payment->getExpiresAt();
        $expiresAt = $expiresAtDateTime ? $expiresAtDateTime->format('Y-m-d H:i:s') : null;

        return new CreatedPaymentDto($paymentId, $confirmationUrl, $expiresAt);
    }

    // Метод для получения статуса платежа по id
    public function getPaymentStatus(string $paymentId): string {
        return $this->client->getPaymentInfo($paymentId)->getStatus();
    }

    // Метод для получения id платежа из уведомления
    public function getPaymentIdFromNotification(array $payload): string {
        if (!isset($payload['event'])) {
            throw new \RuntimeException('YooKassa notification without event');
        }

        // На уровне SDK выбираем правильный класс уведомления
        switch ($payload['event']) {
            case NotificationEventType::PAYMENT_SUCCEEDED:
                $notification = new NotificationSucceeded($payload);
                break;
            case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                $notification = new NotificationWaitingForCapture($payload);
                break;
            case NotificationEventType::PAYMENT_CANCELED:
                $notification = new NotificationCanceled($payload);
                break;
            default:
                // Неизвестный статус
                throw new \RuntimeException('Unsupported YooKassa notification event: '.$payload['event']);
        }

        // Получаем объект платежа
        $payment = $notification->getObject();

        // Возвращаем id заказа
        return $payment->getId();
    }
}