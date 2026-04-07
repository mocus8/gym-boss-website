<?php
declare(strict_types=1);

namespace App\Db;
use App\Support\Logger;

class Db {
    // Публичный статический (для класса в целом, а не для объекта) метод подключения к бд из файла .env
    public static function connect(array $config, Logger $logger): \mysqli { // \mysqli значит что connectFromEnv() возвращает объект mysqli
        // Берем переменные окружения для БД из конфига
        $host = $config['host'] ?? null;
        $name = $config['name'] ?? null;
        $user = $config['user'] ?? null;
        $pass = $config['pass'] ?? null;

        // Если переменных нет, то логируем и падаем 
        if (!$host || !$name || !$user) {
            $logger->error('Database config is not set properly');
            throw new \RuntimeException('Database configuration error');
        }

        // Подключение к БД
        $db = new \mysqli($host, $user, $pass, $name);

        // Проверяем подключение
        if ($db->connect_error) {
            $logger->error('DB connection failed', [
                'db_host'  => $host,
                'db_name'  => $name,
                'db_error' => $db->connect_error,
            ]);
            throw new \RuntimeException('Database connection failed');
        }

        // Настраиваем кодировку
        if (!$db->set_charset('utf8mb4')) {
            $logger->error('Failed to set MySQL charset', [
                'db_error' => $db->error,
            ]);
        }

        // Настраиваем UTC время (все хранится в UTC, при показе пользователю переводится в московское)
        if (!$db->query("SET time_zone = '+00:00'")) {
            $logger->error('Failed to set MySQL time_zone', [
                'db_error' => $db->error,
            ]);
        }

        // Возвращаем mysql объект подключения
        return $db;
    }
}
