<?php
//проверить потом, правильные ли методы и функции из девкида 
function checkYooKassaStatus($orderId) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/envLoader.php';
        
        $yookassa = new \YooKassa\Client();
        $yookassa->setAuth(getenv('YOOKASSA_SHOP_ID'), getenv('YOOKASSA_API_KEY'));
        
        // Ищем платежи за последние 24 часа
        $payments = $yookassa->getPayments([
            'limit' => 10,
            'created_at' => date('Y-m-d\TH:i:s\Z', strtotime('-24 hours'))
        ]);
        
        foreach ($payments->getItems() as $payment) {
            $metadata = $payment->getMetadata();
            if (isset($metadata['orderId']) && $metadata['orderId'] == $orderId) {
                return $payment->getStatus(); // 'succeeded', 'pending', 'canceled'
            }
        }
        
        return 'not_found'; // Платеж не найден
        
    } catch (Exception $e) {
        error_log("YooKassa API error: " . $e->getMessage());
        return 'api_error';
    }
}
?>