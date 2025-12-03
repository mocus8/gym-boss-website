<?php
// тут помимо прочих исправлений нужны обработки ошибок юкассы, еще номер телефона проверять валидировать и т д
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;

if (!$userId || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'INVALID_REQUEST']);
    exit();
}

require_once __DIR__ . '/src/getOrderData.php';

try {
    if (empty($orderItems)) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    $connect = getDB();
    if (!$connect) {
        throw new Exception('DATABASE_CONNECT_FAILED');
    }

    $connect->begin_transaction();

    // Проверяем есть ли активный платеж
    $checkStmt = $connect->prepare("SELECT yookassa_payment_id, payment_expires_at 
        FROM orders WHERE order_id = ? 
        AND status = 'pending_payment'
        ");
    $checkStmt->bind_param("i", $orderId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $existing = $result->fetch_assoc();
    $checkStmt->close();

    if (!empty($existing['yookassa_payment_id'])) {
        // Проверяем не истек ли платеж
        if (strtotime($existing['payment_expires_at']) > time()) {
            // Проверяем статус в ЮКассе
            require_once __DIR__ . '/checkYooKassaStatus.php';
            $status = checkYooKassaStatus($existing['yookassa_payment_id']);
            
            if ($status === 'pending') {
                // Возвращаем существующую ссылку
                $paymentInfo = $yookassa->getPaymentInfo($existing['yookassa_payment_id']);
                echo json_encode([
                    'confirmation_url' => $paymentInfo->getConfirmation()->getConfirmationUrl()
                ]);
                $connect->close();
                exit;
            } else if ($status === 'succeeded') {
                // Платеж уже оплачен (вебхук мог не дойти)
                $updateStmt = $connect->prepare("UPDATE orders SET status = 'paid', paid_at = NOW() WHERE order_id = ?");
                $updateStmt->bind_param("i", $orderId);
                $updateStmt->execute();

                // эту ошибку отрабатывать на фронте
                echo json_encode(['error' => 'ORDER_ALREADY_PAID']);
                $connect->close();
                exit;
            }
        }
    }

    $stmt = $connect->prepare("
        SELECT o.order_id, o.total_price, o.delivery_type, o.delivery_cost, o.status, o.created_at,
        u.login,
        (SELECT COUNT(*) 
        FROM orders o2 
        WHERE o2.user_id = o.user_id 
        AND o2.status = 'pending_payment'
        AND o2.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ) as user_recent_orders
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ? AND o.user_id = ?
    AND u.login IS NOT NULL
    AND EXISTS (SELECT 1 FROM product_order po WHERE po.order_id = o.order_id)
    HAVING user_recent_orders < 10
    FOR UPDATE
    ");
    if (!$stmt) {
        throw new Exception('DATABASE_OPERATIONS_FAILED');
    }
    
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    if ($order['status'] === 'paid') {
        throw new Exception('ORDER_ALREADY_PAID');
    }

    if (empty($order['login'])) {
        throw new Exception('EMPTY_USER_PHONE');
    }

    //создаём массив товаров в нужном для чека формате
    $items = [];
    foreach ($orderItems as $item) {
        $price = number_format($item['price'], 2, '.', '');

        $items[] = [
            'description' => $item['name'],
            'quantity' => (string)$item['amount'],
            'amount' => [
                'value' => $price,
                'currency' => 'RUB'
            ],
            'vat_code' => $item['vat_code'],
            'payment_mode' => 'full_payment',
            'payment_subject' => 'commodity'
        ];
    }

    // добавляем стоимость доставки если она есть
    $deliveryCost = number_format((float)$order['delivery_cost'], 2, '.', '');
    $deliveryType = $order['delivery_type'];

    if ($deliveryCost > 0 && $deliveryType == 'delivery') {
        $items[] = [
            'description' => 'Доставка',
            'quantity' => 1,
            'amount' => [
                'value' => number_format($deliveryCost, 2, '.', ''),
                'currency' => 'RUB'
            ],
            'vat_code' => 4, // НДС 20%
            'payment_mode' => 'full_payment',
            'payment_subject' => 'service' // Доставка это услуга, не товар
        ];
    }

    $receiptTotal = 0;
    foreach ($items as $item) {
        $receiptTotal += $item['amount']['value'] * $item['quantity'];
    }

    if (abs($receiptTotal - (float)$order['total_price']) > 0.01) {
        throw new Exception('RECEIPT_TOTAL_MISMATCH');
    }

    $yookassa = new \YooKassa\Client();
    $yookassa->setAuth(getenv('YOOKASSA_SHOP_ID'), getenv('YOOKASSA_API_KEY'));

    try {
        $idempotenceKey = 'order_' . $orderId . '_' . time();
        
        $payment = $yookassa->createPayment([
            'amount' => [
                'value' => number_format($order['total_price'], 2, '.', ''),
                'currency' => 'RUB'
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => 'https://cw187549.tw1.ru/order_success.php?orderId=' . $orderId
            ],
            'capture' => true,
            'description' => 'Заказ №' . $orderId,
            'metadata' => ['orderId' => $orderId],

            'receipt' => [
                'customer' => [
                    'phone' => $order['login']
                ],
                'items' => $items
            ]
        ], $idempotenceKey);
    } catch (\YooKassa\Common\Exceptions\ApiException $e) {
        error_log("YooKassa API error: " . $e->getMessage());
        throw new Exception('PAYMENT_SYSTEM_ERROR');
    } catch (\YooKassa\Common\Exceptions\BadApiRequestException $e) {
        error_log("YooKassa bad request: " . $e->getMessage());
        throw new Exception('INVALID_PAYMENT_DATA');
    }
    $expiresAt = date('Y-m-d H:i:s', time() + 1800); // устаревание оплаты через 30 минут

    $updateStmt = $connect->prepare("UPDATE orders SET 
        yookassa_payment_id = ?, 
        payment_expires_at = ?, 
        status = 'pending_payment' 
        WHERE order_id = ?");
    $updateStmt->bind_param("ssi", $payment->getId(), $expiresAt, $orderId);
    $updateStmt->execute();
    $updateStmt->close();

    $connect->commit();

    echo json_encode([
        'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    
    if (isset($connect)) {
        $connect->rollback();
    }
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}
?>