<?php
// Контроллер страницы товара

$cartSessionId = getCartSessionId();
$idUser = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : '';

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

// Получаем данные корзины
if ($idUser) {
    $stmt = $db->prepare("
    SELECT p.product_id, p.name, p.price, po.amount 
    FROM product_order po 
    JOIN products p ON po.product_id = p.product_id 
    JOIN orders o ON po.order_id = o.order_id
    WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("i", $idUser);
} else {
    $stmt = $db->prepare("
    SELECT p.product_id, p.name, p.price, po.amount 
    FROM product_order po 
    JOIN products p ON po.product_id = p.product_id 
    JOIN orders o ON po.order_id = o.order_id
    WHERE o.session_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("s", $cartSessionId);
}

$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$cartTotalPrice = 0;
$cartCount = 0;

if ($result) {
    while ($item = $result->fetch_assoc()) {
        $cartItems[] = [
            'id' => $item['product_id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'amount' => $item['amount']
        ];
        $cartTotalPrice += $item['price'] * $item['amount'];
        $cartCount += $item['amount'];
    }
}

$inCart = false;
$cartAmount = 0;

foreach ($cartItems as $item) {
    if ($item['id'] == $productId) {
        $inCart = true;
        $cartAmount = $item['amount'];
        break;
    }
}

$title  = "$productName - Gym Boss";
$canonical = $baseUrl . "/$productSlug"; 
$pageScripts = ['/js/cart.js' ];

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/product.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
