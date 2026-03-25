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
use Dotenv\Dotenv;    // библиотека для прочтения .env файла
use App\Db\Db;    // используем класс Db из пространства имен App\Db
// use App\Support\Logger;
use App\Api\BaseController;    // используем класс с базовым контроллером для наследования остальных
use App\Integrations\Resend\ResendGateway;
use App\Mail\MailService;
use App\Auth\AuthSession;    // используем класс AuthSession из пространства имен App\Auth
use App\Auth\AuthService;
use App\Api\AuthController;
use App\Account\AccountService;
use App\Api\AccountController;
use App\Products\ProductService;    // используем класс ProductService из пространства имен App\Products
use App\Api\ProductController;    // используем класс ProductController из пространства имен App\Api
use App\Cart\CartSession;   // используем класс CartSession из пространства имен App\Cart
use App\Cart\CartService;   // используем класс CartService из пространства имен App\Cart
use App\Api\CartController;    // используем класс CartController из пространства имен App\Api
use App\Orders\OrderService;   // используем класс OrderService из пространства имен App\Orders
use App\Orders\CancelOrderUseCase;    
use App\Api\OrderController;   // используем класс OrderController из пространства имен App\Api
use App\Integrations\Dadata\DadataClient;   // используем класс DadataClient из пространства имен App\Integrations\Dadata
use App\Api\DadataController;   // используем класс DadataController из пространства имен App\Api
use App\Stores\StoreService;   // используем класс StoreService из пространства имен App\Stores
use App\Api\StoreController;   // используем класс StoreController из пространства имен App\Api
use App\Integrations\Yookassa\YookassaGateway;
use App\Payments\PaymentService;
use App\Payments\PaymentStatusSyncService;
use App\Payments\WebhookService;
use App\Api\WebhookController;

// Работаем с библиотекой Dotenv, загружаем .env файл
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Подключаем общие файлы (позже замениться только на composer с настр-ми зав-ями)
require_once __DIR__ . '/Db/Db.php';    // подключаем файл с классом для подключения к бд
require_once __DIR__ . '/Support/helpers.php';    // подключаем файл с вспомогательными утилитами
// require_once __DIR__ . '/Support/Logger.php';
require_once __DIR__ . '/Api/BaseController.php';
require_once __DIR__ . '/Support/AppException.php';
require_once __DIR__ . '/Integrations/Resend/EmailMessageDto.php';
require_once __DIR__ . '/Integrations/Resend/ResendGateway.php';    // файл с gateway-ем Resend-а, оберткой над его sdk
require_once __DIR__ . '/Mail/MailService.php';    // подключаем файл с классом-сервисом для отправки писем
require_once __DIR__ . '/Auth/AuthSession.php';    // подключаем файл с классом для работы с сессией
require_once __DIR__ . '/Auth/AuthService.php';    // подключаем файл с классом-сервисом для работы с пользователями
require_once __DIR__ . '/Api/AuthController.php';    // подключаем файл с классом-контроллером для взаимодействия с пользователями
require_once __DIR__ . '/Account/AccountService.php';
require_once __DIR__ . '/Api/AccountController.php';
require_once __DIR__ . '/Products/ProductService.php';    // подключаем файл с классом-сервисом для получения инфы о товарах
require_once __DIR__ . '/Api/ProductController.php';    // подключаем файл с классом-контроллером для получения инфы о товарах
require_once __DIR__ . '/Cart/CartSession.php';    // подключаем файл с классом для получения/установки cart id в куках
require_once __DIR__ . '/Cart/CartService.php';    // подключаем файл с классом-сервисом для управления корзинами пользователей
require_once __DIR__ . '/Api/CartController.php';    // подключаем файл с классом-контроллером для управления корзинами пользователей
require_once __DIR__ . '/Orders/OrderService.php';    // подключаем файл с классом-сервисом для управления заказами
require_once __DIR__ . '/Orders/CancelOrderUseCase.php';
require_once __DIR__ . '/Api/OrderController.php';    // подключаем файл с классом-контроллером для управления заказами
require_once __DIR__ . '/Integrations/Dadata/DadataClient.php';    // подключаем файл с классом-контроллером для управления заказами
require_once __DIR__ . '/Api/DadataController.php';    // подключаем файл с классом-контроллером для получения подсказок
require_once __DIR__ . '/Stores/StoreService.php';    // подключаем файл с классом-сервисом для получения данных о магазинах
require_once __DIR__ . '/Api/StoreController.php';    // подключаем файл с классом-контроллером для получения магазинов
require_once __DIR__ . '/Integrations/Yookassa/CreatedPaymentDto.php';    // файл с dto классом 
require_once __DIR__ . '/Integrations/Yookassa/YookassaGateway.php';    // файл с gateway-ем юкассы, оберткой над ее sdk
require_once __DIR__ . '/Payments/PaymentService.php';
require_once __DIR__ . '/Payments/PaymentStatusSyncService.php';
require_once __DIR__ . '/Payments/WebhookService.php';
require_once __DIR__ . '/Api/WebhookController.php';

// Подключаем конфиги (массивы из переменных с константами из .env)
$appConfig = require __DIR__ . '/config/app.php';
$deliveryConfig = require __DIR__ . '/config/delivery.php';
$servicesConfig = require __DIR__ . '/config/services.php';

// Получаем URL сайта из переменных окружения
$appUrl = $appConfig['url'] ?? null;
if (!$appUrl) {
    error_log('APP_URL is not set');    // логируем
    throw new RuntimeException('APP_URL is not set');   // и падаем
}
$baseUrl = rtrim($appUrl, '/');

// Подключение к БД через публичный, статический метод класса (не нужно создавать экземпляр)
$db = Db::connect($servicesConfig['database']);

// Работаем с электронными письмами
$resendGateway = new ResendGateway(
    $servicesConfig['resend']['api_key'],
    $servicesConfig['resend']['mail_from_email'],
    $servicesConfig['resend']['mail_from_name'],
    $servicesConfig['resend']['mail_reply_to']
);
$mailService = new MailService($resendGateway);

// Работаем с сессией и пользователями
$authSession = new AuthSession();
$authService = new AuthService($db, $mailService, $baseUrl);
$authController = new AuthController($authSession, $authService);

// Работаем с аккаунтами пользователей
$accountService = new AccountService($db);
$accountController = new AccountController($authSession, $accountService);

// Работаем с сервисом и контроллером товара
$productService = new ProductService($db);    // создаем экземпляр класса
$productController = new ProductController($productService);    // создаем экземпляр класса

// Работаем с сервисом, контроллером и сессией корзины
$cartSession = new CartSession();
$cartService = new CartService($db, $productService);
$cartController = new CartController($cartSession, $authSession, $cartService);

// Создаем сервис заказов
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
// Работаем с платежами
$yookassaGateway = new YookassaGateway($servicesConfig['yookassa']['shop_id'], $servicesConfig['yookassa']['api_key']);
$paymentService = new PaymentService($db, $baseUrl, $orderService, $accountService, $yookassaGateway, $deliveryConfig['vat_code']);
$paymentStatusSyncService = new PaymentStatusSyncService($db, $baseUrl, $orderService, $paymentService, $mailService, $yookassaGateway);
// Создаем use-case/координационный класс для отмены заказов и отмены его платежей
$cancelOrderUseCase = new CancelOrderUseCase($db, $orderService, $paymentService);
// Создаем контроллер заказов
$orderController = new OrderController(
    $authSession,
    $orderService,
    $cancelOrderUseCase,
    $cartSession,
    $cartService,
    $paymentService,
    $paymentStatusSyncService
);

// Создаем вебхук для обработки уведомлений от юкассы
$webhookService = new WebhookService($paymentStatusSyncService, $yookassaGateway);
$webhookController = new WebhookController($webhookService);

// Работаем с внешним сервисом DaData
$dadataClient = new DadataClient($servicesConfig['dadata']['api_key']);    // создаем клиент для взаимодействия с сервисом DaData
$dadataController = new DadataController($dadataClient);

// Работаем с сервисом и контроллером магазинов
$storeService = new StoreService($db);
$storeController = new StoreController($storeService);