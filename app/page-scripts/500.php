<?php
declare(strict_types=1);

$title  = 'Ошибка сервера';
$robots = 'noindex,nofollow';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/500.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
