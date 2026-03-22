<?php
// Контроллер для подгрузки инфы о магазинах (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты

// Тут добавить логирование и документацию для этого api

// Настраиваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Stores\StoreService;    // используем класс StoreService из пространства имен App\Stores

// Класс для подгрузки инфы о магазинах (через методы сервиса)
class StoreController extends BaseController {
    private StoreService $storeService;    // приватное свойство (переменная класса), привязанная к объекту

    // Конструктор (магический метод), присваиваем внеший экземпляр StoreService в переменные создоваемого объекта
    public function __construct(StoreService $storeService) {
        $this->storeService = $storeService;
    }

    // Будущий конструктор (с логером)
    // public function __construct(StoreService $storeService, Logger $logger) {
    //     $this->storeService = $storeService;
    //     parent::__controller($logger);
    // }

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

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getAll  failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для получения магазина по id
    // Обработчик запроса GET /api/stores/{id}
    public function getById(int $id): void {
        try {
            // Получаем инфу о товаре через сервис
            $data = $this->storeService->getById($id);

            if ($data === null) {
                $this->error(404, 'STORE_NOT_FOUND', 'Store not found');
                return;
            }

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Store getById failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }
}