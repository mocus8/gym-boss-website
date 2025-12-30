<?php

require_once __DIR__ . '/bootstrap.php';

$idUser = $_SESSION['user']['id'] ?? '';

if (!$idUser) {
    header("Location: /");
    exit;
}

try {
    // Начинаем транзакцию для безопасности (либо все выполняются либо ни один запросы)
    $db->begin_transaction();
    
    // Удаляем товары в заказах пользователя
    $stmt = $db->prepare("
        DELETE po FROM product_order po 
        INNER JOIN orders o ON po.order_id = o.order_id 
        WHERE o.user_id = ?
    ");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Удаляем заказы пользователя
    $stmt = $db->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();

    // Удаляем адреса доставки пользователя
    $stmt = $db->prepare("DELETE FROM delivery_addresses WHERE user_id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Удаляем самого пользователя
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Подтверждаем все изменения
    $db->commit();
    
    header("Location: /src/logout.php");
    exit;
    
} catch (Exception $e) {
    // Откатываем изменения в случае ошибки
    if (isset($db)) {
        $db->rollback();
    }
    // Логируем ошибку и показываем сообщение пользователю
    error_log("Error deleting account: " . $e->getMessage());
    $_SESSION['error'] = "Произошла ошибка при удалении аккаунта";
    header("Location: /");
    exit;
}