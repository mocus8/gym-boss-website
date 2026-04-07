<?php
declare(strict_types=1);

namespace App\Payments;

// Класс-репозиторий для взаимодействия с бд
class PaymentRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для создания платежа
    public function create(int $orderId, float $orderTotal, string $idempotencyKey): int {
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

        $status = 'creating';
        $stmt->bind_param("isds", $orderId, $status, $orderTotal, $idempotencyKey);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        // Получаем paymentId как AUTO_INCREMENT последней успешно вставленной строки для этого соединения
        $paymentId  = $this->db->insert_id;
        
        $stmt->close();

        return $paymentId;
    }

    // Метод для отметки платежа как pending
    public function setPending(string $externalId, string $confirmationUrl, ?string $expiresAt, int $id): void {
        $sql = "
            UPDATE payments
            SET status = 'pending',
                external_payment_id = ?, 
                confirmation_url = ?,
                expires_at = ?
            WHERE id = ?
                AND status = 'creating'
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("sssi", $externalId, $confirmationUrl, $expiresAt, $id);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для отметки платежа как pending
    public function setFailed(int $paymentId, string $errorCode, string $errorMessage): void {
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

    // Метод для обновления статуса платежа по external Id
    public function updateStatusByExternalId(
        string $externalId,
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

        $stmt->bind_param("sssss", $newProviderStatus, $newStatus, $errorCode, $errorMessage, $externalId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод для обновления статуса платежа по external Id
    public function cancelAllByOrderId(int $orderId, ?string $errorCode = null, ?string $errorMessage = null): void {
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

    // Метод для нахождения активного платежа
    public function findActivePayment(int $orderId): ?array {
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

    // Метод для нахождения ссылки на активный платеж
    public function findActivePaymentUrl(int $orderId): ?string {
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

    // Метод для нахождения активного платежа
    public function findOrderIdByExternalId(string $externalPaymentId): int {
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

        return (int)$row['order_id'];
    }
}