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
                img.image_path as image_path
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
                'image_path' => !empty($row['image_path']) ? $row['image_path'] : '/img/default.png'
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

    // Получение товара из бд по slug
    public function getBySlug(string $slug): ?array {
        $sql = "
            SELECT
                product_id,
                category_id,
                slug,
                name,
                price,
                vat_code,
                description
            FROM products
            WHERE slug = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $slug);

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

        $product = $result->fetch_assoc();
        $stmt->close();

        // Если ничего не нашли - возвращаем null
        if (!$product) {
            return null;
        }

        return $product;
    }

    // Получение товара из бд по id
    public function getById(int $id): ?array {
        $sql = "
            SELECT
                product_id,
                category_id,
                slug,
                name,
                price,
                vat_code,
                description
            FROM products
            WHERE product_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $id);

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

        $product = $result->fetch_assoc();
        $stmt->close();

        // Если ничего не нашли - возвращаем null
        if (!$product) {
            return null;
        }

        return $product;
    }

    // Получение товаров из бд по массиву из id
    public function getByIds(array $ids): array {
        // Фильтруем и приводим к int:
        // array_map('intval', $ids): array_map применяет функцию ко всем элементам массива, intval приводит значение к целому числу.
        // array_unique: убирает дубликаты из массива
        // array_values: переформировывает массив так, чтобы ключи стали 0, 1, 2, ... подряд.        
        $ids = array_values(array_unique(array_map('intval', $ids)));
    
        if ($ids === []) {
            return [];
        }

        // Строим плейсхолдеры ?,?,?, ...
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                p.product_id,
                p.category_id,
                p.slug,
                p.name,
                p.price,
                p.vat_code,
                p.description,
                img.image_path
            FROM products p
            LEFT JOIN product_images img 
                ON p.product_id = img.product_id
                AND img.image_id = (
                    SELECT MIN(img2.image_id)
                    FROM product_images img2
                    WHERE img2.product_id = p.product_id
                )
            WHERE p.product_id IN ($placeholders)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        // Строка типов: столько же 'i', сколько id
        $types = str_repeat('i', count($ids));

        // Троеточие - оператор распаковки массива
        $stmt->bind_param($types, ...$ids);

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

        // Объявляем и заполняем массив с найденными товарами
        // Сразу получается ассоциативный массив, где ключи - это product_id
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['image_path'] = $row['image_path'] ?: '/img/default.png';    // если нет картинки - дефолтную
            $products[(int)$row['product_id']] = $row;
        }
    
        $stmt->close();
    
        return $products;
    }

    // Получение цен товаров из бд по массиву из id
    public function getPricesByIds(array $ids): array {
        // Фильтруем и приводим к int:
        // array_map('intval', $ids): array_map применяет функцию ко всем элементам массива, intval приводит значение к целому числу.
        // array_unique: убирает дубликаты из массива
        // array_values: переформировывает массив так, чтобы ключи стали 0, 1, 2, ... подряд.        
        $ids = array_values(array_unique(array_map('intval', $ids)));
    
        if ($ids === []) {
            return [];
        }

        // Строим плейсхолдеры ?,?,?, ...
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                product_id,
                price
            FROM products
            WHERE products.product_id IN ($placeholders)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        // Строка типов: столько же 'i', сколько id
        $types = str_repeat('i', count($ids));

        // Троеточие - оператор распаковки массива
        $stmt->bind_param($types, ...$ids);

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

        // Объявляем и заполняем массив с найденными ценами
        // Сразу получается ассоциативный массив, где ключи - это product_id
        $prices = [];
        while ($row = $result->fetch_assoc()) {
            $prices[(int)$row['product_id']] = $row['price'];
        }
    
        $stmt->close();
    
        return $prices;
    }

    // Получение картинок товара по id
    public function getImagesById(int $productId): array {
        $sql = "
            SELECT image_path 
            FROM product_images 
            WHERE product_id = ? 
            ORDER BY image_id ASC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $productId);

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

        //  Объявляем и заполняем массив с картинками
        $images = [];

        while ($row = $result->fetch_assoc()) {
            $images[] = $row['image_path'];
        }

        $stmt->close();

        // если картинок нет - дефолтная
        if ($images === []) {
            $images[] = '/img/default.png';
        }

        return $images;
    }

    // Поиск товаров по query-выражению
    public function search(string $query): array {
        // Объявляем массив полученных значений
        $queryProducts = [];

        // Обрезаем до 150 символов
        $query = substr($query, 0, 150);

        // Пустой или состоящий из "мусора" query - отправляем пустой массив
        if (trim($query) === '') {
            return $queryProducts;
        }

        // разбиваем на слова для лучшего поиска
        $words = explode(' ', $query);
        $conditions = [];
        $params = [];
        $types = '';

        // условия каждого слова для поиска
        foreach ($words as $word) {
            if (mb_strlen($word) >= 2) {
                $baseWord = (mb_strlen($word) >= 5) ? mb_substr($word, 0, -2) : $word;
                $conditions[] = "(prdct.name LIKE ? OR prdct.name LIKE ? OR prdct.description LIKE ? OR prdct.description LIKE ?)";
                $params[] = '%' . $word . '%';
                $params[] = '%' . $baseWord . '%';
                $params[] = '%' . $word . '%';
                $params[] = '%' . $baseWord . '%';
                $types .= 'ssss';
            }
        }

        // нет подходящих условий - пустой
        if ($conditions === []) {
            return $queryProducts;
        }

        // Ищем в бд схожие названия
        $sql = "
            SELECT 
                prdct.product_id as prdct_id,
                prdct.slug as prdct_slug,
                prdct.name as prdct_name,
                prdct.price as prdct_price,
                prdct.description as prdct_description,
                img.image_id as img_id,
                img.image_path as img_path,
                (CASE
                    WHEN prdct.name LIKE ? THEN 40
                    WHEN prdct.name LIKE ? THEN 30
                    WHEN prdct.name LIKE ? THEN 20
                    WHEN prdct.description LIKE ? THEN 1
                    ELSE 0
                END) as relevance
            FROM products prdct
            LEFT JOIN product_images img ON prdct.product_id = img.product_id
                AND img.image_id = (
                    SELECT MIN(img2.image_id) 
                    FROM product_images img2 
                    WHERE img2.product_id = prdct.product_id
                )
            WHERE " . implode(' OR ', $conditions) . "
            HAVING relevance > 0
            ORDER BY relevance DESC, prdct.name
        ";

        // оформляем параметры
        $caseParams = array_slice($params, 0, 4);
        $params = array_merge($caseParams, $params);
        $types = 'ssss' . $types;

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param($types, ...$params);

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

        if ($result->num_rows == 0) {
            $stmt->close();
            return $queryProducts;
        }
            
        // формируем массив полученных товаров
        while ($row = $result->fetch_assoc()) {
            $queryProducts[] = [
                'id'         => (int)$row['prdct_id'],
                'slug'       => $row['prdct_slug'],
                'name'       => $row['prdct_name'],
                'price'      => $row['prdct_price'],
                'image_path' => !empty($row['img_path']) ? $row['img_path'] : '/img/default.png',
            ];
        }

        $stmt->close();

        return $queryProducts;
    }
}
