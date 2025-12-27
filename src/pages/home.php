<?php
// Контроллер главной страницы

// Настройки кэширования (путь куда сохранять и время)
$cacheFile = __DIR__ . '/../../var/cache/categories_products.cache';
$cacheTime = 300; // 5 минут

// Проверяем кэш
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    // Берем данные из кэша
    $categoriesWithProducts = unserialize(file_get_contents($cacheFile));
} else {
    // Делаем запрос к БД
    if (isset($db) && $db instanceof PDO) {
        $connect = $db; // Используем существующее соединение
    } else {
        $connect = getDB(); // Создаем новое
    }

    $sql = "
        SELECT 
            ctg.category_id as ctg_id,
            ctg.name as ctg_name,
            prdct.product_id as prdct_id,
            prdct.slug as prdct_slug,
            prdct.name as prdct_name,
            prdct.price as prdct_price,
            img.image_id as img_id,
            img.image_path as img_path
        FROM categories ctg
        INNER JOIN products prdct ON ctg.category_id = prdct.category_id
        LEFT JOIN product_images img ON prdct.product_id = img.product_id
            AND img.image_id = (
                SELECT MIN(img2.image_id) 
                FROM product_images img2 
                WHERE img2.product_id = prdct.product_id
            )
        ORDER BY ctg.name, prdct.name
    ";

    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $categoriesWithProducts = [];
    
    while ($row = $result->fetch_assoc()) {
        $categoryId = $row['ctg_id'];
        
        if (!isset($categoriesWithProducts[$categoryId])) {
            $categoriesWithProducts[$categoryId] = [
                'category' => [
                    'id' => $categoryId,
                    'name' => $row['ctg_name']
                ],
                'products' => []
            ];
        }
        
        $categoriesWithProducts[$categoryId]['products'][] = [
            'id' => $row['prdct_id'],
            'slug' => $row['prdct_slug'],
            'name' => $row['prdct_name'],
            'price' => $row['prdct_price'],
            'image_path' => !empty($row['img_path']) ?$row['img_path'] : '/img/default.png'
        ];
    }
    
    $categoriesWithProducts = array_values($categoriesWithProducts);
    
    // Сохраняем в кэш
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    file_put_contents($cacheFile, serialize($categoriesWithProducts));
}

if (function_exists('getCartSessionId')) {
    getCartSessionId();
}

$title     = 'Gym Boss - спорттовары';
$robots    = 'index,follow';
$canonical = $baseUrl . '/'; // добавляем "/" чтобы получить каноникал главной страницы как https://gymboss.ru/
// Какие js нужны этой странице (если не нужны не указываем)
// $pageScripts = ['/js/home.js']; - тут не нужен, для примера

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/home.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
?>
