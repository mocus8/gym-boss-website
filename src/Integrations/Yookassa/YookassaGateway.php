<?php
// Всё взаимодействие с sdk юкассы оборачивается в методы этого класса, удобная интеграционная обёртка над sdk

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Integrations\Yookassa;
use YooKassa\Client;
use YooKassa\Model\PaymentInterface;

class YookassaGateway {
    // Приватное поле - api клиент для использоания sdk юкассы
    private Client $client;

    public function __construct(string $shopId, string $secretKey) {
        // Создаем api клиент и настраиваем его авторизацию для использования sdk
        $this->client = new Client();
        $this->client->setAuth($shopId, $secretKey);
    }

    // Метод создания платежа, возвращает массив с нужной инфой о платеже из юкассы
    // Payload - основное "тело", вся полезная инфа для создания платежа
    // TODO мб сделать DTO вместо просто массива
    public function createPayment(array $payload, string $idempotencyKey): array {
        $paymentInfo = [];

        $payment = $this->client->createPayment($payload, $idempotencyKey);

        $paymentInfo['confirmationUrl'] = $payment->getConfirmation()->getConfirmationUrl();
        $expiresAtDateTime = $payment->getExpiresAt();
        $paymentInfo['expiresAt'] = $expiresAtDateTime ? $expiresAtDateTime->format('Y-m-d H:i:s') : null;
        $paymentInfo['paymentId'] = $payment->getId();

        return $paymentInfo;
    }

    // Метод для получения статуса платежа по id
    public function getPaymentStatus(string $paymentId): string {
        return $this->client->getPaymentInfo($paymentId)->getStatus();
    }
}