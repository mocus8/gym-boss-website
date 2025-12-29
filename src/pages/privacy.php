<?php
// Контроллер страницы с политикой конф-сти

$title  = 'Политика конфиденциальности - Gym Boss';
$canonical = $baseUrl . '/privacy';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/privacy.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
