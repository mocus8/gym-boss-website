<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Stores;

// Класс для получения инфы о магазинах
class StoreService {
    private StoreRepository $storeRepository;

    public function __construct(StoreRepository $storeRepository) {
        $this->storeRepository = $storeRepository;
    }

    // Получение всех магазинов
    public function getAll(): array {
        return $this->storeRepository->findAll();
    }

    // Получение магазина по id
    public function getById(int $id): ?array {
        return $this->storeRepository->findById($id);
    }
}
