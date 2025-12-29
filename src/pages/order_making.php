<?php
// Контроллер страницы оформления заказа

// Если нечего нет в корзине в момент оформления - перекидываем на главную
require_once __DIR__ . '/../getCartInfo.php';
if ($cartCount === 0) {
    header('Location: /');
    exit;
}

$title  = 'Оформление заказа - Gym Boss';
$robots = 'noindex,nofollow';
$pageScripts = [
    '/js/maps.js',
    '/js/order-making.js'
];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/order_making.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
