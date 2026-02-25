<?php

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Db;

class Db {
    // Публичный статический (для класса в целом, а не для объекта) метод подключения к бд из файла .env
    public static function connect(array $config): \mysqli { // \mysqli значит что connectFromEnv() возвращает объект mysqli
        // Берем переменные окружения для БД из конфига
        $host = $config['host'] ?? null;
        $name = $config['name'] ?? null;
        $user = $config['user'] ?? null;
        $pass = $config['pass'] ?? null;

        // Если переменных нет, то логируем и падаем 
        if (!$host || !$name || !$user) {
            error_log('Database config is not set properly');
            throw new \RuntimeException('Database configuration error');
        }

        // Подключение к БД
        $db = \mysqli_connect($host, $user, $pass, $name);

        // Проверяем подключение
        if ($db === false) {
            error_log('DB connection failed: ' . \mysqli_connect_error());
            throw new \RuntimeException('Database connection failed');
        }

        // Настраиваем UTC время (все хранится в UTC, при показе пользователю переводится в московское)
        if (!$db->query("SET time_zone = '+00:00'")) {
            error_log('Error setting MySQL time_zone: ' . $db->error);
        }

        // Возвращаем mysql объект подключения
        return $db;
    }
}
