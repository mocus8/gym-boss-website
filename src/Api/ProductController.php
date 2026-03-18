<?php
// Контроллер для подгрузки инфы о товарах (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты

// Тут добавить логирование и документацию для этого api

// Настраиваем простанство имен (для будующего, когда буду заменять require_once на composer)
namespace App\Api;

use App\Products\ProductService;    // используем класс ProductService из пространства имен App\Products
// use App\Support\Logger;    // пространство имен для логгера, на будующее

// Класс для подгрузки инфы о товарах (через методы сервиса)
class ProductController {
    private ProductService $productService;    // приватное свойство (переменная класса), привязанная к объекту
    // private Logger $logger;    // Логгер для передачи в зависимость в конструкторе, потом подключить

    // Конструктор (магический метод), присваиваем внеший экземпляр ProductService в переменные создоваемого объекта
    public function __construct(ProductService $productService) {
        $this->productService = $productService;
    }

    // Будущий конструктор (с логером)
    // public function __construct(ProductService $productService, Logger $logger) {
    //     $this->productService = $productService;
    //     $this->logger = $logger;
    // }

    // Приватная функция для отправки успеха
    private function success(int $status = 200, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    // Приватная функция для отправки ошибки
    // Возможно логгер сюда переместить
    private function error(
        int $status = 500,
        string $code = 'INTERNAL_SERVER_ERROR',
        string $message = 'Internal server error'
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    // Метод для получения каталога, возвращает массив - категории, товары в них, инфа о товарах
    // Обработчик запроса GET /api/products
    public function getCatalog(): void {
        try {
            // Получаем каталог через сервис: категории + товары + первую картинку
            $data = $this->productService->getCatalog();

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Product getCatalog  failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для получения товара по slug, использует и объединяет результаты getBySlug и getImagesById сервиса
    // Обработчик запроса GET /api/products/{slug}
    public function getBySlug(string $slug): void {
        try {
            // Получаем инфу о товаре через сервис
            $data = $this->productService->getBySlug($slug);

            if ($data === null) {
                $this->error(404, 'PRODUCT_NOT_FOUND', 'Product not found');
                return;
            }

            // Получаем фотки через сервис
            $images = $this->productService->getImagesById((int)$data['product_id']);

            // Вкладываем картинки внутрь
            $data['images'] = $images;

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Product getBySlug failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }

    // Метод для поиска товара по query-выражению
    // Обработчик запроса GET /api/products/search?q=...
    public function search(string $query): void {
        try {
            // Получаем массив найденых товаров (или пустой)
            $data = $this->productService->search($query);

            // Возвращаем успех через приватную функцию
            $this->success(200, $data);

        } catch (\Throwable $e) {
            // Вместо Exception, Throwable - более обширное, все поймает
            // Ошибка сервера/баг/БД упала - 500 + запись в лог, а пользователю только общий текст.

            // Релизовать во время добавления логирования, также добавить контекст
            // $this->logger->error('Product search failed', [
            //     'exception' => $e,
            // ]);

            // Возвращаем ошибку через приватную функцию (параметры по умолчанию)
            $this->error();
        }
    }
}