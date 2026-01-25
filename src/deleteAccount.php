<?php

require_once __DIR__ . '/bootstrap.php';

if (!$userId) {
    header("Location: /");
    exit;
}

try {
    // Начинаем транзакцию для безопасности (либо все выполняются либо ни один запросы)
    $db->begin_transaction();
    
    // Удаляем пользователя
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Подтверждаем все изменения
    $db->commit();
    
    // Чистим сессию
    $_SESSION = [];
    session_destroy();

    header("Location: /");

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