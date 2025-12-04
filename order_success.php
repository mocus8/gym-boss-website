<?php
session_start();

require_once __DIR__ . '/src/helpers.php';

$userId = $_SESSION['user']['id'] ?? null;
$orderId = $_GET['orderId'] ?? '';
$paidAt = null;

if (!$userId) {
    header('Location: index.php');
    exit();
}

if (!$orderId) {
    header('Location: my_orders.php');
    exit();
}

try {
    $connect = getDB();
    if (!$connect || $connect->connect_error) {
        throw new Exception('DATABASE_CONNECT_FAILED');
    }

    // получаем из бд данные о заказе (id заказа, статус и время оплаты)
    $stmt = $connect->prepare("SELECT order_id, delivery_cost, status, paid_at, yookassa_payment_id, payment_expires_at FROM orders WHERE order_id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception('DATABASE_QUERY_FAILED');
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

    if ($order['status'] !== 'paid' && $paymentExpiresAt && strtotime($paymentExpiresAt) < time()) {
        throw new Exception('PAYMENT_TIME_EXPIRED');
    }

    // Заказ есть, но не оплачен
    if ($order['status'] !== 'paid') { 
        if (!$yookassaPaymentId) {
            throw new Exception('PAYMENT_NOT_CREATED');
        }

        try {
            require_once __DIR__ . '/vendor/autoload.php';
            require_once __DIR__ . '/src/envLoader.php';
            
            $yookassa = new \YooKassa\Client();
            $yookassa->setAuth(getenv('YOOKASSA_SHOP_ID'), getenv('YOOKASSA_API_KEY'));
            
            $yookassaPaymentStatus = $yookassa->getPaymentInfo($yookassaPaymentId)->getStatus();
            
        } catch (\YooKassa\Common\Exceptions\NotFoundException $e) {
            throw new Exception('PAYMENT_NOT_FOUND');

        } catch (Exception $e) {
            error_log("YooKassa API error: " . $e->getMessage());
            throw new Exception('PAYMENT_SYSTEM_ERROR');
        }

        switch ($yookassaPaymentStatus) {
            case 'succeeded':
                // Деньги списались, но вебхук не сработал - обновляем статус в БД
                $updateStmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), status = 'paid' WHERE order_id = ?");
                $paidAt = date('Y-m-d H:i:s');
                if (!$updateStmt) {
                    throw new Exception('DATABASE_QUERY_FAILED');
                }
                $updateStmt->bind_param("i", $orderId);
                if (!$updateStmt->execute()) {
                    $updateStmt->close();
                    throw new Exception('DATABASE_QUERY_FAILED');
                }
                $updateStmt->close();
                $order['status'] = 'paid'; // Обновляем локально
                break;
                
            case 'pending':
                // Ожидание оплаты
                throw new Exception('PAYMENT_PENDING');
                
            case 'canceled':
                // Оплата отменена
                throw new Exception('PAYMENT_CANCELED');
                
            case 'failed':
                // Ошибка оплаты
                throw new Exception('PAYMENT_FAILED');
                
            default:
                // Неизвестный статус или ошибка API
                throw new Exception('PAYMENT_STATUS_UNKNOWN');
        }
    }
} catch (Exception $e) {
    $_SESSION['flash_payment_error'][$orderId] = $e->getMessage();
    header('Location: my_orders.php');
    exit();

} finally {
    // Закрываем все соединения в finally (выполнится в любом случае)
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}

require_once __DIR__ . '/src/getOrderData.php';
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>
            Интернет-магазин "Boss Of This Gym"
		</title>
		<link rel="stylesheet" href="styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <!-- <div class="loading-text">Загрузка...</div> -->
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/header.php'; ?>
            <main class="main">
                <div class="button_return_position">
                    <a href="index.php">
                        <div class="button_return">
                            <div class="button_return_text">
                                На главную
                            </div>
                            <img class="button_return_img" src="img/arrow_back.png">
                        </div>
                    </a>
                </div>
                <div class="order_success_back">
                    <div class="order_success_title">
                        Заказ успешно оформлен и оплачен!
                    </div>
                    <div class="order_success_row">
                        Номер заказа: <?= '#' . str_pad($orderId, 6, '0', STR_PAD_LEFT) ?>
                    </div>
                    <div class="order_right_row_gap"></div>
                    <?php
                    foreach ($orderItems as $item) { 
                    ?>   
                        <div class="order_right_products_row">
                            <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= $item['price'] * $item['amount'] ?> ₽
                        </div>
                    <?php
                        }
                    ?>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_row">
                        Итоговая стоимость: <?= $orderTotalPrice + $deliveryCost?> ₽
                    </div>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_row">
                        Способ доставки: <?= $orderDetails['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
                    </div>
                    <?php
                    if ($orderDetails['delivery_type'] === 'pickup' && $orderDetails['store_name']) { 
                    ?>  
                        <div class="order_success_row">
                            Пункт выдачи: <?= htmlspecialchars($orderDetails['store_name']) ?>, <?= htmlspecialchars($orderDetails['store_address']) ?>
                        </div>
                        <div class="order_success_row">
                            Заказ может быть готов к получению с <?= date('H:i', strtotime($paidAt . ' +1 hour')) ?> до <?= date('H:i', strtotime($paidAt . ' +3 hour')) ?>, для уточнения звоните менеджеру 
                            <a href='tel: <?= htmlspecialchars($orderDetails['phone']) ?>' class="colour_href">
                                <?= htmlspecialchars($orderDetails['phone']) ?>
                            </a>
                        </div>
                    <?php
                    } else if ($orderDetails['delivery_type'] === 'delivery' && $orderDetails['delivery_address']) { 
                    ?>
                        <div class="order_success_row">
                            Стоимость доставки: <?= (int)$deliveryCost ?> ₽
                        </div>
                        <div class="order_success_row">
                            Адрес доставки: <?= htmlspecialchars($orderDetails['delivery_address']) ?>
                        </div>
                        <div class="order_success_row">
                            Ориентировочная дата доставки: с <?= date('d.m.Y', strtotime($paidAt . ' +1 day')) ?> до <?= date('d.m.Y', strtotime($paidAt . ' +2 days')) ?>
                        </div>
                        <div class="order_success_row">
                            Курьер свяжеться с вами по телефону
                        </div>
                    <?php
                    } 
                    ?>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_row">
                        Письмо с чеком будет отправлено на вашу почту
                    </div>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_title">
                        Спасибо за ваш заказ!
                    </div>
                    <div class="order_success_actions">
                        <a href="my_orders.php" class="order_success_actions_button">История заказов</a>
                        <a href="index.php" class="order_success_actions_button">Продолжить покупки</a>
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
	</body>
</html>