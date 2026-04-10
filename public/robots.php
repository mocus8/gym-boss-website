<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

header('Content-Type: text/plain; charset=UTF-8');

// Если из bootatrap не пришел baseUrl
if (!$baseUrl) {
    $logger->error('BaseUrl is not set');    // логируем
    exit(1);    // и падаем
}

echo "User-agent: *\n";
echo "Disallow: /api/\n";
echo "Disallow: /auth\n";
echo "Disallow: /account\n";
echo "Disallow: /cart\n";
echo "Disallow: /checkout\n";
echo "Disallow: /dadata\n";
echo "Disallow: /404\n\n";

echo "Sitemap: {$baseUrl}/sitemap.xml\n";