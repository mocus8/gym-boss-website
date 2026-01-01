<?php
// bootstrap - общая инициализация окружения

session_start();

// Подключаем общие файлы
require_once __DIR__ . '/../vendor/autoload.php';   // подключаем composer
require_once __DIR__ . '/envLoader.php';    // подключаем загрузчик .env файла
require_once __DIR__ . '/support/helpers.php'; // подключаем файл с вспомогательными утилитами
require_once __DIR__ . '/Db/Db.php'; // подключаем файл с классом для подключения к бд
require_once __DIR__ . '/Cart/CartSession.php'; // подключаем файл с классом для получения/установки cart id в куках
require_once __DIR__ . '/Cart/CartService.php'; // подключаем файл с классом-сервисом для управления корзинами пользователей

// Подключаем пространства имен
use App\Db\Db;  // используем класс Db из пространства имен App\Db
use App\Cart\CartSession;   // используем класс CartSession из пространства имен App\Cart
use App\Cart\CartService;   // используем класс CartService из пространства имен App\Cart

// Подключение к БД через публичный, статический метод класса (не нужно создавать экземпляр)
$db = Db::connectFromEnv();

// Создаем экземпляр класса и получаем id сеанса корзины (не статически т.к. более гибко для будующего)
$cartSession = new CartSession();
$cartSessionId = $cartSession->getId();

// Полчаем id user-а из сессии
$userId = $_SESSION['user']['id'] ?? null;

// Работаем с сервисом корзины
$cartService = new CartService($db);    // создаем экземпляр класса
$cartId = $cartService->getOrCreateCartId($cartSessionId, $userId);    // получаем id корзины из бд
$cartCount = $cartService->getItemsCount($cartId);    // получаем кол-во товаров в корзине (для отображения в хедере)

// Получаем URL сайта из переменных окружения
$appUrl = getenv('APP_URL');
if (!$appUrl) {
    error_log('APP_URL is not set');    // логируем
    throw new RuntimeException('APP_URL is not set');   // и падаем
}

$baseUrl   = rtrim($appUrl, '/');