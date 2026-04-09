<?php
declare(strict_types=1);

// Контроллер страницы товара

// Получаем данные о товаре (потом вынести в сервис и контроллеры)

// Получаем slug товара из URL
if (!isset($_GET['url'])) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$productSlug = (string)$_GET['url'];

// Получаем данные товара через сервис
$product = null;
$images = [];
$productDescriptionHtml = '';

try {
    // Данные о товаре
    $product = $productService->getBySlug($productSlug);

    if ($product === null) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

    // Форматируем описание
    $productDescription = $product['description'] ?? 'Описание отсутствует';
    $paragraphs = preg_split("/\R{2,}/u", $productDescription) ?: [];

    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') {
            continue;
        }

        $safe = htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safe = nl2br($safe);

        $productDescriptionHtml .= "<p>{$safe}</p>";   // получаем отформатированное описание
    }

    // Картинки
    $images = $productService->getImagesById((int)$product['id']);
    
} catch (\Throwable $e) {
    $logger->error('Failed to get product info', [
        'exception' => $e
    ]);
    
    http_response_code(500);
    require __DIR__ . '/500.php';
    exit;
}

$productName = $product['name'] ?? 'Товар';

$title  = "$productName - Gym Boss";
$canonical = $baseUrl . '/products/' . rawurlencode($productSlug);
$pageModuleScripts = ['/assets/js/products/product.page.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/product.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
