<?php
declare(strict_types=1);

# Скрипт для очистки попыток входа старше 30 дней

require_once __DIR__ . '/../bootstrap/app.php';

try {
    $stmt = $db->prepare("
        DELETE FROM login_attempts 
        WHERE attempted_at < NOW() - INTERVAL 30 DAY
    ");

    if (!$stmt) {
        throw new \RuntimeException('Clean login attempts script query prepare failed: ' . $db->error);
    }

    if (!$stmt->execute()) {
        throw new \RuntimeException('Clean login attempts script query execute failed: ' . $stmt->error);
    }

    $logger->info('Clean login attempts script completed, {deleted_attempts} attempts deleted', [
        'deleted_attempts' => $stmt->affected_rows
    ]);

} catch (\Throwable $e) {
    $logger->error('Clean login attempts script failed', [
        'db_error' => $e->getMessage()
    ]);

} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}