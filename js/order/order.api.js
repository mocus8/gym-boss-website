// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const ORDER_BASE_URL = "/api/";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestOrder(path, options = {}) {
    return requestApi(ORDER_BASE_URL, path, options);
}

// Функция для создания заказа из корзины
// В параметре объект с полями (так не обязательно явно указывать все поля при вызове)
// Отправляет запрос POST /api/order/create-from-cart
// JSON.stringify({...}) - превращает объект в JSON‑строку
export function createOrderFromCart({
    deliveryTypeId,
    deliveryAddressText = null,
    deliveryPostalCode = null,
    storeId = null,
}) {
    return requestOrder("order/create-from-cart", {
        method: "POST",
        body: JSON.stringify({
            deliveryTypeId: Number(deliveryTypeId),
            deliveryAddressText:
                deliveryAddressText == null
                    ? null
                    : String(deliveryAddressText),
            deliveryPostalCode:
                deliveryPostalCode == null ? null : String(deliveryPostalCode),
            storeId: storeId == null ? null : Number(storeId),
        }),
    });
}

// Функция для получения заказа по его id
// Отправляет запрос GET /api/order/{id}
export function getOrderById(orderId) {
    const id = Number(orderId);

    return requestOrder(`order/${id}`, {
        method: "GET",
    });
}

// Функция для получения заказа по его id
// Отправляет запрос GET /api/orders
export function getUserOrders() {
    return requestOrder("orders", {
        method: "GET",
    });
}

// Функция для отмены заказа по его id
// Отправляет запрос POST /api/order/{id}/cancel
export function markOrderAsCanceled(orderId) {
    const id = Number(orderId);

    return requestOrder(`order/${id}/cancel`, {
        method: "POST",
    });
}

// Функция для попытки оплаты заказа (получение ссылки для оплаты)
// Отправляет запрос POST /api/order/{id}/start-payment
export function getPaymentForOrder(orderId) {
    const id = Number(orderId);

    return requestOrder(`order/${id}/start-payment`, {
        method: "POST",
    });
}

// Функция для синхронизации статуса платежа и заказа между бд и юкассой
// Отправляет запрос POST /api/order/{id}/sync-payment
export function syncPaymentForOrder(orderId) {
    const id = Number(orderId);

    return requestOrder(`order/${id}/sync-payment`, {
        method: "POST",
    });
}
