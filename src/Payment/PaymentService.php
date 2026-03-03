<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// Вызывается из OrderController, без отдельного контроллера 

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Payment;
use App\Order\OrderService;
use App\Integrations\Yookassa\YookassaGateway;

// Класс для управления платежами
class PaymentService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private string $baseUrl;
    private OrderService $orderService;    // экземпляр сервиса для заказов (dependency injection)
    private YookassaGateway $yookassaGateway;    // экземпляр YookassaGateway для взаимодействия с sdk
    private int $deliveryVatCode;    // код НДС для доставки

    // Константы для типов доставки
    private const DELIVERY_TYPE_COURIER = 1;
    private const DELIVERY_TYPE_PICKUP = 2;

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(
        \mysqli $db,
        string $baseUrl,
        OrderService $orderService,
        YookassaGateway $yookassaGateway,
        int $deliveryVatCode
    ) {
        $this->db = $db;
        $this->baseUrl = $baseUrl;
        $this->orderService = $orderService;
        $this->yookassaGateway = $yookassaGateway;
        $this->deliveryVatCode = $deliveryVatCode;
    }

    // Вспомогательный приватный метод для генерации уникального кода UUID v4
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Вспомогательный приватный метод для получения ссылки для оплаты из активного платежа
    private function getActivePaymentUrl(int $orderId): ?string {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем информацию о последнем активном платеже на заказ
        $sql = "
            SELECT confirmation_url
            FROM payments
            WHERE order_id = ? 
                AND status = 'pending'
                AND (expires_at IS NULL OR expires_at > NOW())
                AND confirmation_url IS NOT NULL
            ORDER BY created_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $orderId);

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

        $payment = $result->fetch_assoc();

        $stmt->close();

        // Если платеж есть
        if ($payment !== null && !empty($payment['confirmation_url'])) {
            return $payment['confirmation_url'];
        }

        // Если платежа нет
        return null;
    }

    // Вспомогательный приватный метод для формирования массива товаров для чека
    private function buildReceiptItems(array $order, array $items): array {
        // Cоздаём массив товаров в нужном для чека формате
        $itemsForReceipt = [];
        foreach ($items as $item) {
            $price = number_format($item['price'], 2, '.', '');
    
            $itemsForReceipt[] = [
                'description' => $item['product_name'],
                'quantity' => $item['amount'],
                'amount' => [
                    'value' => $price,
                    'currency' => 'RUB'
                ],
                'vat_code' => $item['vat_code'],
                'payment_mode' => 'full_payment',
                'payment_subject' => 'commodity'
            ];
        }
    
        // Добавляем стоимость доставки если она есть
        $deliveryCost = number_format((float)$order['delivery_cost'], 2, '.', '');
        $deliveryTypeId = (int)$order['delivery_type_id'];
        if ((float)$order['delivery_cost'] > 0 && $deliveryTypeId === self::DELIVERY_TYPE_COURIER) {
            $itemsForReceipt[] = [
                'description' => 'Доставка',
                'quantity' => 1,
                'amount' => [
                    'value' => $deliveryCost,
                    'currency' => 'RUB'
                ],
                'vat_code' => $this->deliveryVatCode,
                'payment_mode' => 'full_payment',
                'payment_subject' => 'service'    // доставка это услуга, не товар
            ];
        }

        return $itemsForReceipt;
    }

    // Вспомогательный приватный метод для проверки схождения сумм заказа из объекта order и массива itemsForReceipt
    private function checkReceiptTotalMatch(array $itemsForReceipt, float $orderTotal): void {
        // Проверяем итоговую стоимость (должна сходиться с чеком)
        $receiptTotal = 0;
        foreach ($itemsForReceipt as $item) {
            $receiptTotal += (float)$item['amount']['value'] * $item['quantity'];
        }
    
        if (abs($receiptTotal - $orderTotal) > 0.01) {
            throw new \RuntimeException('Receipt total mismatch');
        }
    }

    // Вспомогательный приватный метод для пометки платежа как неудачного (создан черновик в бд, не создался в юкассе)
    private function markPaymentFailed(
        int $paymentId, 
        string $errorCode,
        string $errorMessage
    ): void {
        if ($paymentId <= 0) {
            throw new \InvalidArgumentException('Invalid paymentId');
        }

        // Вносим в строку платежа статус failed и код + сообщение ошибки 
        $sql = "
            UPDATE payments
            SET status = 'failed',
                error_code = ?,
                error_message = ?
            WHERE id = ?
                AND status = 'creating'
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ssi", $errorCode, $errorMessage, $paymentId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
        
        $stmt->close();
    }

    // Вспомогательный приватный метод для маппинга ошибки создания платежа в юкассе
    private function mapToErrorCode(\Throwable $e): string {
        // Ошибки запроса/данных
        if ($e instanceof \YooKassa\Common\Exceptions\BadApiRequestException) {
            return 'BAD_REQUEST';
        }

        // Проблемы соединения/таймауты
        if ($e instanceof \YooKassa\Common\Exceptions\ApiConnectionException) {
            return 'PAYMENT_SERVICE_UNAVAILABLE';
        }

        // Общая ошибка API
        if ($e instanceof \YooKassa\Common\Exceptions\ApiException) {
            return 'PAYMENT_SYSTEM_ERROR';
        }

        // Всё остальное это внутренняя ошибка
        return 'INTERNAL_ERROR';
    }

    // Метод для получения или создания платежа, возвращает существующею/новую ссылку на платеж
    public function getOrCreatePayment(int $orderId, int $userId): string {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid userId');
        }

        // Cтавим ожидание блокировок как 5 секунд, потом ошибка от sql
        $this->db->query("SET SESSION innodb_lock_wait_timeout = 5");

        // Массив со всей инфой о заказе
        // Нужен для синхронизации переменных которые появляются во время транзакции
        // Эти переменные могут не объявится если во время транзакции что-то упадет
        $draftPaymentInfo = null;

        // Статус "черновика" платежа
        $creatingStatus = 'creating';

        // Начинаем транзакцию (либо все либо ничего для sql)
        $this->db->begin_transaction();

        try {
            // Получаем базовую инфу о заказе из orders с блокировкой строки через for update
            $order = $this->orderService->lockForPayment($orderId, $userId);
            $orderTotal = (float)$order['total_price'] + (float)$order['delivery_cost'];
            // Получаем id статуса pending_payment через таблицу-справочник
            $pendingPaymentStatusId = $this->orderService->getStatusIdByCode('pending_payment');

            // Проверяем статус заказа
            if ((int)$order['status_id'] !== $pendingPaymentStatusId) {
                throw new \RuntimeException('Order status is not pending_payment');
            }

            // Смотрим, есть ли активная ссылка на платеж
            $paymentConfirmationUrl = $this->getActivePaymentUrl($orderId);

            // Если ссылка есть то просто возвращаем ее
            if ($paymentConfirmationUrl !== null) {
                $this->db->commit();
                return $paymentConfirmationUrl;
            }

            // Если платежа нет - создаем новый в три этапа:
            // 1. собираем инфу для платежа в массив draftPaymentInfo
            // 2. через gateway юкассы создаем платеж в самой юкассе 
            // 3. если платеж в юкассе создан - дополняем запись платежа в бд, если нет - помечаем как failed

            // Получаем логин пользователя через метод AccountService для формирования чека
            // $email = $this->AccountService->getEmailForReceipt($userId);

            // TODO потом заменить на то, что выше
            // Пока через простой запрос
            $sql = "
                SELECT email
                FROM users
                WHERE id = ?
                LIMIT 1        
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
            $email = $row['email'] ?? null;
            $stmt->close();

            // Получаем залоченые позиции заказа из order_items
            $items = $this->orderService->getItemsForReceipt($orderId);
            // Cоздаём массив товаров в нужном для чека формате
            $itemsForReceipt = $this->buildReceiptItems($order, $items);
            
            // Проверяем итоговую стоимость (должна сходиться с чеком)
            $this->checkReceiptTotalMatch($itemsForReceipt, $orderTotal);
        
            // Фиксированный idempotency key для защиты от дублей
            $idempotencyKey = $this->generateUuidV4();

            // Создаем в бд платеж со статусом creating 
            $sql = "
                INSERT INTO payments (
                    order_id,
                    status,
                    amount,
                    idempotency_key
                )
                VALUES (?, ?, ?, ?)
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("isds", $orderId, $creatingStatus, $orderTotal, $idempotencyKey);

            if (!$stmt->execute()) {
                $error = $stmt->error ?: $this->db->error;
                $stmt->close();
                throw new \RuntimeException('DB execute failed: ' . $error);
            }

            // Получаем paymentId как AUTO_INCREMENT последней успешно вставленной строки для этого соединения
            $paymentId  = $this->db->insert_id;
            
            $stmt->close();

            $this->db->commit();

            // Все нужные далее переменные оборачиваем в массив
            // Так если без ошибок дошло до commit то будет доступен массив со всеми переменными, иначе он null
            $draftPaymentInfo = [
                'paymentId' => $paymentId,
                'orderId' => $orderId,
                'orderTotal' => $orderTotal,
                'email' => $email,
                'itemsForReceipt' => $itemsForReceipt,
                'idempotencyKey' => $idempotencyKey,
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        // Проверяем инфу для создания платежа
        if ($draftPaymentInfo === null) {
            throw new \RuntimeException('Draft payment info is missing');
        }

        // Формируем payload (тело) для создания платежа
        $payload = [
            'amount' => [
                'value' => number_format($draftPaymentInfo['orderTotal'], 2, '.', ''),
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->baseUrl . '/order/' . $draftPaymentInfo['orderId']
            ],
            'capture' => true,
            'description' => 'Заказ №' . $draftPaymentInfo['orderId'],
            'metadata' => ['orderId' => $draftPaymentInfo['orderId']],

            'receipt' => [
                'customer' => [
                    'email' => $draftPaymentInfo['email']
                ],
                'items' => $draftPaymentInfo['itemsForReceipt']
            ]
        ];
        
        // Пытаемся создать платеж в юкассе через gateway-интеграционную оболочку над sdk юкассы
        try {
            // Создается платеж, возвращается массив с инфой о платеже из юкассы
            $createdPayment = $this->yookassaGateway->createPayment($payload, $draftPaymentInfo['idempotencyKey']);
        } catch (\Throwable $e) {
            // При ошибке ставим на "черновик" платежа ошибку и прокидываем ошибку
            $this->markPaymentFailed($draftPaymentInfo['paymentId'], $this->mapToErrorCode($e), $e->getMessage());
            throw $e;
        }

        $sql = "
            UPDATE payments
            SET status = 'pending',
                external_payment_id = ?, 
                confirmation_url = ?,
                expires_at = ?
            WHERE id = ?
                AND status = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        // Переносим значения в локальные переменные для использовании в bind_param
        // Если попытаться передать просто как поле DTO то может вылететь ошибка о попытке модификации приватного поля
        $externalPaymentId = $createdPayment->paymentId;
        $confirmationUrl = $createdPayment->confirmationUrl;
        $expiresAt = $createdPayment->expiresAt;

        $stmt->bind_param(
            "sssis",
            $externalPaymentId,
            $confirmationUrl,
            $expiresAt,
            $draftPaymentInfo['paymentId'],
            $creatingStatus
        );

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();

        return $confirmationUrl;
    }

    // Метод для получения активного платежа
    public function getActivePayment(int $orderId): ?array {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Получаем информацию о последнем активном платеже на заказ
        $sql = "
            SELECT id,
                order_id,
                confirmation_url,
                status,
                external_payment_id,
                expires_at,
                last_sync_at
            FROM payments
            WHERE order_id = ? 
                AND status = 'pending'
                AND (expires_at IS NULL OR expires_at > NOW())
                AND external_payment_id IS NOT NULL
            ORDER BY created_at DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $orderId);

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

        $payment = $result->fetch_assoc();

        $stmt->close();

        return $payment ?: null;
    }

    // Метод для получения id заказа по id платежа из провайдера (юкассы)
    public function getOrderIdByExternalId(
        string $externalPaymentId
    ): int {
        $sql = "
            SELECT order_id
            FROM payments
            WHERE external_payment_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $externalPaymentId);

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
        
        if (!$row) {
            $stmt->close();
            throw new \RuntimeException('Payment not found by external_payment_id');
        }

        $stmt->close();

        $orderId = (int)$row['order_id'];
        return $orderId;
    }

    // Метод для обновления статуса платежа по id из провайдера (юкассы)
    public function updateStatusByExternalId(
        string $externalPaymentId,
        string $newStatus,
        string $newProviderStatus,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): void {
        // В бд для платежа безусловно устанавливаем provider_status и last_sync_at
        // Далее если статус succeeded - не меняем поля, в другом случае устанавливаем новые
        $sql = "
        UPDATE payments
        SET
            provider_status = ?,
            last_sync_at = NOW(),
            status = CASE
                WHEN status = 'succeeded' THEN status
                ELSE ?
            END,
            error_code = CASE
                WHEN status = 'succeeded' THEN error_code
                ELSE ?
            END,
            error_message = CASE
                WHEN status = 'succeeded' THEN error_message
                ELSE ?
            END
        WHERE external_payment_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("sssss", $newProviderStatus, $newStatus, $errorCode, $errorMessage, $externalPaymentId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для пометки в бд всех платежей одного заказа как отмененнных
    public function cancelAllByOrderId(
        int $orderId,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): void {
        if ($orderId <= 0) {
            throw new \InvalidArgumentException('Invalid orderId');
        }

        // Вносим в строку платежа статус и код + сообщение ошибки
        $sql = "
            UPDATE payments
            SET status = 'canceled',
                error_code = ?,
                error_message = ?
            WHERE order_id = ?
                AND status IN ('creating','pending')
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ssi", $errorCode, $errorMessage, $orderId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }
}