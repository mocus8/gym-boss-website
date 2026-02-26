<?php 
// Класс-сервис для синхронизации статуса заказа со статусом платежа в юкассе

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Payment;
use App\Order\OrderService;
use App\Payment\PaymentService;
use App\Integrations\Yookassa\YookassaGateway;

class PaymentStatusSyncService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private OrderService $orderService;    // экземпляр сервиса для заказов (dependency injection)
    private PaymentService $paymentService;
    private YookassaGateway $yookassaGateway;    // экземпляр YookassaGateway для взаимодействия с sdk

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        OrderService $orderService,
        PaymentService $paymentService,
        YookassaGateway $yookassaGateway
    ) {
        $this->db = $db;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->yookassaGateway = $yookassaGateway;
    }

    // Метод для синхронизации статуса заказа в бд со статусом платежа оператора (юкассы) по id платежа из юкассы
    public function syncByExternalPaymentId(string $externalPaymentId): void {
        // Получаем статус платежа в юкассе
        $providerStatus = $this->yookassaGateway->getPaymentStatus($externalPaymentId);

        // Если платеж уже оплачен - помечаем
        if ($providerStatus === 'succeeded') {
            $this->db->begin_transaction();

            try {
                $orderId = $this->paymentService->getOrderIdByExternalId($externalPaymentId);
                $this->orderService->markPaidInTx($orderId);
                $this->paymentService->updateStatusByExternalId($externalPaymentId, 'succeeded');

                $this->db->commit();
                return;
            } catch (\Throwable $e) {
                $this->db->rollback();
                throw $e;
            }
        } 
        // Если платеж отменен
        else if ($providerStatus === 'canceled') {
            $this->paymentService->updateStatusByExternalId($externalPaymentId, 'canceled');
            return;
        }
        // Если платеж ожидается
        else if ($providerStatus === 'pending' || $providerStatus === 'waiting_for_capture') {
            $this->paymentService->updateStatusByExternalId($externalPaymentId, 'pending');
            return;
        }

        // Для любого другого статуса (неизвестный/битый/новый) помечаем платеж как unknown и логируем
        $this->paymentService->updateStatusByExternalId($externalPaymentId, 'unknown');
        // TODO: потом сделать правильно через логер (с контекстом)
        error_log("Unknown payment status from provider: $providerStatus for payment: $externalPaymentId");
        return;
    }

    // Метод-обертка над методом syncByExternalPaymentId, находит paymentId и вызывает метод syncByExternalPaymentId
    public function syncByOrderId(int $orderId): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        $paymentInfo = $this->paymentService->getActivePayment($orderId);
        if (!$paymentInfo) {
            return;
        }

        $externalPaymentId = $paymentInfo['external_payment_id'];
        if ($externalPaymentId === '') {
            throw new \RuntimeException('Active payment has no external_payment_id');
        }

        $lastSyncAtRaw = $paymentInfo['last_sync_at'] ?? null;
        // Если последнее время синхронизации указано как null - то синхронизируем первый раз и выходим
        if (!$lastSyncAtRaw) {
            $this->syncByExternalPaymentId($externalPaymentId);
            return;
        }
        // Получаем настоящее время, время последней синхронизации и разницу в секундах
        $lastSyncAt = new \DateTimeImmutable($lastSyncAtRaw);
        $now = new \DateTimeImmutable('now');
        $diffSeconds = $now->getTimestamp() - $lastSyncAt->getTimestamp();
        // Если разница меньше 15 секунд - выходим
        if ($diffSeconds < 15) {
            return;
        }

        $this->syncByExternalPaymentId($externalPaymentId);
    }
}
