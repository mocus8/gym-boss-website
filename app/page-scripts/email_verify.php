<?php
declare(strict_types=1);

// Контроллер страницы подтверждения почты 

// Получаем сырой токен подтверждения из query-параметра
$rawToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

// По умолчанию считаем, что токен невалиден
$status = 'token_invalid';

// Вызываем верификацию почты по токену
try {
    $authService->verify($rawToken);

    // Защищаем от фиксаций на сессии по регенерации id
    $authSession->regenerateId();

    $status = 'success';

} catch (\App\Support\AppException $e) {
    $logger->warning('Email verification failed', [
        'exception' => $e,
    ]);

    // Переводим ошибку сервиса в статус
    $status = match ($e->getErrorCode()) {
        'TOKEN_INVALID' => 'token_invalid',
        'EMAIL_ALREADY_VERIFIED' => 'already_verified',
        'TOKEN_EXPIRED' => 'token_expired',
        default => 'token_invalid',
    };

} catch (\Throwable $e) {
    $logger->error('Unexpected error during email verification', [
        'exception' => $e,
    ]);

    $status = 'server_error';
}

// По полученному статусу определяем контент страницы (матчим по статусу заголовок и сообщение)
$pageData = match ($status) {
    'success' => [
        'title' => 'Почта подтверждена',
        'message' => 'Ваш email успешно подтверждён.',
        'show_resend' => false
    ],

    'already_verified' => [
        'title' => 'Почта уже подтверждена',
        'message' => 'Ваш email уже был подтверждён ранее.',
        'show_resend' => false
    ],

    'token_invalid' => [
        'title' => 'Недействительная ссылка',
        'message' => 'Ссылка для подтверждения недействительна, обратитесь в поддержку.',
        'show_resend' => false
    ],

    'token_expired' => [
        'title' => 'Ссылка устарела',
        'message' => 'Срок действия ссылки истёк, получите новое письмо для подтверждения.',
        'show_resend' => true
    ],

    'server_error' => [
        'title' => 'Ошибка сервера',
        'message' => 'Что-то пошло не так, обновите страницу или обратитесь в поддержку',
        'show_resend' => false
    ],
};

// Смотрим, залогинен ли пользователь 
$isAuthenticated = $currentUser !== null;

$title  = "Подтверждение почты - Gym Boss";
$robots = 'noindex, nofollow';
$pageModuleScripts = ['/assets/js/users/auth/email-verify.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . "/../templates/pages/email_verify.php";
$content = ob_get_clean();

// Подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';