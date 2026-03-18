// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.
// Всегда возвращается объект с полями: { success: true, data: { items, count, total } }

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const PRODUCT_BASE_URL = "/api/products";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestProduct(path, options = {}) {
    return requestApi(PRODUCT_BASE_URL, path, options);
}

// Функция для получения каталога
// Отправляет запрос GET /api/products
export function getCatalog() {
    return requestProduct("", {
        method: "GET",
    });
}

// Функция для получения товара по slug
// Отправляет запрос GET /api/products/{slug}
export function getBySlug(slug) {
    // Защищаем от пробелов и странных символов в slug.
    const safeSlug = encodeURIComponent(slug);

    return requestProduct(`/${safeSlug}`, {
        method: "GET",
    });
}

// Функция для получения каталога
// Отправляет запрос GET /api/products/search?q={query}
export async function searchProducts(query) {
    let q = query.trim();

    if (!q) {
        return []; // сразу «успешный» промис с пустым массивом
    }

    // Ограничиваем длину строки
    if (q.length > 150) {
        q = q.slice(0, 150);
    }

    // URLSearchParams правильно собирает query‑строку и кодирует спецсимволы
    q = new URLSearchParams({ q }).toString();

    const products = requestProduct(`/search?${q}`, {
        method: "GET",
    });

    return products;
}
