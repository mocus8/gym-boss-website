<?php
declare(strict_types=1);

namespace App\Support;

// Класс для работы с флеш-уведомлениями на уровне сервера: класть и получать + удалять сообщения
class Flash {
    // Метод для установки сообщения в сесиию
    public function set(string $message): void {
        $_SESSION['flash'] = $message;
    }

    // Метод для получения и удаления сообщения из сессии
    public function get(): ?string {
        if (!isset($_SESSION['flash'])) return null;

        $message = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $message;
    }
}
