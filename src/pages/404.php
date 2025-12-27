<?php
$title  = 'Страница не найдена';
$robots = 'noindex,nofollow';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/404.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
