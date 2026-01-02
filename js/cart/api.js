// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

const CART_BASE_URL = "/api/cart/";

// Функция запроса к api-контроллеру: добавляет baseUrl, парсит JSON, проверяет HTTP статус и флаг success, кидает осмысленную ошибку
// Options - по умолчанию пустой объект {}, можеть быть таким: { method: 'POST', body: '...' }
// ...options раскрывает содержимое объекта и вставляет его (убирает {} вокруг содержимого)
// Credentials: "same-origin" — настройка для fetch, которая говорит: "отправлять данные только если запрос на тот же домен"
async function requestCart(path, options = {}) {
    const response = await fetch(CART_BASE_URL + path, {
        // Всегда по умолчанию JSON.
        headers: {
            "Content-Type": "application/json",
        },
        credentials: "same-origin",
        ...options,
    });

    // Пытаемся распарсить response как json
    let data = null;
    try {
        data = await response.json();
    } catch {
        data = null;
    }

    // Если HTTP статус не 2xx или в data API вернул success: false то считаем это ошибкой.
    if (!response.ok || (data && data.success === false)) {
        const error = new Error(
            (data && data.error) || `Cart API error (${response.status})`
        );
        error.status = response.status;
        error.payload = data;
        throw error;
    }

    return data;
}

// Функция для получения корзины
// Отправляет запрос GET /api/cart
// Возвращает объект с полями: { success: true, data: { items, count, total } }
export function getCart() {
    return requestCart("", {
        method: "GET",
    });
}

/**
 * Добавить товар в корзину.
 * POST /api/cart/add-item
 * productId: number
 * qty: number (по умолчанию 1)
 */

// Функция для добавления опр-ого кол-ва товара в корзину
// Отправляет запрос POST /api/cart/add-item
// Ничего не возвращает
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
// Ничего не возвращает
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
// Ничего не возвращает
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
// Ничего не возвращает
export function clearCart() {
    return requestCart("clear", {
        method: "POST",
    });
}
