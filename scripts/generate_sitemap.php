<?php
// Генератор sitemap.xml для учебного проекта
// Запуск: docker-compose exec php php /var/www/html/scripts/generate_sitemap.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/envLoader.php';
require_once __DIR__ . '/../src/helpers.php';

// Получаем URL сайта из переменных окружения
$appUrl = getenv('APP_URL');
if (!$appUrl) {
    // логируем
    error_log('Sitemap generator error: APP_URL is not set');
    // и падаем
    exit(1);
}

$baseUrl   = rtrim($appUrl, '/');

// lastmod для статичных страниц не делаю
$urls = [
    ['loc' => $baseUrl . '/'],
    ['loc' => $baseUrl . '/contacts'],
    ['loc' => $baseUrl . '/kwork-customers'],
    ['loc' => $baseUrl . '/privacy'],
    ['loc' => $baseUrl . '/stores']
];

// Попробуем подключиться к БД и добавить страницы товаров
try {
    $connect = getDB();
    if (!$connect) {
        throw new Exception('connection failed');
    }

    $stmt = $connect->prepare("
        SELECT
            slug,
            COALESCE(updated_at, created_at) AS changed
        FROM products
        WHERE slug IS NOT NULL
    ");

    if (!$stmt) {
        throw new Exception('query failed');
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $slug = $row['slug'];
        $lastmod = null;

        if (!empty($row['changed'])) {
            $ts = strtotime($row['changed']);
            if ($ts !== false) {
                $lastmod = date('Y-m-d', $ts);
            }
        }

        // Создание базовой записи для URL
        $entry = [
            'loc' => $baseUrl . '/product/' . rawurlencode($slug),
        ];

        // Если есть дата изменения, добавляем её
        if ($lastmod !== null) {
            $entry['lastmod'] = $lastmod;
        }

        $urls[] = $entry;
    }

} catch (Exception $e) {
    // Не критично для генерации sitemap — просто логируем
    error_log('Sitemap generator DB error: ' . $e->getMessage());

} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    if (isset($connect) && $connect instanceof mysqli) {
        $connect->close();
    }
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    $xml .= "    <url>\n";
    $xml .= '        <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        $xml .= '        <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    }
    $xml .= "    </url>\n";
}

$xml .= '</urlset>' . "\n";

$outPath = __DIR__ . '/../sitemap.xml';
if (file_put_contents($outPath, $xml) === false) {
    fwrite(STDERR, "Failed to write sitemap to {$outPath}\n");
    exit(1);
}

echo "sitemap.xml generated: {$outPath}\n";
