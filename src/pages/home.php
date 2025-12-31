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

    $stmt = $db->prepare($sql);
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

// Также с примером того что может быть в контроллере:
// $title  = 'Gym Boss - спорттовары'; - тут тайтл по умолчанию в app.php, не указваем
// $robots = 'noindex,nofollow'; - индексируется по умолчанию app.php, не указваем
$canonical = $baseUrl . '/'; // добавляем "/" чтобы получить каноникал главной страницы как https://gymboss.ru/
// $pageScripts = [
// '/js/cart.js',
// 'https://api-maps.yandex.ru/2.1/?apikey=' . urlencode(getenv('YANDEX_MAPS_KEY')) . '&lang=ru_RU&load=package.full'
// ]; - какие внешние и обычные скрипты (без import/export, например просто обработчики) нужны для страницы, тут не нужны - не указываем
// $pageModuleScripts = ['/js/cart.js']; - какие модульные (есть import/export) скрипты нужны для страницы, тут не нужны - не указываем

// Через буфер записываем в переменную контент страницы
ob_start();
require __DIR__ . '/../templates/pages/home.php';
$content = ob_get_clean();

// И подключаем главный шаблон сайта
require __DIR__ . '/../templates/layouts/app.php';
