<?php
declare(strict_types=1);

namespace App\Products;

// Класс-репозиторий для взаимодействия с бд
class ProductRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для получения всего каталога
    public function findCatalog(): array {
        $sql = "
            SELECT 
                ctg.id as ctg_id,
                ctg.name as ctg_name,
                prdct.id as prdct_id,
                prdct.slug as prdct_slug,
                prdct.name as prdct_name,
                prdct.price as prdct_price,
                img.id as img_id,
                img.image_path as image_path
            FROM categories ctg
            INNER JOIN products prdct ON ctg.id = prdct.category_id
            LEFT JOIN product_images img ON prdct.id = img.product_id
                AND img.id = (
                    SELECT MIN(img2.id) 
                    FROM product_images img2 
                    WHERE img2.product_id = prdct.id
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
                'image_path' => !empty($row['image_path']) ? $row['image_path'] : '/assets/images/products/default.webp'
            ];
        }
  
        $stmt->close();
    
        // Превращаем ключи категорий в обычный список
        $catalog = array_values($catalog);

        return $catalog;
    }

    // Метод для нахожения товара по slug
    public function findBySlug(string $slug): ?array  {
        $sql = "
            SELECT
                id,
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

    // Метод для нахожения товара по id
    public function findById(int $id): ?array  {
        $sql = "
            SELECT
                id,
                category_id,
                slug,
                name,
                price,
                vat_code,
                description
            FROM products
            WHERE id = ?
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

    // Метод для нахожения множества товаров по массиву ids
    public function findByIds(array $ids): array  {
        // Строим плейсхолдеры ?,?,?, ...
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                p.id,
                p.category_id,
                p.slug,
                p.name,
                p.price,
                p.vat_code,
                p.description,
                img.image_path
            FROM products p
            LEFT JOIN product_images img 
                ON p.id = img.product_id
                AND img.id = (
                    SELECT MIN(img2.id)
                    FROM product_images img2
                    WHERE img2.product_id = p.id
                )
            WHERE p.id IN ($placeholders)
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
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['image_path'] = $row['image_path'] ?: '/assets/images/products/default.webp';    // если нет картинки - дефолтную
            $products[(int)$row['id']] = $row;
        }
    
        $stmt->close();
    
        return $products;
    }

    // Метод для нахожения множества цен товаров по массиву ids
    public function findPricesByIds(array $ids): array  {
        // Строим плейсхолдеры ?,?,?, ...
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "
            SELECT 
                id,
                price
            FROM products
            WHERE products.id IN ($placeholders)
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
        // Сразу получается ассоциативный массив, где ключи - это id продуктов
        $prices = [];
        while ($row = $result->fetch_assoc()) {
            $prices[(int)$row['id']] = $row['price'];
        }
    
        $stmt->close();
    
        return $prices;
    }

    // Метод для нахожения картинок для товара по его id
    public function findImagesById(int $id): array  {
        $sql = "
            SELECT image_path 
            FROM product_images 
            WHERE product_id = ? 
            ORDER BY id ASC
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

        //  Объявляем и заполняем массив с картинками
        $images = [];

        while ($row = $result->fetch_assoc()) {
            $images[] = $row['image_path'];
        }

        $stmt->close();

        return $images;
    }

    // Метод для нахожения картинок для товара по его id
    public function search(array $words): array {
        $conditions = [];
        $params = [];
        $types = '';

        // Условия и параметры каждого слова для запроса
        foreach ($words as $word) {
            $baseWord = (mb_strlen($word) >= 5) ? mb_substr($word, 0, -2) : $word;
            $conditions[] = "(prdct.name LIKE ? OR prdct.name LIKE ? OR prdct.description LIKE ? OR prdct.description LIKE ?)";
            $params[] = '%' . $word . '%';
            $params[] = '%' . $baseWord . '%';
            $params[] = '%' . $word . '%';
            $params[] = '%' . $baseWord . '%';
            $types .= 'ssss';
        }

        // Нет подходящих условий - возвращаем пустой массив
        if ($conditions === []) {
            return [];
        }

        // Ищем в бд схожие названия
        $sql = "
            SELECT 
                prdct.id as prdct_id,
                prdct.slug as prdct_slug,
                prdct.name as prdct_name,
                prdct.price as prdct_price,
                prdct.description as prdct_description,
                img.id as img_id,
                img.image_path as img_path,
                (CASE
                    WHEN prdct.name LIKE ? THEN 40
                    WHEN prdct.name LIKE ? THEN 30
                    WHEN prdct.name LIKE ? THEN 20
                    WHEN prdct.description LIKE ? THEN 1
                    ELSE 0
                END) as relevance
            FROM products prdct
            LEFT JOIN product_images img ON prdct.id = img.product_id
                AND img.id = (
                    SELECT MIN(img2.id) 
                    FROM product_images img2 
                    WHERE img2.product_id = prdct.id
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
            return [];
        }
            
        // Объявляем и заполняем массив полученных товаров
        $queryProducts = [];
        while ($row = $result->fetch_assoc()) {
            $queryProducts[] = [
                'id' => (int)$row['prdct_id'],
                'slug' => $row['prdct_slug'],
                'name' => $row['prdct_name'],
                'price' => $row['prdct_price'],
                'image_path' => !empty($row['img_path']) ? $row['img_path'] : '/assets/images/products/default.webp',
            ];
        }

        $stmt->close();

        return $queryProducts;
    }
}