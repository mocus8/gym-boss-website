1. Добавить защиту от повторной обработки
$connect->begin_transaction();

try {
    // Блокируем запись для этого order_id
    $stmt = $connect->prepare("
        SELECT status, yookassa_payment_id 
        FROM orders 
        WHERE order_id = ? 
        FOR UPDATE
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    // Проверяем ВСЕ возможные условия
    if ($order['status'] === 'paid') {
        logWebhook("Order $orderId already paid");
        $connect->rollback();
        http_response_code(200);
        exit('OK');
    }
    
    if ($order['yookassa_payment_id'] === $paymentId && $order['status'] === 'pending_payment') {
        logWebhook("Payment $paymentId already linked to order $orderId");
        $connect->rollback();
        http_response_code(200);
        exit('OK');
    }
    
    // Выполняем UPDATE
    $updateStmt = $connect->prepare("
        UPDATE orders 
        SET status = 'paid', 
            paid_at = NOW(),
            yookassa_payment_id = ?
        WHERE order_id = ?
    ");
    $updateStmt->bind_param("si", $paymentId, $orderId);
    $updateStmt->execute();
    
    $connect->commit();
    
} catch (Exception $e) {
    $connect->rollback();
    throw $e;
}
2. Добавить проверку актуального статуса через API
Внутри if ($requestBody['event'] === 'payment.succeeded') { добавить в начало:

php
// Проверяем актуальный статус у ЮКассы
try {
    $actualPayment = $yookassa->getPaymentInfo($payment->getId());
    if ($actualPayment->getStatus() !== 'succeeded') {
        logWebhook("Status mismatch for payment " . $payment->getId() . 
                   ". Webhook: succeeded, Actual: " . $actualPayment->getStatus());
        http_response_code(200);
        exit('OK');
    }
} catch (Exception $e) {
    logWebhook("Failed to verify payment status for " . $payment->getId() . ": " . $e->getMessage());
    // Продолжаем обработку, но логируем
}
3. Расширить обработку всех статусов
Заменить switch ($requestBody['event']) { на обработку по статусу:

php
// Получаем статус из объекта
$status = $payment->getStatus();
$paymentId = $payment->getId();
$metadata = $payment->getMetadata();
$orderId = $metadata['orderId'] ?? null;

logWebhook("Processing payment $paymentId with status: $status, order: " . ($orderId ?? 'unknown'));

switch ($status) {
    case 'succeeded':
        // Существующая логика оплаты
        if (!$orderId) {
            logWebhook("No orderId in metadata for payment $paymentId");
            http_response_code(200);
            exit('OK');
        }
        
        // ... ваш существующий код обновления заказа ...
        break;
        
    case 'canceled':
    case 'failed':
        // Обновляем статус заказа на cancelled
        if ($orderId) {
            $stmt = $connect->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            logWebhook("Order $orderId marked as cancelled (payment $paymentId)");
            $stmt->close();
        }
        break;
        
    case 'pending':
        // Ничего не делаем, ждем
        logWebhook("Payment $paymentId is pending");
        break;
        
    case 'waiting_for_capture':
        // Автоподтверждаем платеж (если бизнес-логика позволяет)
        try {
            $response = $yookassa->capturePayment([
                'amount' => $payment->getAmount()
            ], $paymentId);
            logWebhook("Payment $paymentId captured automatically");
        } catch (Exception $e) {
            logWebhook("Failed to capture payment $paymentId: " . $e->getMessage());
        }
        break;
        
    default:
        logWebhook("Unknown payment status: $status for payment $paymentId");
}
4. Использовать те же коды ошибок что в create_payment.php
Заменить общие исключения на конкретные:

php
// Вместо:
if (!$connect) {
    throw new Exception('Database connection failed');
}

// Использовать:
if (!$connect) {
    throw new Exception('DATABASE_CONNECT_FAILED');
}

// И в catch-блоке:
} catch (Exception $e) {
    $errorCode = $e->getMessage();
    $safeErrorCodes = [
        'DATABASE_CONNECT_FAILED',
        'DATABASE_OPERATIONS_FAILED',
        'ORDER_NOT_FOUND'
    ];
    
    if (in_array($errorCode, $safeErrorCodes)) {
        logWebhook("Webhook error: " . $errorCode);
    } else {
        // Маскируем системные ошибки
        logWebhook("Webhook error: [SYSTEM_ERROR]");
    }
}
5. Добавить транзакцию для целостности данных
Обернуть обновление заказа в транзакцию:

php
$connect->begin_transaction();

try {
    $stmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), status = 'paid' WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    // Помечаем платеж как обработанный (дополнительная таблица)
    $stmt2 = $connect->prepare("INSERT INTO processed_payments (payment_id, order_id) VALUES (?, ?)");
    $stmt2->bind_param("si", $paymentId, $orderId);
    $stmt2->execute();
    
    $connect->commit();
    logWebhook('SUCCESS: Order ' . $orderId . ' marked as paid');
    
} catch (Exception $e) {
    $connect->rollback();
    throw $e;
}
Краткий список изменений:
✅ Защита от повторной обработки (проверка по yookassa_payment_id)

✅ Проверка актуального статуса через API

✅ Обработка всех статусов (succeeded, canceled, pending, waiting_for_capture)

✅ Консистентные коды ошибок с create_payment.php

✅ Транзакция для целостности данных