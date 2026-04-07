<?php
// Контроллер для подгрузки инфы о товарах (API)
// Принимает запросы, взаимодействует с бд через методы сервиса и отвечает
// Его методы - отдельные API‑эндпоинты

namespace App\Api;

use App\Products\ProductService;
use App\Support\Logger;

// Класс для подгрузки инфы о товарах (через методы сервиса)
class ProductController extends BaseController {
    private ProductService $productService;    // приватное свойство (переменная класса), привязанная к объекту

    // Конструктор (магический метод), присваиваем внеший экземпляр ProductService в переменные создоваемого объекта
    public function __construct(ProductService $productService, Logger $logger) {
        $this->productService = $productService;
        parent::__construct($logger);
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

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get catalog',
                context: [
                    'exception' => $e,
                ]
            );
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

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to get product by slug',
                context: [
                    'exception' => $e,
                ]
            );
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

            // Возвращаем ошибку и логируем через приватную функцию (параметры по умолчанию)
            $this->error(
                message: 'Failed to search product',
                context: [
                    'exception' => $e,
                ]
            );
        }
    }
}