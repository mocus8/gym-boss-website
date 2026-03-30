<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
];
