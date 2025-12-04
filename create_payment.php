<?php
// тут помимо прочих исправлений нужно номер телефона проверять валидировать и т д
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/envLoader.php';
require_once __DIR__ . '/vendor/autoload.php';

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

    // ставим ожидание блокировок как 5 секунд, потом ошибка от sql
    $connect->query("SET SESSION innodb_lock_wait_timeout = 5");

    // начинаем транзакцию (либо все либо ничего для sql)
    $connect->begin_transaction();

    $yookassa = new \YooKassa\Client();
    $yookassa->setAuth(getenv('YOOKASSA_SHOP_ID'), getenv('YOOKASSA_API_KEY'));

    // получаем всю инфу о заказе и блокируем на время выполнения через FOR UPDATE на случай одновременно оплаты
    $stmt = $connect->prepare("
        SELECT o.order_id, o.total_price, o.delivery_type, o.delivery_cost, 
               o.status, o.yookassa_payment_id, o.payment_expires_at,
               u.login
        FROM orders o
        INNER JOIN users u ON o.user_id = u.id
        WHERE o.order_id = ? AND o.user_id = ?
        FOR UPDATE
    ");
    
    if (!$stmt) {
        throw new Exception('DATABASE_QUERY_FAILED');
    }
    
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    if ($order['status'] === 'paid') {
        throw new Exception('ORDER_ALREADY_PAID');
    }

    // проверяем телефон (почту в будующем) для отправки чеков (важно, поэтому отдельно проверяем)
    if (empty($order['login'])) {
        throw new Exception('EMPTY_USER_PHONE');
    }

    // Проверяем есть ли активный платеж, не истек ли платеж
    if (!empty($order['yookassa_payment_id']) &&
        !empty($order['payment_expires_at']) && 
        strtotime($order['payment_expires_at']) > time()) {

        // Проверяем статус в ЮКассе        
        try {
            $yookassaExistingPayment = $yookassa->getPaymentInfo($order['yookassa_payment_id']);

            if ($yookassaExistingPayment->getStatus() === 'pending') {
                $connect->commit();
                $connect->close();

                // Возвращаем существующую ссылку
                echo json_encode([
                    'confirmation_url' => $yookassaExistingPayment->getConfirmation()->getConfirmationUrl()
                ]);

                exit;

            } else if ($yookassaExistingPayment->getStatus() === 'succeeded') {
                // Платеж уже оплачен (вебхук мог не дойти)
                $updateStmt = $connect->prepare("
                    UPDATE orders 
                    SET status = 'paid', 
                        paid_at = COALESCE(paid_at, NOW())
                    WHERE order_id = ?
                ");
                $updateStmt->bind_param("i", $orderId);
                $updateStmt->execute();
                $updateStmt->close();

                $connect->commit();
                $connect->close();

                // эту ошибку отрабатывать на фронте
                echo json_encode(['error' => 'ORDER_ALREADY_PAID']);

                exit;
            }
        } catch (Exception $e) {
            // Платеж не найден - продолжаем
        }
    }

    //создаём массив товаров в нужном для чека формате, для финалки проверять и валидировать НДС
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

    // проверяем итоговую стоимость (должна сходиться с чеком)
    $receiptTotal = 0;
    foreach ($items as $item) {
        $receiptTotal += $item['amount']['value'] * $item['quantity'];
    }

    if (abs($receiptTotal - (float)$order['total_price']) > 0.01) {
        throw new Exception('RECEIPT_TOTAL_MISMATCH');
    }

    // Фиксированный idempotenceKey для защиты от дублей
    $dataHash = md5($orderId . $order['total_price']);
    $idempotenceKey = 'order_' . $orderId . '_' . $dataHash;

    // создаем платеж в юкассе
    try {
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

    // устанавливаем в бд устаревание оплаты через 30 минут
    $expiresAt = date('Y-m-d H:i:s', time() + 1800);

    $updateStmt = $connect->prepare("
        UPDATE orders
        SET yookassa_payment_id = ?, 
            payment_expires_at = ?, 
            status = 'pending_payment' 
        WHERE order_id = ?
    ");
    $updateStmt->bind_param("ssi", $payment->getId(), $expiresAt, $orderId);
    $updateStmt->execute();
    $updateStmt->close();

    $connect->commit();

    // возвращаем ссылку для оплаты с нужными данными (создала юкасса)
    echo json_encode([
        'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()
    ]);

// ловим таймаут по блокировке в бд (если FOR UPDATE сработал)
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1205) {
        echo json_encode(['error' => 'PAYMENT_IN_PROGRESS']);
    } else {
        error_log("MySQL error [Order: $orderId]: " . $e->getMessage());
        echo json_encode(['error' => 'DATABASE_ERROR']);
    }
    
    if (isset($connect)) {
        try {
            $connect->rollback();
            $connect->close();
        } catch (Exception $rollbackError) {
            // Игнорируем ошибки при откате
        }
    }
    
// ловим общие ошибки
} catch (Exception $e) {
    error_log("Payment error [Order: $orderId, User: $userId]: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    
    if (isset($connect)) {
        try {
            $connect->rollback();
            $connect->close();
        } catch (Exception $rollbackError) {
            // Игнорируем ошибки при откате
        }
    }

} finally {
    if (isset($stmt) && $stmt) $stmt->close();
    // по thread_id проверяем что соединение активно
    if (isset($connect) && $connect->thread_id) {
        $connect->close();
    }
}
?>