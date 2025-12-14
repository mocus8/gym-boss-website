<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/envLoader.php';

use YooKassa\Model\Notification\NotificationFactory;

// Функция логирования вебхука (для финального сделать нормальное через redis или laravel)
function logWebhook($message) {
    $logFile = __DIR__ . '/webhook.log';
    
    // Проверяем существует ли файл
    if (!file_exists($logFile)) {
        // Создаем файл
        file_put_contents($logFile, '');
        chmod($logFile, 0644); // Права на чтение/запись
    }
    
    file_put_contents($logFile, 
        date('Y-m-d H:i:s') . ' - ' . $message . "\n", 
        FILE_APPEND
    );
}

logWebhook('Request from IP: ' . $_SERVER['REMOTE_ADDR']);

// Проверка IP адреса места откуда идет уведомление
$remoteIP = $_SERVER['REMOTE_ADDR'];
$ipTrusted = false;
$trustedRanges = [
    '185.71.76.0/27',
    '185.71.77.0/27',
    '77.75.153.0/25',
    '77.75.156.11', 
    '77.75.156.35',
    '77.75.154.128/25',
    '2a02:5180::/32'
];

foreach ($trustedRanges as $range) {
    if (strpos($range, '/') !== false) {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($remoteIP);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        if (($ip & $mask) == ($subnet & $mask)) {
            $ipTrusted = true;
            break;
        }
    } else {
        if ($remoteIP === $range) {
            $ipTrusted = true;
            break;
        }
    }
}

if (!$ipTrusted) {
    logWebhook('Untrusted IP: ' . $remoteIP);
    http_response_code(403);
    exit('Forbidden');
}

try {
    $source = file_get_contents('php://input');
    $requestBody = json_decode($source, true);

    if (!$requestBody) {
        throw new Exception('INVALID_JSON');
    }

    logWebhook('Event received: ' . ($requestBody['event'] ?? 'unknown'));

    // Создаем объект уведомления через фабрику (встроенный в SDK юкассы класс)
    $factory = new NotificationFactory();
    $notification = $factory->factory($requestBody);
    $payment = $notification->getObject();
    
    $yookassaPaymentId = $payment->getId();
    $yookassaPaymentStatus = $payment->getStatus();
    $metadata = $payment->getMetadata();
    $orderId = $metadata['orderId'] ?? null;
    
    logWebhook("Payment ID: $yookassaPaymentId, Status: $yookassaPaymentStatus, Order: " . ($orderId ?? 'unknown'));

    // Если нет orderId в metadata - выходим
    if (!$orderId) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    $connect = getDB();
    if (!$connect) {
        throw new Exception('DATABASE_ERROR');
    }

    // Транзакция для целостности данных
    $connect->begin_transaction();

    try {
        // Проверяем повторную обработку одного и того же заказа (блокируем запись и проверяем статус)
        $checkStmt = $connect->prepare("
            SELECT status, yookassa_payment_id 
            FROM orders 
            WHERE order_id = ? 
            FOR UPDATE
        ");
        $checkStmt->bind_param("i", $orderId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $order = $result->fetch_assoc();
        $checkStmt->close();

        if (!$order) {
            throw new Exception('ORDER_NOT_FOUND');
        }

        // Если заказ уже оплачен - выходим
        if ($order['status'] === 'paid') {
            throw new Exception('ORDER_ALREADY_PAID');
        }

        // Обработка разных статусов платежа
        switch ($yookassaPaymentStatus) {
            case 'succeeded':
                // Обновляем статус в бд как оплаченный
                $updateStmt = $connect->prepare("
                    UPDATE orders
                    SET paid_at = NOW(),   
                        status = 'paid',
                        yookassa_payment_id = ?
                    WHERE order_id = ?
                ");
                $updateStmt->bind_param("si", $yookassaPaymentId, $orderId);
                $success = $updateStmt->execute();
                $updateStmt->close();

                if (!$success) {
                    throw new Exception('DATABASE_ERROR');
                }

                logWebhook("SUCCESS: Order $orderId marked as paid");
                break;

            case 'canceled':
            case 'failed':
                // Обновляем статус в бд как отмененный
                $updateStmt = $connect->prepare("
                    UPDATE orders
                    SET cancelled_at = NOW(),   
                        status = 'cancelled',
                        yookassa_payment_id = ?
                    WHERE order_id = ?
                ");
                $updateStmt->bind_param("si", $yookassaPaymentId, $orderId);
                $success = $updateStmt->execute();
                $updateStmt->close();

                if (!$success) {
                    throw new Exception('DATABASE_ERROR');
                }

                logWebhook("SUCCESS: Order $orderId marked as cancelled");
                break;

            case 'pending':
                // Ничего не делаем (платеж создан, но еще не оплачен)
                logWebhook("Payment $yookassaPaymentId is pending for order $orderId");
                break;
            default:
                logWebhook("Unknown payment status: $yookassaPaymentStatus for payment $yookassaPaymentId");
        }

        // При успехе коммитим транзакцию
        $connect->commit();
    } catch (Exception $e) {
        // При ошибке откатываем транзакцию 
        $connect->rollback();
        throw $e; // Пробрасываем выше
    }

} catch (Exception $e) {
    // Сливаем в логи только безопасные ошибки (те что сами создали, отсальные могут содержать секреты)
    $safeErrorCodes = [
        'ORDER_NOT_FOUND',
        'DATABASE_ERROR',
        'INVALID_JSON',
        'ORDER_ALREADY_PAID'
    ];

    $errorCode = $e->getMessage();

    if (in_array($errorCode, $safeErrorCodes)) {
        $error = $errorCode;
    } else {
        // Системная ошибка - общее сообщение
        $error = 'SYSTEM_ERROR';
    }

    // Логирование общих ошибок (в реальном проекте использовать логирование в отдельный файл)
    logWebhook("Webhook error [Order: $orderId]: " . $error);
} finally {
    if (isset($connect)) $connect->close();

    // Всегда возвращаем 200 ОК для юкассы
    http_response_code(200);
    echo 'OK';
}
?>