<?php
declare(strict_types=1);

// Генератор sitemap.xml

require_once __DIR__ . '/../bootstrap/app.php';

// Если из bootatrap не пришел baseUrl
if (!$baseUrl) {
    $logger->error('BaseUrl is not set');    // логируем
    exit(1);    // и падаем
}

// lastmod для статичных страниц не делаю
$urls = [
    ['loc' => $baseUrl . '/'],
    ['loc' => $baseUrl . '/contacts'],
    ['loc' => $baseUrl . '/about'],
    ['loc' => $baseUrl . '/privacy'],
    ['loc' => $baseUrl . '/stores']
];

// Попробуем подключиться к БД и добавить страницы товаров
try {
    $stmt = $db->prepare("
        SELECT
            slug,
            COALESCE(updated_at, created_at) AS changed
        FROM products
        WHERE slug IS NOT NULL
    ");

    if (!$stmt) {
        throw new \RuntimeException('Sitemap query prepare failed: ' . $db->error);
    }

    if (!$stmt->execute()) {
        throw new \RuntimeException('Sitemap query execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if (!$result) {
        throw new \RuntimeException('Sitemap get_result failed: ' . $stmt->error);
    }

    while ($row = $result->fetch_assoc()) {
        $slug = $row['slug'];
        $lastmod = null;

        if (!empty($row['changed'])) {
            $dt = new \DateTimeImmutable($row['changed'], new \DateTimeZone('UTC'));
            $lastmod = $dt->format('Y-m-d');
        }

        // Создание базовой записи для URL
        $entry = [
            'loc' => $baseUrl . '/products/' . rawurlencode($slug),
        ];

        // Если есть дата изменения, добавляем её
        if ($lastmod !== null) {
            $entry['lastmod'] = $lastmod;
        }

        $urls[] = $entry;
    }

} catch (\Throwable $e) {
    // Не критично для генерации sitemap — просто логируем
    $logger->error('Sitemap generator DB error', [
        'db_error'   => $e->getMessage()
    ]);

} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $u) {
    $xml .= "    <url>\n";
    $xml .= '        <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    if (!empty($u['lastmod'])) {
        $xml .= '        <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    }
    $xml .= "    </url>\n";
}

$xml .= '</urlset>' . "\n";
$outPath = __DIR__ . '/../public/sitemap.xml';

if (file_put_contents($outPath, $xml) === false) {
    $logger->error('Failed to write sitemap file', [
        'path' => $outPath,
    ]);
    fwrite(STDERR, "Failed to write sitemap to {$outPath}\n");
    exit(1);
}

echo "sitemap.xml generated: {$outPath}\n";
