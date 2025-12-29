<?php
// Контроллер страницы магазинов

$title  = 'Магазины Gym Boss';
$canonical = $baseUrl . '/stores';
$pageScripts = ['/js/maps.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/stores.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
