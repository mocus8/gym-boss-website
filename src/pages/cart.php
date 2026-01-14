<?php
// Контроллер корзины

$title  = 'Корзина товаров - Gym Boss';
$robots = 'noindex,nofollow';
$pageModuleScripts = ['/js/pages/cart.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/cart.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
