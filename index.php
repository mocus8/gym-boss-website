<?php
// Единая точка входа, проостейший роутер

session_start();

// Подключаем необходимые для всего сайта php-файлы 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/envLoader.php';

// Получаем URL сайта из переменных окружения
$appUrl = getenv('APP_URL');
if (!$appUrl) {
    // логируем
    error_log('APP_URL is not set');
    // и падаем
    throw new RuntimeException('APP_URL is not set');
}

$baseUrl   = rtrim($appUrl, '/');

// Разбор URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Определение маршрутов
$routes = [
    ''              => 'home.php',
    '/'             => 'home.php',
    '/cart'       => 'cart.php',
    '/contacts'   => 'contacts.php',
    '/kwork-customers' => 'kwork_customers.php',
    '/my-orders'  => 'my_orders.php',
    '/order-making' => 'order_making.php',
    '/privacy'    => 'privacy.php',
    '/stores'     => 'stores.php',
];

// Если маршрут есть в списке, подключаем соответствующий файл
if (isset($routes[$uri])) {
    require __DIR__ . '/' . $routes[$uri];
    exit;
}
// Страница товара: /product/slug
elseif (preg_match('#^/product/([a-zA-Z0-9-]+)$#', $uri, $matches)) {
    $_GET['url'] = $matches[1];
    require __DIR__ . '/product.php';
    exit;
}
// Страница заказа: /order/123
elseif (preg_match('#^/order/([0-9]+)$#', $uri, $matches)) {
    $_GET['orderId'] = $matches[1];
    require __DIR__ . '/order.php';
    exit;
}
// Любой другой путь - 404
else {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
?>