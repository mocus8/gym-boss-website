<?php

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Db;

class Db {
    // Публичный статический (для класса в целом, а не для объекта) метод подключения к бд из файла .env
    public static function connectFromEnv(): \mysqli { // \mysqli значит что connectFromEnv() возвращает объект mysqli
        // Читаем переменные окружения для БД
        $host = getenv('DB_HOST');
        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        // Если переменных нет, то логируем и падаем 
        if ($host === false || $name === false || $user === false || $pass === false) {
            error_log('Database env variables are not set');
            throw new \RuntimeException('Database configuration error');
        }

        // Подключение к БД
        $db = mysqli_connect($host, $user, $pass, $name);

        // Проверяем подключение
        if ($db === false) {
            error_log('DB connection failed: ' . mysqli_connect_error());
            throw new \RuntimeException('Database connection failed');
        }

        // Возвращаем mysql объект подключения
        return $db;
    }
}
