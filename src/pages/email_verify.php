<?php
// Контроллер страницы подтверждения почты 

// Получаем сырой токен подтверждения из query-параметра
$rawToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

// По умолчанию считаем, что токен невалиден
$status = 'token_invalid';

// Если токен не подходит под генерируемый формат
if ($rawToken === '' || strlen($rawToken) !== 64 || !preg_match('/^[0-9a-f]{64}$/i', $rawToken)) {
    $status = 'token_invalid';
} else {
    // Вызываем верификацию почты по токену
    try {
        $authService->verify($rawToken);
        $status = 'success';

    } catch (\App\Auth\AuthException $e) {
        // Переводим ошибку сервиса в статус
        $status = match ($e->getErrorCode()) {
            'TOKEN_INVALID' => 'token_invalid',
            'EMAIL_ALREADY_VERIFIED' => 'already_verified',
            'TOKEN_EXPIRED' => 'token_expired',
            default => 'token_invalid',
        };

    } catch (\Throwable $e) {
        $status = 'server_error';
    }
}

// По полученному статусу определяем контент страницы (матчим по статусу заголовок и сообщение)
$pageData = match ($status) {
    'success' => [
        'title' => 'Почта подтверждена',
        'message' => 'Ваш email успешно подтверждён.',
        'showResend' => false
    ],

    'already_verified' => [
        'title' => 'Почта уже подтверждена',
        'message' => 'Ваш email уже был подтверждён ранее.',
        'showResend' => false
    ],

    'token_invalid' => [
        'title' => 'Недействительная ссылка',
        'message' => 'Ссылка для подтверждения недействительна, обратитесь в поддержку.',
        'showResend' => false
    ],

    'token_expired' => [
        'title' => 'Ссылка устарела',
        'message' => 'Срок действия ссылки истёк, получите новое письмо для подтверждения.',
        'showResend' => true
    ],

    'server_error' => [
        'title' => 'Ошибка сервера',
        'message' => 'Что-то пошло не так, обновите страницу или обратитесь в поддержку',
        'showResend' => false
    ],
};

$title  = "Подтверждение почты - Gym Boss";
$robots = 'noindex, nofollow';

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . "/../templates/pages/email_verify.php";    // TODO: сделать шаблон страницы результата, по status выбрать содержимое
$content = ob_get_clean();

// Подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';