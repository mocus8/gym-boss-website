<?php
declare(strict_types=1);

// Контроллер страницы магазинов

$title  = 'Магазины Gym Boss';
$canonical = $baseUrl . '/stores';
$pageModuleScripts = [
    '/assets/js/stores/stores.page.js',
    '/assets/js/maps/index.js'
];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/stores.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
