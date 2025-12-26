<?php
// Если пользователь не авторизирован то перекидываем на главную
if (!isset($_SESSION['user']['id'])) {
    header('Location: /');
    exit;
}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="robots" content="noindex, nofollow">
		<title>
            Интернет-магазин "Boss Of This Gym"
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
            require_once __DIR__ . '/header.php'; 

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
                // потом нормально логировать
                error_log("My orders error: " . $e->getMessage());
                $ordersInfo = [];
            } finally {
                if (isset($stmt)) $stmt->close();
                if (isset($connect)) $connect->close();
            }
            ?>
            <main class="main">
                <div class="button_return_position">
                    <a href="/">
                        <div class="button_return">
                            <div class="button_return_text">
                                На главную
                            </div>
                            <img class="button_return_img" src="/img/arrow_back.png">
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
                        <div class="cart_empty">
                            У вас еще нет заказов
                        </div>
                    <?php
                    } else {
                        foreach ($ordersInfo as $order) {
                    ?>
                            <div class="order" data-order-id="<?= $order['order_id'] ?>">
                                <div class="order_number">
                                    Заказ <?= '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?> (оформлен <?= date('d.m.Y', strtotime($order['created_at'])) ?>)
                                </div>

                                <div class="order_data">
                                    Стоимость: <?= $order['total_price'] ?> ₽
                                </div>

                                <div class="order_data">
                                    Способ получения: <br>
                                    <?= $order['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
                                </div>
                                
                                <?php
                                if ($order['delivery_type'] === 'pickup' && $order['store_address']) { 
                                ?>  
                                    <div class="order_data_address">
                                        Пункт выдачи: <?= $order['store_address'] ?>
                                    </div>
                                <?php
                                } else if ($order['delivery_type'] === 'delivery' && $order['delivery_address']) { 
                                ?>
                                    <div class="order_data_address">
                                        Адрес доставки: <?= $order['delivery_address'] ?>
                                    </div>
                                <?php
                                } 
                                ?>

                                <div class="order_data" data-field="status">
                                    Статус заказа: <br>
                                    <?= match($order['status']) {
                                        'cart' => 'корзина',
                                        'paid' => 'оплачен', 
                                        'pending_payment' => 'ожидает оплаты',
                                        'cancelled' => 'отменён',
                                        'refund' => 'возврат',
                                        default => $order['status']
                                    } ?> 
                                </div>

                                <a href="/order/<?= htmlspecialchars($order['order_id']) ?>">
                                    <div class="order_button">
                                        Перейти к заказу
                                    </div>
                                </a>
                            </div>
                        <?php
                        }
                        ?>
                    <?php
                    }
                    ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
	</body>
</html>