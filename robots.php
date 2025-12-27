<?php

require_once __DIR__ . '/src/envLoader.php';

// Получаем URL сайта из переменных окружения
$appUrl = getenv('APP_URL');
if (!$appUrl) {
    // логируем
    error_log('Sitemap generator error: APP_URL is not set');
    // и падаем
    exit(1);
}

$baseUrl   = rtrim($appUrl, '/');

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Disallow: /404\n";
echo "Disallow: /cart\n";
echo "Disallow: /my-orders\n";
echo "Disallow: /order-making\n";
echo "Disallow: /db\n";
echo "Disallow: /scripts\n";
echo "Disallow: /src\n";
echo "Disallow: /src/templates\n\n";

echo "Sitemap: {$baseUrl}/sitemap.xml\n";