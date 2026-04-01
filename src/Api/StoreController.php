<?php
// Контроллер для подгрузки инфы о магазинах (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты

namespace App\Api;

use App\Stores\StoreService;
use App\Support\Logger;

// Класс для подгрузки инфы о магазинах (через методы сервиса)
class StoreController extends BaseController {
    private StoreService $storeService;

    // Конструктор (магический метод), присваиваем внеший экземпляр StoreService в переменные создоваемого объекта
    public function __construct(StoreService $storeService, Logger $logger) {
        $this->storeService = $storeService;
        parent::__construct($logger);
    }

    // Метод для получения всех магазинов
    // Обработчик запроса GET /api/stores
    public function getAll(): void {
        try {
            $data = $this->storeService->getAll();

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get all stores',
                context: [
                    'exception' => $e,
                ]
            );
        }
    }

    // Метод для получения магазина по id
    // Обработчик запроса GET /api/stores/{id}
    public function getById(int $id): void {
        try {
            // Получаем инфу о товаре через сервис
            $data = $this->storeService->getById($id);

            if ($data === null) {
                $this->error(
                    404,
                    'STORE_NOT_FOUND',
                    'Store not found for store id {store_id}',
                    context: [
                        'store_id' => $id
                    ]
                );

                return;
            }

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get store by id {store_id}',
                context: [
                    'store_id' => $id,
                    'exception' => $e,
                ]
            );
        }
    }
}