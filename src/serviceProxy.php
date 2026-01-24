<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

// Получаем ключ из конфига
$dadataKey = $servicesConfig['dadata']['api_key'] ?? null;

if ($dadataKey === null) {
    echo json_encode([
        'status' => 'false'
    ]);
}


// Отдаем ключ клиенту
echo json_encode([
    'key' => $dadataKey,
    'status' => 'success'
]);
?>