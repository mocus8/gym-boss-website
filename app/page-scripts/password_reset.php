<?php
declare(strict_types=1);

// Контроллер страницы сброса пароля

// Получаем сырой токен подтверждения из query-параметра
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

$title  = 'Сброс пароля - Gym Boss';
$robots = 'noindex, nofollow';
$pageModuleScripts = ['/assets/js/users/auth/password-reset.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/password_reset.php';
$content = ob_get_clean();

// Подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';