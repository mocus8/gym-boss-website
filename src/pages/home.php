<?php
// Контроллер главной страницы

// Получаем католог через сервис
try {
    $catalog = $productService->getCatalog();
} catch (\Throwable $e) {
    // Тут потом нормально логировать
    error_log(sprintf(
        '[home] getCatalog failed: %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    http_response_code(500);
    require __DIR__ . '/500.php';
    exit;
}

// Также с примером того что может быть в контроллере:
// $title  = 'Gym Boss - спорттовары'; - тут тайтл по умолчанию в app.php, не указваем
// $robots = 'noindex,nofollow'; - индексируется по умолчанию app.php, не указваем
$canonical = $baseUrl . '/'; // добавляем "/" чтобы получить каноникал главной страницы как https://gymboss.ru/
// $pageScripts = [
// '/js/cart.js',
// 'https://api-maps.yandex.ru/2.1/?apikey=' . urlencode(getenv('YANDEX_MAPS_KEY')) . '&lang=ru_RU&load=package.full'
// ]; - какие внешние и обычные скрипты (без import/export, например просто обработчики) нужны для страницы, тут не нужны - не указываем
// $pageModuleScripts = ['/js/cart.js']; - какие модульные (есть import/export) скрипты нужны для страницы, тут не нужны - не указываем

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/home.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
