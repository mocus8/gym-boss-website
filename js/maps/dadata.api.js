// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const API_BASE_URL = "/api/";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestDadata(path, options = {}) {
    return requestApi(API_BASE_URL, path, options);
}

// Функция для получения подсказок
// Отправляет запрос POST /api/dadata/suggest/address
export function suggestAddress(query, count = 5, options = {}) {
    const q = query == null ? "" : String(query).trim();

    return requestDadata("dadata/suggest/address", {
        method: "POST",
        body: JSON.stringify({
            query: q,
            count: Number(count),
        }),
        ...options,
    });
}
