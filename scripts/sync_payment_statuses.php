<?php
declare(strict_types=1);

// Синхронизация pending-заказов 

require_once __DIR__ . '/../bootstrap/app.php';

try {
    // Получаем все pending-заказы
    $pendingOrders = $orderRepository->findPendingOrders();
    if ($pendingOrders === []) {
        $logger->info('Sync payment statuses script completed, no pending orders found');

        exit(0);
    }

    foreach ($pendingOrders as $orderId) {
        $paymentStatusSyncService->syncByOrderId($orderId);
    }

    $logger->info('Sync payment statuses script completed, statuses was sync for {processed_count} orders', [
        'processed_count' => count($pendingOrders)
    ]);
} catch (\Throwable $e) {
    $logger->error('Sync payment statuses script failed', [
        'error' => $e
    ]);
}