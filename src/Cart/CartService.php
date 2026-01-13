<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Cart;

// Класс для управления корзинами пользователей
class CartService {
    private \mysqli $db;    // приватное свойство (переменная класса), привязанная к объекту

    // Конструктор (магический метод), просто присваиваем внешюю $db в переменную создоваемого объекта
    public function __construct(\mysqli $db) {
        $this->db = $db;
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
    public function getItemsTotal(int $cartId): int {
        $sql = "
            SELECT COALESCE(SUM(ci.amount * p.price), 0) AS total
            FROM cart_items AS ci
            JOIN products AS p ON ci.product_id = p.product_id
            WHERE ci.cart_id = ?
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

        return (int)($row['total'] ?? 0);
    }

    // Метод для получения всех товаров в корзине (с первой фотографией)
    public function getItems(int $cartId): array {
        // Этот запрос возвращает по строчке на каждую позицию в корзине:
        // product_id, slug, name, price, amount, image_path (первое по id фото, главное)
        $sql = "
            SELECT
                p.product_id,
                p.slug,
                p.name,
                p.price,
                ci.amount,
                COALESCE((
                    SELECT pi.image_path
                    FROM product_images AS pi
                    WHERE pi.product_id = p.product_id
                    ORDER BY pi.image_id ASC
                    LIMIT 1
                ), '/img/default.png') AS image_path
            FROM cart_items AS ci
            JOIN products AS p ON ci.product_id = p.product_id
            WHERE ci.cart_id = ?
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

        // Объявляем и заполняем массив товаров в корзине ($items)
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();

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
    public function clearCart(int $cartId): void {
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
}