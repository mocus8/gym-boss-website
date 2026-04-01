<?php

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

// Если из bootatrap не пришел baseUrl
if (!$baseUrl) {
    $logger->error('BaseUrl is not set');    // логируем
    exit(1);    // и падаем
}

echo "User-agent: *\n";
echo "Disallow: /404\n";
echo "Disallow: /cart\n";
echo "Disallow: /orders\n";
echo "Disallow: /checkout\n";
echo "Disallow: /db\n";
echo "Disallow: /scripts\n";
echo "Disallow: /src\n";
echo "Disallow: /src/templates\n\n";

echo "Sitemap: {$baseUrl}/sitemap.xml\n";