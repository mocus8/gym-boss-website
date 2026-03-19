<?php
// Единая точка входа, роутер и для web и для php запросов

// Подключаем bootstrap (общая инициализация)
require_once __DIR__ . '/src/bootstrap.php';

// Разбор URI (и метода)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'];    // GET, POST и т.д.

// Маршрут вебхука (получение уведомлений от провайдера оплаты)
if ($method === 'POST' && $uri === '/webhook/yookassa') {
    $webhookController->handleNotification();
    exit;
}

// Api-маршруты
if (strpos($uri, '/api/') === 0) {
    // убираем префикс /api
    $apiPath = substr($uri, strlen('/api'));

    // Api маршруты, требуещие авторизации
    $protectedApiRoutes = [
        'POST' => [
            '/auth/email/resend',
            '/auth/logout',
            '/orders/create-from-cart',
            '/dadata/suggest/address',
        ],
        'GET' => [
            '/auth/me',
            '/orders',
        ],
        'PUT' => [
            '/account/password',
        ],
        'PATCH' => [
            '/account/profile',
        ],
        'DELETE' => [
            '/account',
        ]
        // Сюда же можно добавить ещё закрытых маршрутов для неавторизированных пользователей
    ];

    // Если маршрут требует авторизации - проверяем авторизацию
    if (isset($protectedApiRoutes[$method]) && in_array($apiPath, $protectedApiRoutes[$method], true)) {
        requireApiAuth($authSession);
    }

    // Api маршруты, требующие подтвержденного email
    $verifiedEmailApiRoutes = [
        'POST' => [
            '/orders/create-from-cart',
            '/dadata/suggest/address',
        ],
        'GET' => [
            '/orders',
        ]
        // Сюда же можно добавить ещё закрытых маршрутов
    ];

    // Если маршрут требует подтвержденной почты - проверяем верификацию
    if (isset($verifiedEmailApiRoutes[$method]) && in_array($apiPath, $verifiedEmailApiRoutes[$method], true)) {
        requireVerifiedEmail($authSession, $authService);
    }

    // Определение api маршрутов
    $apiRoutes = [
        'POST' => [
            '/auth/register' => [$authController, 'register'],
            '/auth/email/resend' => [$authController, 'resendVerification'],
            '/auth/login' => [$authController, 'login'],
            '/auth/logout' => [$authController, 'logout'],
            '/auth/password/forgot' => [$authController, 'forgotPassword'],
            '/auth/password/reset' => [$authController, 'resetPassword'],
            '/cart/add-item' => [$cartController, 'addItem'],
            '/cart/update-item-qty' => [$cartController, 'updateItemQty'],
            '/cart/remove-item' => [$cartController, 'removeItem'],
            '/cart/clear' => [$cartController, 'clear'],
            '/orders/create-from-cart' => [$orderController, 'createFromCart'],
            '/dadata/suggest/address' => [$dadataController, 'suggestAddress'],
        ],
        'GET' => [
            '/auth/me' => [$authController, 'me'],
            '/cart' => [$cartController, 'getCart'],
            '/products' => [$productController, 'getCatalog'],
            '/orders' => [$orderController, 'getUserOrders'],
            '/stores' => [$storeController, 'getAll'],
        ],
        // 'PUT' => [
        //     '/account/profile' => [$accountController, 'updateProfile'],    // TODO
        // ],
        // 'PATCH' => [
        //     '/account/password' => [$accountController, 'updatePassword'],    // TODO
        // ],
        // 'DELETE' => [
        //     '/account' => [$accountController, 'delete'],    // TODO
        // ]
    ];

    // Если маршрут есть в списке, подключаем соответствующий метод
    if (isset($apiRoutes[$method][$apiPath])) {
        $handler = $apiRoutes[$method][$apiPath];    // handler - массив [объект контроллера][строка с именем метода]
        call_user_func($handler);    // вызываем метод
        exit;
    }
    // Поиск товара: GET /api/products/search?q=...
    elseif ($method === 'GET' && $apiPath === '/products/search') {
        $q = $_GET['q'] ?? '';
        $productController->search($q);
        exit;
    }
    // Товар по slug: GET /api/products/{slug}
    elseif ($method === 'GET' && preg_match('#^/products/([a-zA-Z0-9-]+)$#', $apiPath, $matches)) {
        $slug = $matches[1];
        $productController->getBySlug($slug);
        exit;
    }
    // Заказ по id: GET /api/orders/{id}
    elseif ($method === 'GET' && preg_match('#^/orders/([0-9]+)$#', $apiPath, $matches)) {
        requireApiAuth($authSession);
        requireVerifiedEmail($authSession, $authService);

        $orderId  = (int)$matches[1];
        $orderController->getById($orderId );
        exit;
    }
    // Отмена заказа по id: POST /api/orders/{id}/cancel
    elseif ($method === 'POST' && preg_match('#^/orders/([0-9]+)/cancel$#', $apiPath, $matches)) {
        requireApiAuth($authSession);
        requireVerifiedEmail($authSession, $authService);

        $orderId  = (int)$matches[1];
        $orderController->markCancel($orderId);
        exit;
    }
    // Попытка оплаты заказа (получение ссылки для оплаты) по id: POST /api/orders/{id}/start-payment
    elseif ($method === 'POST' && preg_match('#^/orders/([0-9]+)/start-payment$#', $apiPath, $matches)) {
        requireApiAuth($authSession);
        requireVerifiedEmail($authSession, $authService);

        $orderId  = (int)$matches[1];
        $orderController->startPayment($orderId);
        exit;
    }
    // Синхронизация статуса платежа и заказа между бд и юкассой по id: POST /api/orders/{id}/sync-payment
    elseif ($method === 'POST' && preg_match('#^/orders/([0-9]+)/sync-payment$#', $apiPath, $matches)) {
        requireApiAuth($authSession);
        requireVerifiedEmail($authSession, $authService);

        $orderId  = (int)$matches[1];
        $orderController->syncPayment($orderId);
        exit;
    }
    // Получение магазина по id: GET /api/stores/{id}
    elseif ($method === 'GET' && preg_match('#^/stores/([0-9]+)$#', $apiPath, $matches)) {
        $storeId  = (int)$matches[1];
        $storeController->getById($storeId);
        exit;
    }
    // Любой другой путь - 404-й статус и json ответ с указанием
    else {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'API endpoint not found',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Web-маршруты

// Web маршруты, требуещие авторизации
$protectedWebRoutes = [
    '/account/orders',
    '/checkout',
    // сюда можно добавить ещё закрытых маршрутов для неавторизированных пользователей
];

// Если маршрут требует авторизации - проверяем авторизацию
if (in_array($uri, $protectedWebRoutes, true)) {
    requireWebAuth($authSession);
}

// Определение web маршрутов
$routes = [
    '' => 'home.php',
    '/' => 'home.php',
    '/auth/email/verify' => 'email_verify.php',
    '/auth/password/reset' =>'password_reset.php',
    '/cart' => 'cart.php',
    '/contacts' => 'contacts.php',
    '/kwork-customers' => 'kwork_customers.php',
    '/account/orders' => 'orders.php',
    '/checkout' => 'checkout.php',
    '/privacy' => 'privacy.php',
    '/stores' => 'stores.php',
];

// Если маршрут есть в списке, подключаем соответствующий файл
if (isset($routes[$uri])) {
    require __DIR__ . '/src/pages/' . $routes[$uri];
    exit;
}
// Страница товара: /products/slug
elseif (preg_match('#^/products/([a-zA-Z0-9-]+)$#', $uri, $matches)) {
    $_GET['url'] = $matches[1];
    require __DIR__ . '/src/pages/product.php';
    exit;
}
// Страница заказа: /orders/123, также записываем в GET id
elseif (preg_match('#^/orders/([0-9]+)$#', $uri, $matches)) {
    requireWebAuth($authSession);

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