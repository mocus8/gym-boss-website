<?php
declare(strict_types=1);

# Скрипт для очистки карт неавторизированных пользователей
# Удаляет неконвертированные корзины, которые не изменялись 14 дней

require_once __DIR__ . '/../bootstrap/app.php';

try {
    $stmt = $db->prepare("
        DELETE FROM carts 
        WHERE is_converted = 0
            AND user_id is NULL 
            AND updated_at < NOW() - INTERVAL 14 DAY
    ");

    if (!$stmt) {
        throw new \RuntimeException('Clean carts script query prepare failed: ' . $db->error);
    }

    if (!$stmt->execute()) {
        throw new \RuntimeException('Clean carts script query execute failed: ' . $stmt->error);
    }

    $logger->info('Clean carts script completed, {deleted_rows} deleted rows', [
        'deleted_rows' => $stmt->affected_rows
    ]);

} catch (\Throwable $e) {
    $logger->error('Clean carts script DB error', [
        'db_error'   => $e->getMessage()
    ]);

} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}