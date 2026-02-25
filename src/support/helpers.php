<?php
// Файл для мелких технических функций, утилит (например форматирование цен, дат и т.д.; работа с путями/URL;
// обёртка для htmlspecialchars, сокращённые проверки, маленькие преобразования строк/массивов)

// Получение id юзера
function authId(): ?int {
    return $_SESSION['user']['id'] ?? null;
}

// Проверка авторизации (наличия user id в сессии)
function authCheck(): bool {
    return authId() !== null;
}

// Проверка авторизации для api запросов (с 401 ответом и json ответом)
function requireApiAuth(): void {
    // Если пользователь не залогинен - возвращаем 401-й статус и json ответ с указанием
    if (!authCheck()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => 'UNAUTHORIZED',
                'message' => 'User is not authorized',
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Проверка авторизации для web маршрутов (с редиректом)
function requireWebAuth(): void {
    if (!authCheck()) {
        header('Location: /');
        exit;
    }
}

// Форматирование времени из SQL формата в ISO формат
function sqlUtcToIso(?string $sql): ?string {
    if ($sql === null) return null;
    
    $sql = trim($sql);
    if ($sql === '') return null;

    $dt = new DateTimeImmutable($sql, new DateTimeZone('UTC'));
    return $dt->format('Y-m-d\TH:i:s\Z');
}

// Форматирование временных полей массива из SQL формата в ISO формат
function mapIsoFields(array $row, array $fields): array {
    foreach ($fields as $f) {
        if (array_key_exists($f, $row)) {
            $row[$f] = sqlUtcToIso($row[$f]);
        }
    }
    return $row;
}

// Форматирование цены товара
function formatPrice(float $value): string {
    return number_format($value, 2, ',', ' ');
}