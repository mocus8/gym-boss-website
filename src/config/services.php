<?php
declare(strict_types=1);

return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '',
        'name' => $_ENV['DB_NAME'] ?? '',
        'user' => $_ENV['DB_USER'] ?? '',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],

    'yookassa' => [
        'shop_id' => $_ENV['YOOKASSA_SHOP_ID']?: '',
        'api_key' => $_ENV['YOOKASSA_API_KEY'] ?: '',
    ],

    'dadata' => [
        'api_key' => $_ENV['DADATA_API_KEY'] ?: '',
    ],

    'yandex_maps' => [
        'key' => $_ENV['YANDEX_MAPS_KEY'] ?: '',
    ],

    'recaptcha' => [
        'site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?: '',
        'secret_key' => $_ENV['GOOGLE_RECAPTCHA_SECRET_KEY'] ?: '',
    ],

    'resend' => [
        'api_key' => $_ENV['RESEND_API_KEY'] ?? '',
        'mail_from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? '',
        'mail_from_name' => ($_ENV['MAIL_FROM_NAME'] ?? ''),
        'mail_reply_to' => ($_ENV['MAIL_REPLY_TO'] ?? ''),
    ],
];
