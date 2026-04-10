<?php
declare(strict_types=1);

// Контроллер страницы заказа

$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;

if ($orderId <= 0) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit();
}

$title  = "Заказ № $orderId - Gym Boss";
$robots = 'noindex, nofollow';
$pageModuleScripts = ['/assets/js/orders/order.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . "/../templates/pages/order.php";
$content = ob_get_clean();

// Подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
