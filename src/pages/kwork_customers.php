<?php
// Контроллер корзины

// Получаем корзину пользователя
require_once __DIR__ . '/../getCartInfo.php';

$title  = 'Реализация проекта - Gym Boss';
$canonical = $baseUrl . '/kwork_customers';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/kwork_customers.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
