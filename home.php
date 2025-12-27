<?php

// Добавляем / чтобы получить каноникал главной страницы как https://gymboss.ru/
$canonical = $baseUrl . '/';

// Настройки кэширования
$cacheFile = __DIR__ . '/public/cache/categories_products.cache';
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
?>


<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>
            Gym Boss - спорттовары
		</title>
        <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
        <link rel="icon" href="/public/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <img class="loader" src="/img/loader.png" alt="Загрузка">
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/templates/partials/header.php'; ?>
            <main class="main">
                <div class="catalog">
                    <?php
                        foreach ($categoriesWithProducts as $categoryData) {
                    ?>
                        <div class="category_row">
                            <div class="category_name">
                                <?= htmlspecialchars($categoryData['category']['name']) ?>
                            </div>
                            <div class="products_main">
                                <?php
                                    foreach ($categoryData['products'] as $ProductData) {
                                ?>
                                    <a href="/product/<?= $ProductData['slug'] ?>">
                                        <div class="product">
                                            <div class="product_click">
                                                <img class="product_img_1" src="<?= $ProductData['image_path'] ?>">
                                                <div class="product_name_1">
                                                    <?= htmlspecialchars($ProductData['name']) ?>
                                                </div>
                                                <div class="product_price_1">
                                                    <?= htmlspecialchars($ProductData['price']) ?> ₽
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php
                                    }
                                ?>
                            </div>
                        </div>
                    <?php
                        }
                    ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/templates/partials/footer.php'; ?>
        </div>
	</body>
</html>