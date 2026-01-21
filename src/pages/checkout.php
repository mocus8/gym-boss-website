<?php
// Контроллер страницы оформления заказа

$title  = 'Оформление заказа - Gym Boss';
$robots = 'noindex,nofollow';
$pageScripts = ['/js/maps.js'];
$pageModuleScripts = ['/js/checkout.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/checkout.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
