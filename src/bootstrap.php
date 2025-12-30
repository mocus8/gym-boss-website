<?php
// Общая инициализация окружения

session_start();

// Подключаем общие файлы
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/envLoader.php';
require_once __DIR__ . '/helpers.php';

// Читаем переменные окружения для БД
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

// Если переменных нет, то логируем и падаем 
if ($dbHost === false || $dbName === false || $dbUser === false || $dbPass === false) {
    error_log('Database env variables are not set');
    throw new RuntimeException('Database configuration error');
}

// Подключение к БД
$db = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

// Проверяем подключение
if ($db === false) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    throw new RuntimeException('Database connection failed');
}


// Получаем URL сайта из переменных окружения
$appUrl = getenv('APP_URL');
if (!$appUrl) {
    // логируем
    error_log('APP_URL is not set');
    // и падаем
    throw new RuntimeException('APP_URL is not set');
}

$baseUrl   = rtrim($appUrl, '/');