<?php
declare(strict_types=1);

$title  = 'Реализация проекта - Gym Boss';
$canonical = $baseUrl . '/about';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/about.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
