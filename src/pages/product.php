<?php
// Контроллер страницы товара

// Получаем данные о товаре (потом вынести в сервис и контроллеры)

// Получаем slug товара из URL
$productSlug = $_GET['url'] ?? '';

// Получаем данные товара
$stmt = $db->prepare("SELECT * FROM products WHERE slug = ?");
$stmt->bind_param("s", $productSlug);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit();
}

$productId = $product['product_id'];
$productName = $product['name'];
$productPrice = $product['price'];
$productDescription = $product['description'] ?? 'Описание отсутствует';

// Получаем изображение товара из product_images
$stmt_images = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY image_id ASC");
$stmt_images->bind_param("i", $productId);
$stmt_images->execute();
$images_result = $stmt_images->get_result();
$productImages = [];

if ($images_result && $images_result->num_rows > 0) {
    while ($image = $images_result->fetch_assoc()) {
        $productImages[] = $image['image_path'];
    }
}

// Первое изображение считается главным
if ($productImages) {
    $mainImage = $productImages[0];
} else {
    $mainImage = '/img/default.png';
}

$title  = "$productName - Gym Boss";
$canonical = $baseUrl . "/$productSlug"; 
$pageModuleScripts = ['/js/pages/product.js'];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/product.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
