<?php
// Вебхук для обработки уведомлений от юкассы

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Payment;
use App\Order\OrderService;
use App\Integrations\Yookassa\YookassaGateway;

class WebhookService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private PaymentStatusSyncService $paymentStatusSyncService;
    private YookassaGateway $yookassaGateway;    // экземпляр YookassaGateway для взаимодействия с sdk

    // Конструктор (магический метод), просто присваиваем внешние переменные в поля создоваемого объекта
    public function __construct(
        \mysqli $db,
        PaymentStatusSyncService $paymentStatusSyncService,
        YookassaGateway $yookassaGateway
    ) {
        $this->db = $db;
        $this->paymentStatusSyncService = $paymentStatusSyncService;
        $this->yookassaGateway = $yookassaGateway;
    }

    // Метод для обработки уведомлений от юкассы
    public function handleNotification(array $payload): void {
        // Получаем информацию о платеже из тела запроса
        $notificationPaymentId = $this->yookassaGateway->getPaymentIdFromNotification($payload);
        // Синхронизируем статус платежа и заказа
        $this->paymentStatusSyncService->syncByExternalPaymentId($notificationPaymentId);
    }
}