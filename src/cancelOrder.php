<?php
// тут нехватает crsf токена, и мб еще что то надо доделать

header('Content-Type: application/json');

require_once __DIR__ . '/bootstrap.php';

// Разрешаем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'METHOD_NOT_ALLOWED']);
    exit();
}

$orderId = $_POST["order_id"] ?? '';
$userId = $_SESSION['user']['id'] ?? null;

if (!$userId || !$orderId) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'INVALID_REQUEST']);
    exit();
}

if (!is_numeric($orderId) || $orderId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'INVALID_ORDER_ID']);
    exit();
}

try {
    $stmt = $db->prepare("
        UPDATE orders 
        SET status = 'cancelled', 
            cancelled_at = NOW()
        WHERE order_id = ?
        AND user_id = ? 
        AND status = 'pending_payment'
    ");

    if (!$stmt) {
        http_response_code(500); // Internal Server Error (Внутренняя ошибка сервера)
        throw new Exception('DATABASE_ERROR');
    }
    
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $affectedRows = $stmt->affected_rows; // Сохраняем результат
    $stmt->close();
    
    if ($affectedRows === 0) {
        http_response_code(409); // Conflict
        throw new Exception('ORDER_CANNOT_BE_CANCELLED');
    }

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // сливаем в логи только безопасные ошибки (те что сами создали, отсальные могут содержать секреты)
    $safeErrorCodes = [
        'DATABASE_ERROR',
        'ORDER_CANNOT_BE_CANCELLED'
    ];

    $errorCode = $e->getMessage();

    if (in_array($errorCode, $safeErrorCodes)) {
        $error = $errorCode;
    } else {
        // Системная ошибка - общее сообщение
        $error = 'CANCEL_ORDER_ERROR';
    }

    // Логируем с маскировкой телефона (если почта то маскируем ее и т д), потом логировать правильно
    $logMessage = preg_replace('/\+7\d{10}/', '[PHONE_MASKED]', $e->getMessage());
    error_log("Payment error [Order: $orderId]: " . $logMessage);

    // Уже установлен http_response_code выше в отдельных проверках
    // Если не установлен, ставим 500
    if (http_response_code() === 200 || http_response_code() === false) {
        http_response_code(500);
    }

    echo json_encode(['error' => $error]);

}
?>