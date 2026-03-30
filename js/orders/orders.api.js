// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../utils.js";

const ORDER_BASE_URL = "/api/orders";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestOrder(path, options = {}) {
    return requestApi(ORDER_BASE_URL, path, options);
}

// Функция для создания заказа из корзины
// В параметре объект с полями (так не обязательно явно указывать все поля при вызове)
// Отправляет запрос POST /api/orders/create-from-cart
// JSON.stringify({...}) - превращает объект в JSON‑строку
export function createOrderFromCart({
    deliveryTypeId,
    deliveryAddressText = null,
    deliveryPostalCode = null,
    storeId = null,
}) {
    return requestOrder("/create-from-cart", {
        method: "POST",
        body: JSON.stringify({
            delivery_type_id: Number(deliveryTypeId),
            delivery_address_text:
                deliveryAddressText == null
                    ? null
                    : String(deliveryAddressText),
            delivery_postal_code:
                deliveryPostalCode == null ? null : String(deliveryPostalCode),
            store_id: storeId == null ? null : Number(storeId),
        }),
    });
}

// Функция для получения заказа по его id
// Отправляет запрос GET /api/orders/{id}
export function getOrderById(orderId) {
    const id = Number(orderId);

    return requestOrder(`/${id}`, {
        method: "GET",
    });
}

// Функция для получения заказов
// Отправляет запрос GET /api/orders
export function getUserOrders() {
    return requestOrder("", {
        method: "GET",
    });
}

// Функция для отмены заказа по его id
// Отправляет запрос POST /api/orders/{id}/cancel
export function markOrderAsCanceled(orderId) {
    const id = Number(orderId);

    return requestOrder(`/${id}/cancel`, {
        method: "POST",
    });
}

// Функция для попытки оплаты заказа (получение ссылки для оплаты)
// Отправляет запрос POST /api/orders/{id}/start-payment
export function getPaymentForOrder(orderId) {
    const id = Number(orderId);

    return requestOrder(`/${id}/start-payment`, {
        method: "POST",
    });
}

// Функция для синхронизации статуса платежа и заказа между бд и юкассой
// Отправляет запрос POST /api/orders/{id}/sync-payment
export function syncPaymentForOrder(orderId) {
    const id = Number(orderId);

    return requestOrder(`/${id}/sync-payment`, {
        method: "POST",
    });
}
