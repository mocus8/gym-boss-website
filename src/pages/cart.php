<?php
// Контроллер корзины

// Получаем корзину пользователя
require_once __DIR__ . '/../getCartInfo.php';

$title  = 'Корзина товаров - Gym Boss';
$robots = 'noindex,nofollow';
// Какие js нужны этой странице (если не нужны не указываем)
$pageScripts = ['/js/cart.js']; 

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/cart.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
