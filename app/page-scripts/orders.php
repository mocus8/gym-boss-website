<?php
declare(strict_types=1);

// Контроллер страницы заказов пользователя

$title  = 'Мои заказы - Gym Boss';
$robots = 'noindex, nofollow';
$pageModuleScripts = ['/assets/js/orders/orders.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/orders.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
