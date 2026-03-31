<?php
// Файл для мелких технических функций, утилит (например форматирование цен, дат и т.д.; работа с путями/URL;
// обёртка для htmlspecialchars, сокращённые проверки, маленькие преобразования строк/массивов)

// Проверка авторизации для api запросов (с 401 ответом и json ответом)
function requireApiAuth(\App\Auth\AuthSession $authSession): void {
    // Если пользователь не залогинен - возвращаем 401-й статус и json ответ с указанием
    if (!$authSession->isAuthenticated()) {
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

// Проверка подтвержденной почты для api запросов (со статусом и json в ответе)
function requireApiVerifiedEmail(\App\Auth\AuthSession $authSession, \App\Auth\AuthService $authService): void {
    // Получаем userId из сессии через метод
    $userId = $authSession->getUserId();

    // Если пользователь не залогинен - возвращаем 401-й статус и json ответ с указанием
    if ($userId === null) {
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

    // Если почта не подтверждена - возвращаем 403-й статус и json ответ с указанием
    if (!$authService->isEmailVerified($userId)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => 'EMAIL_UNVERIFIED',
                'message' => 'User email is not verified',
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Проверка авторизации для web маршрутов (с редиректом)
function requireWebAuth(\App\Auth\AuthSession $authSession): void {
    if (!$authSession->isAuthenticated()) {
        header('Location: /');
        exit;
    }
}

// Проверка подтвержденной почты для api запросов (со статусом и json в ответе)
function requireWebVerifiedEmail(\App\Auth\AuthSession $authSession, \App\Auth\AuthService $authService): void {
    // Получаем userId из сессии через метод
    $userId = $authSession->getUserId();

    // Если пользователь не залогинен - редиректим на главную
    if ($userId === null) {
        header('Location: /');
        exit;
    }

    // Если почта не подтверждена - редиректим на страницу аккаунта
    if (!$authService->isEmailVerified($userId)) {
        header('Location: /account');
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

// Форматирование числе в формат для email-писем
function formatDateForEmail(?string $dateStr): string {
    if (empty($dateStr)) return '';
    $date = new \DateTime($dateStr);
    return $date->format('d.m.Y, H:i');
}

// Форматирование цены товара
function formatPrice(float $value): string {
    return number_format($value, 2, ',', ' ');
}