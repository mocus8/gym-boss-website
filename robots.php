<?php

require_once __DIR__ . '/src/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

// Если из bootatrap не пришла appUrl или baseUrl
if (!$appUrl || !$baseUrl) {
    // логируем
    error_log('[robots.php] AppUrl or baseUrl is not set');
    // и падаем
    exit(1);
}

header('Content-Type: text/plain; charset=UTF-8');

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