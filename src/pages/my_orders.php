<?php

// Получаем инфу о заказах пользователя
try {
    $stmt = $db->prepare("
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

    $userId = $_SESSION['user']['id'];
    $stmt->bind_param("i", $userId);
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
}

$title  = 'Мои заказы - Gym Boss';
$robots = 'noindex,nofollow';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/my_orders.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
