<?php
// bootstrap - общая инициализация окружения

// Безопасный старт и управление сессией
session_name('PHPSESSID');
session_start();

// Сохраниение текущего времени
$now = time();

// Разлогин после 60 минут неактивности
if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    // Отправляем на главную
    header('Location: /');
    exit;
}

// Регенерация ID сессии раз в 5 минут (защита от фиксации)
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = $now;
} elseif ($now - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = $now;
}

// Обновляем отметку активности
$_SESSION['last_activity'] = $now;

// Подключаем composer
require_once __DIR__ . '/../vendor/autoload.php';

// Подключаем пространства имен
use Dotenv\Dotenv; // библиотека для прочтения .env файла
use App\Db\Db;  // используем класс Db из пространства имен App\Db
use App\Product\ProductService;    // используем класс ProductService из пространства имен App\Product
use App\Api\ProductController;    // используем класс ProductController из пространства имен App\Api
use App\Cart\CartSession;   // используем класс CartSession из пространства имен App\Cart
use App\Cart\CartService;   // используем класс CartService из пространства имен App\Cart
use App\Api\CartController;    // используем класс CartController из пространства имен App\Api
use App\Order\OrderService;   // используем класс OrderService из пространства имен App\Order
use App\Api\OrderController;   // используем класс OrderController из пространства имен App\Api
use App\Integrations\Dadata\DadataClient;   // используем класс DadataClient из пространства имен App\Integrations\Dadata

// Работаем с библиотекой Dotenv, загружаем .env файл
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Подключаем общие файлы (позже замениться только на composer с настр-ми зав-ями)
require_once __DIR__ . '/Db/Db.php';    // подключаем файл с классом для подключения к бд
require_once __DIR__ . '/support/helpers.php';    // подключаем файл с вспомогательными утилитами
require_once __DIR__ . '/Product/ProductService.php';    // подключаем файл с классом-сервисом для получения инфы о товарах
require_once __DIR__ . '/Api/ProductController.php';    // подключаем файл с классом-контроллером для получения инфы о товарах
require_once __DIR__ . '/Cart/CartSession.php';    // подключаем файл с классом для получения/установки cart id в куках
require_once __DIR__ . '/Cart/CartService.php';    // подключаем файл с классом-сервисом для управления корзинами пользователей
require_once __DIR__ . '/Api/CartController.php';    // подключаем файл с классом-контроллером для управления корзинами пользователей
require_once __DIR__ . '/Order/OrderService.php';    // подключаем файл с классом-сервисом для управления заказами
require_once __DIR__ . '/Api/OrderController.php';    // подключаем файл с классом-контроллером для управления заказами
require_once __DIR__ . '/Integrations/Dadata/DadataClient.php';    // подключаем файл с классом-контроллером для управления заказами

// Подключаем конфиги (массивы из переменных с константами из .env)
$appConfig = require __DIR__ . '/config/app.php';
$servicesConfig = require __DIR__ . '/config/services.php';
$deliveryConfig = require __DIR__ . '/config/delivery.php';

// Подключение к БД через публичный, статический метод класса (не нужно создавать экземпляр)
$db = Db::connect($servicesConfig['database']);

// Работаем с сервисом и контроллером товара
$productService = new ProductService($db);    // создаем экземпляр класса
$productController = new ProductController($productService);    // создаем экземпляр класса

// Создаем экземпляр класса и получаем id сеанса корзины (не статически т.к. более гибко для будующего)
$cartSession = new CartSession();
$cartSessionId = $cartSession->getId();

$userId = getCurrentUserId();

// Работаем с сервисом и контроллером корзины
$cartService = new CartService($db, $productService);    // создаем экземпляр класса
$cartId = $cartService->getOrCreateCartId($cartSessionId, $userId);    // получаем id корзины из бд
$cartCount = $cartService->getItemsCount($cartId);    // получаем кол-во товаров в корзине (для отображения в хедере)
$cartController = new CartController($cartSession, $cartService);

// Работаем с сервисом и контроллером заказов
// В параметры передаем бд, другие сервисы для взаимодействия и переменные доставки из конфига
$orderService = new OrderService(
    $db,
    $productService,
    $cartService,
    $deliveryConfig['courier_delivery_price'],
    $deliveryConfig['free_delivery_threshold'],
    $deliveryConfig['pickup_ready_from_hours'],
    $deliveryConfig['pickup_ready_to_hours'],
    $deliveryConfig['courier_delivery_from_hours'],
    $deliveryConfig['courier_delivery_to_hours'],
);
$orderController = new OrderController($orderService, $cartSession, $cartService);

// Работаем с внешним сервисом DaData
// Создаем клиент для взаимодействия с сервисом DaData
$dadataClient = new DadataClient($servicesConfig['dadata']['api_key']);

// Получаем URL сайта из переменных окружения
$appUrl = $appConfig['url'] ?? null;
if (!$appUrl) {
    error_log('APP_URL is not set');    // логируем
    throw new RuntimeException('APP_URL is not set');   // и падаем
}

$baseUrl = rtrim($appUrl, '/');