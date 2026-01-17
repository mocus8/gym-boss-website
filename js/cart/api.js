// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.
// Всегда возвращается объект с полями: { success: true, data: { items, count, total } }

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const CART_BASE_URL = "/api/cart/";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestCart(path, options = {}) {
    return requestApi(CART_BASE_URL, path, options);
}

// Функция для получения корзины
// Отправляет запрос GET /api/cart
export function getCart() {
    return requestCart("", {
        method: "GET",
    });
}

// Функция для добавления опр-ого кол-ва товара в корзину
// Отправляет запрос POST /api/cart/add-item
// JSON.stringify({...}) - превращает объект в JSON‑строку
// Nubmer - приводит строку к числу
export function addCartItem(productId, qty = 1) {
    return requestCart("add-item", {
        method: "POST",
        body: JSON.stringify({
            product_id: Number(productId),
            qty: Number(qty),
        }),
    });
}

// Функция для удаления товара из корзины
// Отправляет запрос POST /api/cart/remove-item
export function removeCartItem(productId) {
    return requestCart("remove-item", {
        method: "POST",
        body: JSON.stringify({
            product_id: Number(productId),
        }),
    });
}

// Функция для жесткого установления кол-ва товара в корзине
// Если qty = 0 — на бэке товар убирается
// Отправляет запрос POST /api/cart/update-item-qty
export function updateCartItemQty(productId, qty) {
    return requestCart("update-item-qty", {
        method: "POST",
        body: JSON.stringify({
            product_id: Number(productId),
            qty: Number(qty),
        }),
    });
}

// Функция для очистки корзины
// Отправляет запрос POST /api/cart/clear
export function clearCart() {
    return requestCart("clear", {
        method: "POST",
    });
}
