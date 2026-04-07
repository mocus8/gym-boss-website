<?php
declare(strict_types=1);

// Чистая бизнес‑логика, не завязанная на HTTP, JSON, $_POST, echo, будет использовать в контроллерах и других файлах
// Тут просто выбрасываем исключения, ловим их уже в endpoint-ах и других файлах

namespace App\Products;

// Класс для получения инфы о товарах
class ProductService {
    private ProductRepository $productRepository;

    private const CATALOG_CACHE_FILE = __DIR__ . '/../../storage/cache/catalog.cache';    // путь к файлу с кэшем каталога
    private const CATALOG_CACHE_TTL  = 300;    // время кэширования

    public function __construct(ProductRepository $productRepository) {
        $this->productRepository = $productRepository;
    }

    // Получение из бд всех категорий с товарами (для отображения каталога), либо его получение из кэша
    public function getCatalog(): array {
        $cacheFile = self::CATALOG_CACHE_FILE;
        $cacheTime = self::CATALOG_CACHE_TTL;

        // Пытаемся прочитать кеш
        if (is_file($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if ($mtime !== false && (time() - $mtime) < $cacheTime) {
                // Получаем каталог из кеша
                $raw = file_get_contents($cacheFile);
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        // Если не нашлось в кеше - получаем каталог из бд
        $catalog = $this->productRepository->findCatalog();

        // Сохраняем в кэш (если не получится - просто пропускаем)
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, json_encode($catalog));

        return $catalog;
    }

    // Получение товара из бд по slug
    public function getBySlug(string $slug): ?array {
        return $this->productRepository->findBySlug($slug);
    }

    // Получение товара из бд по id
    public function getById(int $id): ?array {
        return $this->productRepository->findById($id);
    }

    // Получение товаров из бд по массиву из id
    public function getByIds(array $ids): array {
        // Фильтруем и приводим к int:
        // array_map('intval', $ids): array_map применяет функцию ко всем элементам массива, intval приводит значение к целому числу
        // array_unique: убирает дубликаты из массива
        // array_values: переформировывает массив так, чтобы ключи стали 0, 1, 2, ... подряд    
        $ids = array_values(array_unique(array_map('intval', $ids)));
    
        if ($ids === []) {
            return [];
        }

        return $this->productRepository->findByIds($ids);
    }

    // Получение цен товаров из бд по массиву из id
    public function getPricesByIds(array $ids): array {
        // Фильтруем и приводим к int:
        // array_map('intval', $ids): array_map применяет функцию ко всем элементам массива, intval приводит значение к целому числу.
        // array_unique: убирает дубликаты из массива
        // array_values: переформировывает массив так, чтобы ключи стали 0, 1, 2, ... подряд.        
        $ids = array_values(array_unique(array_map('intval', $ids)));
    
        if ($ids === []) {
            return [];
        }

        return $this->productRepository->findPricesByIds($ids);
    }

    // Получение картинок товара по id
    public function getImagesById(int $productId): array {
        $images = $this->productRepository->findImagesById($productId);

        // Если картинок нет - дефолтная
        if ($images === []) {
            $images[] = '/img/default.png';
        }

        return $images;
    }

    // Поиск товаров по query-выражению
    public function search(string $query): array {
        // Обрезаем до 150 символов и убираем лишние пробелы
        $query = substr(trim($query), 0, 150);

        if ($query === '') {
            return [];
        }

        // Разбиваем на слова для лучшего поиска и берем только слова с длинной >= 2
        $words = array_filter(
            explode(' ', $query),
            fn($w) => mb_strlen($w) >= 2
        );
    
        if (empty($words)) {
            return [];
        }

        return $this->productRepository->search($words);
    }
}
