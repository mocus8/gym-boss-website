<?php
declare(strict_types=1);

// bootstrap - общая инициализация окружения

// Подключаем composer
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;    // библиотека для прочтения .env файла

use App\Support\Logger;
use App\Db\Db;
use App\Support\Flash;

use App\Integrations\GoogleRecaptcha\GoogleRecaptchaClient;
use App\Integrations\Yookassa\YookassaGateway;
use App\Integrations\Dadata\DadataClient;
use App\Integrations\Resend\ResendGateway;

use App\Users\UserRepository;
use App\Auth\AuthSession;
use App\Auth\AuthService;
use App\Auth\LoginAttemptRepository;
use App\Auth\PasswordResetTokenRepository;
use App\Auth\EmailVerificationTokenRepository;
use App\Account\AccountService;

use App\Stores\StoreRepository;
use App\Stores\StoreService;

use App\Products\ProductRepository;
use App\Products\ProductService;

use App\Mail\MailService;

use App\Cart\CartSession;
use App\Cart\CartRepository;
use App\Cart\CartItemRepository;
use App\Cart\CartService;

use App\Orders\OrderRepository;
use App\Orders\OrderItemRepository;
use App\Orders\OrderService;
use App\Orders\CancelOrderUseCase;   

use App\Payments\PaymentRepository;
use App\Payments\PaymentService;
use App\Payments\PaymentStatusSyncService;
use App\Payments\WebhookService;

use App\Api\BaseController;
use App\Api\DadataController;
use App\Api\WebhookController;
use App\Api\AuthController;
use App\Api\AccountController;
use App\Api\ProductController;
use App\Api\CartController;
use App\Api\OrderController;
use App\Api\StoreController;

// Работаем с библиотекой Dotenv, загружаем .env файл
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Начинаем сессию
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

// Подключаем конфиги (массивы из переменных с константами из .env)
$appConfig = require __DIR__ . '/config/app.php';
$deliveryConfig = require __DIR__ . '/config/delivery.php';
$servicesConfig = require __DIR__ . '/config/services.php';

// Создаем логгер
$logger = new Logger($appConfig['log_file'], $appConfig['log_level']);

// Получаем URL сайта из переменных окружения
$appUrl = $appConfig['url'] ?? null;
if (!$appUrl) {
    $logger->error('APP_URL is not set in appConfig');    // логируем
    throw new RuntimeException('APP_URL is not set');   // и падаем
}

$baseUrl = rtrim($appUrl, '/');

// Подключение к БД через публичный, статический метод класса (не нужно создавать экземпляр)
$db = Db::connect($servicesConfig['database'], $logger);

// Создаем репозитории для работой с бд
$userRepository = new UserRepository($db);
$emailVerificationTokenRepository = new EmailVerificationTokenRepository($db);
$loginAttemptRepository = new LoginAttemptRepository($db);
$passwordResetTokenRepository = new PasswordResetTokenRepository($db);
$cartRepository = new CartRepository($db);
$cartItemRepository = new CartItemRepository($db);
$orderRepository = new OrderRepository($db);
$orderItemRepository = new OrderItemRepository($db);
$paymentRepository = new PaymentRepository($db);
$productRepository = new ProductRepository($db);
$storeRepository = new StoreRepository($db);

// Работаем с серверными флеш-уведомлениями
$flash = new Flash();

// Работаем с электронными письмами
$resendGateway = new ResendGateway(
    $servicesConfig['resend']['api_key'],
    $servicesConfig['resend']['mail_from_email'],
    $servicesConfig['resend']['mail_from_name'],
    $servicesConfig['resend']['mail_reply_to']
);
$mailService = new MailService($resendGateway);

// Работаем с сервисом и контроллером товара
$productService = new ProductService($productRepository);
$productController = new ProductController($productService, $logger);

// Создаем клиент для обращения к гугл рекапче
$googleRecaptchaClient = new GoogleRecaptchaClient($servicesConfig['recaptcha']['secret_key'], $logger);

// Работаем с корзинами и пользователями
$authSession = new AuthSession();
$authService = new AuthService(
    $db, 
    $userRepository, 
    $emailVerificationTokenRepository, 
    $loginAttemptRepository,
    $passwordResetTokenRepository, 
    $mailService, 
    $baseUrl, 
    $logger
);
$accountService = new AccountService($db, $userRepository, $passwordResetTokenRepository);
$accountController = new AccountController($authSession, $accountService, $flash, $logger);
$cartSession = new CartSession();
$cartService = new CartService($cartRepository, $cartItemRepository, $productService);
$authController = new AuthController(
    $authSession, 
    $authService, 
    $cartSession, 
    $cartService, 
    $googleRecaptchaClient, 
    $flash, 
    $logger
);
$cartController = new CartController($cartSession, $authSession, $cartService, $logger);

// Создаем сервис заказов
// В параметры передаем бд, другие сервисы для взаимодействия и переменные доставки из конфига
$orderService = new OrderService(
    $db,
    $productService,
    $cartService,
    $orderRepository,
    $orderItemRepository,
    $deliveryConfig['courier_delivery_price'],
    $deliveryConfig['free_delivery_threshold'],
    $deliveryConfig['pickup_ready_from_hours'],
    $deliveryConfig['pickup_ready_to_hours'],
    $deliveryConfig['courier_delivery_from_hours'],
    $deliveryConfig['courier_delivery_to_hours'],
);
// Работаем с платежами
$yookassaGateway = new YookassaGateway($servicesConfig['yookassa']['shop_id'], $servicesConfig['yookassa']['api_key']);
$paymentService = new PaymentService(
    $db, 
    $baseUrl, 
    $orderService, 
    $paymentRepository,
    $userRepository,
    $yookassaGateway, 
    $deliveryConfig['vat_code'],
    $logger
);
$paymentStatusSyncService = new PaymentStatusSyncService(
    $db,
    $baseUrl,
    $orderService,
    $paymentService,
    $mailService,
    $yookassaGateway,
    $logger
);
// Создаем use-case/координационный класс для отмены заказов и отмены его платежей
$cancelOrderUseCase = new CancelOrderUseCase($db, $baseUrl, $orderService, $paymentService, $mailService, $logger);
// Создаем контроллер заказов
$orderController = new OrderController(
    $authSession,
    $orderService,
    $cancelOrderUseCase,
    $cartSession,
    $cartService,
    $paymentService,
    $paymentStatusSyncService,
    $logger
);

// Создаем вебхук для обработки уведомлений от юкассы
$webhookService = new WebhookService($paymentStatusSyncService, $yookassaGateway);
$webhookController = new WebhookController($webhookService, $logger);

// Работаем с внешним сервисом DaData
$dadataClient = new DadataClient($servicesConfig['dadata']['api_key']);    // создаем клиент для взаимодействия с сервисом DaData
$dadataController = new DadataController($dadataClient, $logger);

// Работаем с сервисом и контроллером магазинов
$storeService = new StoreService($storeRepository);
$storeController = new StoreController($storeService, $logger);

// Устанавливаем на все приложение хендлер
// Если где то будет непойманая ошибка - вызывается этот обработчик
// set_exception_handler - регистрация глобального обработчика
// function (\Throwable $e) - анонимная функция, без имени
// use ($logger) - замыкание переменной внутрь функции
set_exception_handler(function (\Throwable $e) use ($logger) {
    // Получаем путь запроса и метод
    $uri = $_SERVER['REQUEST_URI'] ?? null;
    $method = $_SERVER['REQUEST_METHOD'] ?? null;

    $logger->error('Unhandled exception', [
        'uri'       => $uri,
        'method'    => $method,
        'exception' => $e,
    ]);

    $path = $uri ? parse_url($uri, PHP_URL_PATH) : '';
    $isApi = is_string($path) && str_starts_with($path, '/api/');

    http_response_code(500);

    if ($isApi) {
        // Если api запрос - отвечаем ошибкой
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Internal server error',
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Если web запрос - рендерим 500 страницу
        require __DIR__ . '/pages/500.php';
    }

    exit;
});