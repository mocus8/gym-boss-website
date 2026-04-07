<?php 
// Класс-сервис для синхронизации статуса заказа со статусом платежа в юкассе

namespace App\Payments;
use App\Orders\OrderService;
use App\Payments\PaymentService;
use App\Mail\MailService;
use App\Integrations\Yookassa\YookassaGateway;
use App\Support\Logger;

class PaymentStatusSyncService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private string $baseUrl;
    private OrderService $orderService;    // экземпляр сервиса для заказов (dependency injection)
    private PaymentService $paymentService;
    private MailService $mailService;
    private YookassaGateway $yookassaGateway;    // экземпляр YookassaGateway для взаимодействия с sdk
    private Logger $logger;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        string $baseUrl,
        OrderService $orderService,
        PaymentService $paymentService,
        MailService $mailService,
        YookassaGateway $yookassaGateway,
        Logger $logger
    ) {
        $this->db = $db;
        $this->baseUrl = $baseUrl;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->mailService = $mailService;
        $this->yookassaGateway = $yookassaGateway;
        $this->logger = $logger;
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
                $justMarked = $this->orderService->markPaidInTx($orderId);
                $this->paymentService->updateStatusByExternalId($externalPaymentId, 'succeeded', $providerStatus);

                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollback();
                throw $e;
            }

            // Если статус реально поменялся - отправляем письмо 
            if ($justMarked) {
                // Получаем данные о заказе
                $orderData = $this->orderService->getById($orderId);

                // Собираем ссылку на страницу заказа
                $orderUrl = $this->baseUrl . '/orders/' . $orderId;

                // Отправляем письмо
                $this->mailService->sendOrderConfirmation(
                    $orderData['order'],
                    $orderData['items'],
                    $orderUrl
                );
            }

            return;
        } 
        // Если платеж отменен
        elseif ($providerStatus === 'canceled') {
            $this->paymentService->updateStatusByExternalId($externalPaymentId, 'canceled', $providerStatus);
            return;
        }
        // Если платеж ожидается
        elseif ($providerStatus === 'pending' || $providerStatus === 'waiting_for_capture') {
            $this->paymentService->updateStatusByExternalId($externalPaymentId, 'pending', $providerStatus);
            return;
        }

        // Для любого другого статуса (неизвестный/битый/новый) помечаем платеж как unknown и логируем
        $this->paymentService->updateStatusByExternalId($externalPaymentId, 'unknown', $providerStatus);

        $this->logger->error('Unknown payment status {provider_status} from provider for payment {external_payment_id}', [
            'provider_status' => $providerStatus,
            'external_payment_id' => $externalPaymentId
        ]);

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
        if (empty($externalPaymentId)) {
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
