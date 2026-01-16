<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Product;

// Класс для получения инфы о товарах
class ProductService {
    private \mysqli $db;    // приватное свойство (переменная класса), привязанная к объекту
    // Константы класса (не свойства, вызываются через self::)
    private const CATALOG_CACHE_FILE = __DIR__ . '/../../var/cache/catalog.cache';    // путь к файлу с кэшем каталога
    private const CATALOG_CACHE_TTL  = 300;    // время кэширования (ttl - time to live)

    // Конструктор (магический метод), просто присваиваем внешюю $db в переменную создоваемого объекта
    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Методы которые еще нужно реализовать:
    // getBySlug
    // getById
    // getByIds
    // getPricesByIds
    // search

    // Получение из бд всех категорий с товарами (для отображения каталога), либо его получение из кэша
    public function getCatalog(): array {
        $cacheFile = self::CATALOG_CACHE_FILE;
        $cacheTime = self::CATALOG_CACHE_TTL;

        // Попытка прочитать кеш
        if (is_file($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if ($mtime !== false && (time() - $mtime) < $cacheTime) {
                $raw = file_get_contents($cacheFile);
                if ($raw !== false) {
                    $data = @unserialize($raw);    // @ - оператор подавления ошибки (не упадет в случае ошибки)
                    if (is_array($data)) {
                        return $data;
                    }
                }
            }
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

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $result = $stmt->get_result();

        if (!$result) {
            $stmt->close();
            throw new \RuntimeException('DB get_result failed: ' . $this->db->error);
        }

        // Объявляем и заполняем удобный массив каталога
        $catalog = [];

        while ($row = $result->fetch_assoc()) {
            $categoryId = $row['ctg_id'];
            
            if (!isset($catalog[$categoryId])) {
                $catalog[$categoryId] = [
                    'category' => [
                        'id' => $categoryId,
                        'name' => $row['ctg_name']
                    ],
                    'products' => []
                ];
            }
            
            $catalog[$categoryId]['products'][] = [
                'id' => (int)$row['prdct_id'],
                'slug' => $row['prdct_slug'],
                'name' => $row['prdct_name'],
                'price' => $row['prdct_price'],
                'image_path' => !empty($row['img_path']) ? $row['img_path'] : '/img/default.png'
            ];
        }
    
        // Превращаем ключи категорий в обычный список
        $catalog = array_values($catalog);

        $stmt->close();

        // Сохраняем в кэш (если не получится — просто пропускаем)
        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0755, true);
        }
        @file_put_contents($cacheFile, serialize($catalog));

        return $catalog;
    }
}