// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const STORE_BASE_URL = "/api/stores";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestStore(path, options = {}) {
    return requestApi(STORE_BASE_URL, path, options);
}

// Функция для получения всех магазинов
// Отправляет запрос GET /api/stores
export function getStores() {
    return requestStore("", {
        method: "GET",
    });
}

// Функция для получения магазина по id
// Отправляет запрос GET /api/stores/{id}
export function getStoreById(storeId) {
    const id = Number(storeId);

    return requestStore(`/${id}`, {
        method: "GET",
    });
}
