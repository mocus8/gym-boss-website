<?php
// Контроллер страницы личного кабинета

$title  = 'Личный кабинет - Gym Boss';
$robots = 'noindex,nofollow';
$pageModuleScripts = ['/js/users/account/account.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/account.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
