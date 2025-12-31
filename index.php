<?php
// Единая точка входа, проостейший роутер

// Подключаем bootstrap (общая инициализация)
require_once __DIR__ . '/src/bootstrap.php';

// Разбор URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Маршруты требуещие авторизации
$protectedRoutes = [
    '/my-orders',
    '/order-making',
    // сюда же можно добавить ещё маршрутов закрытых для неавторизированных пользователей
];

// Если маршрут требует авторизации и пользователь не залогинен — на главную
if (in_array($uri, $protectedRoutes, true) && $userId === null) {
    header('Location: /');
    exit;
}

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
    require __DIR__ . '/src/pages/' . $routes[$uri];
    exit;
}
// Страница товара: /product/slug
elseif (preg_match('#^/product/([a-zA-Z0-9-]+)$#', $uri, $matches)) {
    $_GET['url'] = $matches[1];
    require __DIR__ . '/src/pages/product.php';
    exit;
}
// Страница заказа: /order/123, также записываем в GET id
elseif (preg_match('#^/order/([0-9]+)$#', $uri, $matches)) {
    $_GET['orderId'] = $matches[1];
    require __DIR__ . '/src/pages/order.php';
    exit;
}
// Любой другой путь - 404
else {
    http_response_code(404);
    require __DIR__ . '/src/pages/404.php';
    exit;
}
?>