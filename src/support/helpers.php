<?php
// Файл для мелких технических функций, утилит (например форматирование цен, дат и т.д.; работа с путями/URL;
// обёртка для htmlspecialchars, сокращённые проверки, маленькие преобразования строк/массивов)

// Получение id юзера
function getCurrentUserId(): ?int {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = $_SESSION['user']['id'] ?? null;

    return $userId;
}

// Форматирование цены товара
function formatPrice(float $value): string {
    return number_format($value, 2, ',', ' ');
}