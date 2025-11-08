<?php
// Подключаем загрузчик .env
require_once __DIR__ . '/envLoader.php';

header('Content-Type: application/json');

// Получаем ключ из .env
$dadataKey = getenv('DADATA_API_KEY');

// Отдаем ключ клиенту
echo json_encode([
    'key' => $dadataKey,
    'status' => 'success'
]);
?>