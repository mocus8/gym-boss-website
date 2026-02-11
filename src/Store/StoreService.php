<?php
// Класс-сервис для взаимодействия с бд, будет использовать в контроллерах и других файлах
// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах
// В этих файлах перед вызовом этих методов нужно валидировать данные

// Настриваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Store;

// Класс для получения инфы о магазинах
class StoreService {
    private \mysqli $db;    // приватное свойство (переменная класса), привязанная к объекту

    // Конструктор (магический метод), просто присваиваем внешюю $db в переменную создоваемого объекта
    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Получение всех магазинов
    public function getAll(): array {
        $sql = "SELECT * FROM stores";

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

        // stores - ассоциативный массив с инфой о магазинах
        $stores = [];
        while ($row = $result->fetch_assoc()) {
            // В результатах преобразовываем типы широты и долготы из string в float
            $row['latitude'] = isset($row['latitude'])  ? (float)$row['latitude']  : null;
            $row['longitude'] = isset($row['longitude'])  ? (float)$row['longitude']  : null;

            $stores[] = $row;
        }

        $stmt->close();

        return $stores;
    }

    // Получение магазина по id
    public function getById(int $id): ?array {
        $sql = "SELECT * FROM stores WHERE id = ?";

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

        $store = $result->fetch_assoc();
        $stmt->close();

        // Если ничего не нашли - возвращаем null
        if (!$store) {
            return null;
        }

        return $store;
    }
}
