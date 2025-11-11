<?php
session_start();

// ПРОВЕРКА АВТОРИЗАЦИИ - ЕСЛИ НЕ АВТОРИЗОВАН, ПЕРЕНАПРАВЛЯЕМ НА ГЛАВНУЮ
if (!isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit;
}
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
            <?php 
            require_once __DIR__ . '/header.php'; 

            //тут в нормальной версии нужно обрабатывать ошибку при подключении и далее, и логировать ее как положено вместе с остальными
            try {
                $connect = getDB();
                if (!$connect || $connect->connect_error) {
                    throw new Exception('Database connection failed');
                }
                
                $stmt = $connect->prepare("
                    SELECT 
                        o.order_id, 
                        o.status, 
                        o.total_price, 
                        o.created_at, 
                        o.paid_at,
                        o.delivery_type,
                        s.name as store_name,
                        s.address as store_address, 
                        da.address_line as delivery_address
                    FROM orders o 
                    LEFT JOIN stores s ON o.store_id = s.id
                    LEFT JOIN delivery_addresses da ON o.delivery_address_id = da.id
                    WHERE o.user_id = ? AND o.status != 'cart'
                    ORDER BY o.created_at DESC
                ");
                
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement');
                }
                
                $stmt->bind_param("i", $idUser);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $ordersInfo = [];
                while ($order = $result->fetch_assoc()) {
                    $ordersInfo[] = $order;
                }
            } catch (Exception $e) {
                error_log("My orders error: " . $e->getMessage());
                $ordersInfo = [];
            } finally {
                if (isset($stmt)) $stmt->close();
                if (isset($connect)) $connect->close();
            }
            
            // тут доделать или перенести чтобы статусы можно было отображать 
            // $orderStatus = match($order['status']) {
            //     'cart' => 'корзина',
            //     'paid' => 'оплачен',
            //     'pending_payment' => 'ожидает оплаты',
            //     'refund' => 'возврат',
            // };
            ?>
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
                <div class="cart_in_cart_text">
                    Ваши заказы:
                </div>
                <div class="orders_list">
                    <?php
                    if (empty($ordersInfo)) {
                    ?>
                        У вас еще нет заказов
                    <?php
                    } else {
                        foreach ($ordersInfo as $order) {
                    ?>
                            <div class="order">
                                Заказ <?= '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                <!-- тут показывать соответствующие кнопки действий -->
                                <?php 
                                // Проверяем наличие КОНКРЕТНОГО кода ошибки оплаты
                                if (isset($_SESSION['flash_payment_error'])) { 
                                    // Преобразуем код в текст прямо на месте
                                    $errorText = match($_SESSION['flash_payment_error']) {
                                        'PAYMENT_CANCELED' => 'Оплата отменена. Попробуйте еще раз',
                                        'PAYMENT_FAILED' => 'Оплата не прошла. Попробуйте еще раз или выберите другой способ',
                                        'ORDER_NOT_FOUND' => 'Заказ не найден. Попробуйте создать заказ заново',
                                        'PAYMENT_PENDING' => 'Оплата обрабатывается. Подождите несколько минут',
                                        'EMPTY_USER_PHONE' => 'Заказ не найден. Попробуйте создать заказ заново',
                                        'PAYMENT_STATUS_UNKNOWN' => 'Статус оплаты неизвестен. Подождите или проверьте позже',
                                        'DATABASE_CONNECT_FAILED' => 'Временные технические неполадки. Попробуйте позже',
                                        'DATABASE_OPERATIONS_FAILED ' => 'Ошибка обработки заказа. Попробуйте позже',
                                        default => 'Произошла ошибка при оплате. Пожалуйста, попробуйте оплатить еще раз.'
                                    };
                                ?>
                                    <div class="error_pay_no_address open" id="flash-payment-error">
                                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                                        <?= htmlspecialchars($errorText) ?>
                                    </div>
                                <?php 
                                    // Удаляем ошибку после показа
                                    unset($_SESSION['flash_payment_error']);
                                } 
                                ?>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>