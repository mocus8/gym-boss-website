<?php
declare(strict_types=1);

return [
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'log_file' => __DIR__ . '/../' . ($_ENV['LOG_FILE'] ?? 'storage/logs/app.log'),
    'log_level' => $_ENV['LOG_LEVEL'] ?? 'warning',
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
];
