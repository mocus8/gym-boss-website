<?php
// Контроллер страницы контактов

$title  = 'Контакты - Gym Boss';
$canonical = $baseUrl . '/contacts';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/contacts.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
