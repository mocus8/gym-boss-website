<?php
require_once __DIR__ . '/src/bootstrap.php';

use YooKassa\Model\Notification\NotificationFactory;

// Функция логирования вебхука
// Логироание потом тут полностью заменить (нормальное через redis или laravel, правильные коды)
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
    $orderId = null;

    $source = file_get_contents('php://input');
    $requestBody = json_decode($source, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid incoming JSON');
    }

    logWebhook('Event received: ' . ($requestBody['event'] ?? 'unknown'));

    // Создаем объект уведомления через фабрику (встроенный в SDK юкассы класс)
    $factory = new NotificationFactory();
    $notification = $factory->factory($requestBody);
    $payment = $notification->getObject();
    
    $yookassaPaymentId = $payment->getId();
    $yookassaPaymentStatus = $payment->getStatus();
    $metadata = $payment->getMetadata();
    $orderId = isset($metadata['orderId']) ? (int)$metadata['orderId'] : null;
    
    logWebhook("Payment ID: $yookassaPaymentId, Status: $yookassaPaymentStatus, Order: " . ($orderId ?? 'unknown'));

    // Если нет orderId в metadata - выходим
    if (!$orderId) {
        throw new Exception('Order not found');
    }

    // Обработка разных статусов платежа от юкассы
    switch ($yookassaPaymentStatus) {
        case 'succeeded':
            // Обновляем статус в бд как оплаченный
            $orderService->markPaid($orderId, $yookassaPaymentId);

            logWebhook("SUCCESS [Order: $orderId]: Marked as paid");
            break;

        case 'canceled':
        case 'failed':
            // Обновляем статус в бд как отмененный
            $orderService->markCancelFromPaymentProvider($orderId, $yookassaPaymentId);

            logWebhook("SUCCESS [Order: $orderId]: Marked as cancelled");
            break;

        case 'pending':
            // Ничего не делаем (платеж создан, но еще не оплачен)
            logWebhook("Payment $yookassaPaymentId is pending for order $orderId");
            break;
        default:
            logWebhook("Unknown payment status: $yookassaPaymentStatus for payment $yookassaPaymentId");
    }

} catch (Exception $e) {
    // Сливаем в логи только безопасные ошибки (те что сами создали, остальные могут содержать секреты)
    $safeError = [
        'Invalid incoming JSON',
        'Invalid orderId',
        'Empty yookassaPaymentId',
        'Order not found',
        'Order already paid',
        'Order status is not pending_payment',
        'Order cannot be cancelled from current status'
    ];

    $error = $e->getMessage();

    if (in_array($error, $safeError)) {
        $errorLog = $error;
    } else {
        // Системная ошибка - общее сообщение
        $errorLog = 'System error';
    }

    // Логирование общих ошибок (в реальном проекте использовать логирование в отдельный файл)
    $orderIdForLog = isset($orderId) ? $orderId : 'unknown';
    logWebhook("ERROR [Order: $orderIdForLog]: " . $errorLog);

} finally {
    // Всегда возвращаем 200 ОК для юкассы
    http_response_code(200);
    echo 'OK';
}