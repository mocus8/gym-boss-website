<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Cart;
// Используем класс ProductService из пространства имен App\Product
use App\Product\ProductService;

// Класс для управления корзинами пользователей
class CartService {
    // Приватное свойство (переменная класса), привязанная к объекту
    private \mysqli $db;
    private ProductService $productService;    // экземпляр сервиса для товаров (dependency injection)

    // Конструктор (магический метод), просто присваиваем внешние переменные в переменную создоваемого объекта
    public function __construct(\mysqli $db, ProductService $productService) {
        $this->db = $db;
        $this->productService = $productService;
    }

    // Поиск корзины по cart_session_id или user_id, нашли - возвращаем ее $cartId, нет - создаем и возвращаем $cartId новой
    public function getOrCreateCartId(string $cartSessionId, ?int $userId): int {
        // Проверяем аргументы 
        if ($userId === null && $cartSessionId === '') {
            throw new \InvalidArgumentException('Empty cartSessionId and userId');
        }

        // Выбираем выражение исходя исходя из значения аргументов
        if ($userId) {
            $sql = "
                SELECT id
                FROM carts
                WHERE user_id = ? AND is_converted = 0
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param("i", $userId);
        } else {
            $sql = "
                SELECT id
                FROM carts
                WHERE session_id = ? AND is_converted = 0
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("s", $cartSessionId);
        }

        // Выполняем
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

        $row = $result->fetch_assoc();

        $stmt->close();

        // Если строчка нашлась - возвращаем,
        if ($row) {
            return (int)$row['id'];
        }

        // Не нашли - заносим в таблицу новую строку
        if ($userId) {
            $sql = "
                INSERT INTO carts (user_id, session_id)
                VALUES (?, ?)
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("is", $userId, $cartSessionId);
        } else {
            $sql = "
                INSERT INTO carts (session_id)
                VALUES (?)
            ";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
            }

            $stmt->bind_param("s", $cartSessionId);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        // Получаем id как последний вставленный в бд и возвращаем его
        $cartId = $this->db->insert_id;
        return $cartId;
    }

    // Метод для привязки гостевой корзины к пользователю (по cart_session_id и user_id)
    public function attachGuestCartToUser(string $cartSessionId, int $userId) {
        // Проверяем аргументы 
        if ($userId <= 0 || $cartSessionId === '') {
            throw new \InvalidArgumentException('Empty cartSessionId or invalid userId');
        }

        $sql = "
            SELECT id
            FROM carts
            WHERE session_id = ? AND user_id IS NULL AND is_converted = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("s", $cartSessionId);

        // Выполняем
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

        $row = $result->fetch_assoc();

        $stmt->close();

        // Если строчка не нашлась - выходим
        if (!$row) return;

        $cartId = (int)$row['id'];

        $sql = "
            UPDATE carts
            SET user_id = ?, session_id = NULL
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("ii", $userId, $cartId);

        // Выполняем
        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }

        $stmt->close();
    }

    // Метод получения кол-ва всех товаров в корзине
    public function getItemsCount(int $cartId): int {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) AS count
            FROM cart_items
            WHERE cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

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
        
        $row = $result->fetch_assoc();

        $stmt->close();

        return (int)($row['count'] ?? 0);
    }

    // Метод для подсчета общей стоимости корзины
    public function getItemsTotal(int $cartId): float {
        $sql = "
            SELECT
                product_id,
                amount
            FROM cart_items
            WHERE cart_items.cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

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

        $items = [];
        $productIds = [];

        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $amount    = (int)$row['amount'];

            $items[$productId] = $amount;
            $productIds[] = $productId;
        }
        
        $stmt->close();

        if ($items === []) {
            return 0;
        }

        $prices = $this->productService->getPricesByIds($productIds);

        // Объявляем переменную с общей суммой и заполняем
        $total = 0;
        foreach ($items as $productId => $amount) {
            // Если цены для товара нет то пропускаем итерацию
            if (!isset($prices[$productId])) {
                continue;
            }

            $total += (float)$prices[$productId] * $amount;
        }

        return (float)$total;
    }

    // Метод для получения всех товаров в корзине (с первой фотографией)
    public function getItems(int $cartId): array {
        $sql = "
            SELECT 
                product_id,
                amount
            FROM cart_items
            WHERE cart_items.cart_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param("i", $cartId);

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

        // Объявляем и заполняем массивы id => amount и id товаров из корзины
        $amountByIds = [];
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $productId = (int)$row['product_id'];
            $amount = (int)$row['amount'];

            $amountByIds[$productId] = $amount;
            $ids[] = $productId;
        }

        if ($ids === []) {
            $stmt->close();
            return [];
        }

        $stmt->close();

        // Получаем массив товаров через productService
        $products = $this->productService->getByIds($ids);

        // Собираем итоговый массив позиций корзины: товар + amount
        $items = [];
        foreach ($products as $productId => $product) {
            // Прибавляем к элементу product массива products поле, и все это вместе кладем в items
            $items[] = array_merge($product, ['amount' => $amountByIds[$productId] ?? 0]);
        }

        return $items;
    }

    // Метод для добавления определенного кол-ва (qty) товара в корзину / прибавление кол-ва к сущ-ему товару
    public function addItem(int $cartId, int $productId, int $qty): void {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    
        // Запрос вставляет в таблицу строку, либо увел-ает кол-во товара (если уже в корзине)
        $sql = "
            INSERT INTO cart_items (cart_id, product_id, amount)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iii', $cartId, $productId, $qty);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для удаления товара из корзины
    public function removeItem(int $cartId, int $productId): void {
        $sql = "
            DELETE FROM cart_items
            WHERE cart_id = ? AND product_id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('ii', $cartId, $productId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для жёсткого установления нового количества товара в корзине
    public function updateItemQty(int $cartId, int $productId, int $qty): void {
        if ($qty < 0) {
            throw new \InvalidArgumentException('Quantity must be >= 0');
        }

        if ($qty === 0) {
            $this->removeItem($cartId, $productId);
            return;
        }

        $sql = "
            UPDATE cart_items
            SET amount = ?
            WHERE cart_id = ? AND product_id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('iii', $qty, $cartId, $productId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для очистки корзины
    public function clear(int $cartId): void {
        $sql = "
            DELETE FROM cart_items
            WHERE cart_id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('i', $cartId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }

    // Метод для пометки корзины как конвертированной
    public function convert(int $cartId): void {
        $sql = "
            UPDATE carts
            SET is_converted = 1
            WHERE id = ?
        ";
    
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new \RuntimeException('DB prepare failed: ' . $this->db->error);
        }
    
        $stmt->bind_param('i', $cartId);

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $this->db->error;
            $stmt->close();
            throw new \RuntimeException('DB execute failed: ' . $error);
        }
    
        $stmt->close();
    }
}