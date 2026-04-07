<?php

namespace App\Stores;

// Класс-репозиторий для взаимодействия с бд
class StoreRepository {
    private \mysqli $db;

    public function __construct(\mysqli $db) {
        $this->db = $db;
    }

    // Метод для поиска всех магазинов
    public function findAll(): array {
        $sql = "
            SELECT
                id,
                name,
                address,
                phone,
                work_hours,
                latitude,
                longitude
            FROM stores
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

    // Метод для поиска всех магазинов
    public function findById(int $id): ?array {
        $sql = "
            SELECT
                id,
                name,
                address,
                phone,
                work_hours,
                latitude,
                longitude
            FROM stores
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

        $store = $result->fetch_assoc();
        $stmt->close();

        // Если ничего не нашли - возвращаем null
        if (!$store) {
            return null;
        }

        return $store;
    }
}