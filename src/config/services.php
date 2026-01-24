<?php

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

    'smsc' => [
        'login'    => $_ENV['SMSC_LOGIN'] ?? '',
        'password' => $_ENV['SMSC_PASSWORD'] ?? '',
        'post'     => (int)($_ENV['SMSC_POST'] ?? 0),
        'https'    => (int)($_ENV['SMSC_HTTPS'] ?? 1),
        'charset'  => $_ENV['SMSC_CHARSET'] ?? 'utf-8',
        'debug'    => (int)($_ENV['SMSC_DEBUG'] ?? 0),
        'sender'   => $_ENV['SMSC_SENDER'] ?? '',
        'test'     => ($_ENV['SMSC_TEST_MODE'] ?? 'false') === 'true',
    ],
];
