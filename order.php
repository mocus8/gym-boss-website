<?php
// Тут зарефакторить и новые коды ошибок добавить в utils

$userId = $_SESSION['user']['id'] ?? null;
$orderId = $_GET['orderId'] ?? '';
$paidAt = null;

if (!$userId) {
    header('Location: /');
    exit();
}

if (!$orderId) {
    require __DIR__ . '/404.php';
    exit();
}

// Определяем дефолтный шаблон для страницы и ошибку для показа
$template = 'error.php';
$error_message = '';

try {
    $connect = getDB();
    if (!$connect || $connect->connect_error) {
        throw new Exception('DATABASE_ERROR');
    }

    // получаем из бд данные о заказе (id заказа, статус и время оплаты)
    $stmt = $connect->prepare("
        SELECT 
            order_id, 
            delivery_cost, 
            status, 
            paid_at, 
            yookassa_payment_id, 
            payment_expires_at 
        FROM orders 
        WHERE order_id = ? AND user_id = ?
    ");
    if (!$stmt) {
        throw new Exception('DATABASE_ERROR');
    }

    // Привязываем параметры
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    // Заказ вообще не существует
    if (!$order) {
        throw new Exception('ORDER_NOT_FOUND');
    }
    
    $yookassaPaymentId = $order['yookassa_payment_id'];
    $paymentExpiresAt = $order['payment_expires_at'];
    $paidAt = $order['paid_at'];
    $deliveryCost = $order['delivery_cost'];


    // if ($order['status'] !== 'paid' && $paymentExpiresAt && strtotime($paymentExpiresAt) < time()) {
    //     throw new Exception('ORDER_EXPIRED');
    // }

    // Заказ есть, но не оплачен
    if ($order['status'] === 'pending_payment') { 
        if (!$yookassaPaymentId) {
            throw new Exception('PAYMENT_NOT_CREATED');
        }

        try {
            $yookassa = new \YooKassa\Client();
            $yookassa->setAuth(getenv('YOOKASSA_SHOP_ID'), getenv('YOOKASSA_API_KEY'));
            
            $yookassaPaymentStatus = $yookassa->getPaymentInfo($yookassaPaymentId)->getStatus();
            
        } catch (\YooKassa\Common\Exceptions\NotFoundException $e) {
            throw new Exception('ORDER_NOT_FOUND');

        } catch (Exception $e) {
            // потом нормально логировать
            error_log("YooKassa API error: " . $e->getMessage());
            throw new Exception('PAYMENT_SYSTEM_ERROR');
        }

        switch ($yookassaPaymentStatus) {
            case 'pending':
                // Ожидание оплаты, ничего не меняем
                break;

            case 'succeeded':
                // Деньги списались, но вебхук не сработал - обновляем статус в БД
                $updateStmt = $connect->prepare("
                    UPDATE orders 
                    SET paid_at = NOW(), status = 'paid' 
                    WHERE order_id = ?
                ");
                if (!$updateStmt) {
                    throw new Exception('DATABASE_ERROR');
                }

                $updateStmt->bind_param("i", $orderId);
                if (!$updateStmt->execute()) {
                    $updateStmt->close();
                    throw new Exception('DATABASE_ERROR');
                }

                $updateStmt->close();

                // Обновляем локально и сохраняем время для рассчёта срока готовности заказа
                $order['status'] = 'paid';
                $paidAt = date('Y-m-d H:i:s');

                break;
                
            case 'canceled':
            case 'failed':
                // Платеж отменен или неудался - меняем статус в бд на отмененный
                $updateStmt = $connect->prepare("
                    UPDATE orders 
                    SET cancelled_at = NOW(), status = 'cancelled' 
                    WHERE order_id = ?
                ");
                if (!$updateStmt) {
                    throw new Exception('DATABASE_ERROR');
                }

                $updateStmt->bind_param("i", $orderId);
                if (!$updateStmt->execute()) {
                    $updateStmt->close();
                    throw new Exception('DATABASE_ERROR');
                }

                $updateStmt->close();

                // Обновляем локально
                $order['status'] = 'cancelled';

                break;
                
            default:
                // Неизвестный статус или ошибка API
                throw new Exception('PAYMENT_SYSTEM_ERROR');
        }
    } 

    // Выбор шаблона статусу в бд (откорректирован юкассой выше)
    switch ($order['status']) {
        case 'paid':
            $template = 'status_success.php';
            break;
        case 'pending_payment':
            $template = 'status_pending.php';
            break;
        case 'cancelled':
            $template = 'status_cancelled.php';
            break;
        default:
            $template = 'error.php';
    }

} catch (Exception $e) {
    $template = 'error.php';
    $error_message = $e->getMessage();

    // Потом нормально логировать
    error_log("[Order] Order page error: " . $e->getMessage() . " Order id: " . $orderId . " User id: " . $userId);

} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}

// Тут потом поменять на класс/норм функцию
require_once __DIR__ . '/src/getOrderData.php';
?>

<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8">
        <meta name="robots" content="noindex, nofollow">
		<title>
            Gym Boss - спорттовары
		</title>
        <link rel="icon" href="/public/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <!-- <div class="loading-text">Загрузка...</div> -->
        </div>

        <div class="desktop">
            <?php
            // Подключаем нужный шаблон
            include "src/templates/order/{$template}";
            ?>
        </div>

        <script type="module" src="/js/order.js"></script>
	</body>
</html>