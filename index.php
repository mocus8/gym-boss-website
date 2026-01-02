<?php
// Единая точка входа, роутер и для web и для php запросов

// Подключаем bootstrap (общая инициализация)
require_once __DIR__ . '/src/bootstrap.php';

// Подключаем пространства имен
use App\Cart\CartService;    // используем класс CartService из пространства имен App\Cart
use App\Api\CartController;    // используем класс CartController из пространства имен App\Api

// Разбор URI (и метода)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];    // GET, POST и т.д.

$userId = getCurrentUserId();

// Api-маршруты
if (strpos($uri, '/api/') === 0) {
    // убираем префикс /api
    $apiPath = substr($uri, strlen('/api'));

    // создаём сервис и контроллер
    $cartService    = new CartService($db);
    $cartController = new CartController($cartService);

    // Определение api маршрутов
    $apiRoutes = [
        'GET' => [
            '/cart'                => [$cartController, 'getCart'],
        ],
        'POST' => [
            '/cart/add-item'       => [$cartController, 'addItem'],
            '/cart/update-item-qty'=> [$cartController, 'updateItemQty'],
            '/cart/remove-item'    => [$cartController, 'removeItem'],
            '/cart/clear'          => [$cartController, 'clear'],
        ],
    ];

    // Если маршрут есть в списке, подключаем соответствующий метод cartController
    if (isset($apiRoutes[$method][$apiPath])) {
        $handler = $apiRoutes[$method][$apiPath];    // handler - массив [объект контроллера][строка с именем метода]
        call_user_func($handler);    // вызываем метод
        exit;
    }

    // Любой другой путь - 404-й статус и json ответ с указанием
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => 'API endpoint not found',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Web-маршруты

// Web маршруты, требуещие авторизации
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

// Определение web маршрутов
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