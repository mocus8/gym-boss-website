import {
    getOrderById,
    markOrderAsCancelled,
    getPaymentForOrder,
    syncPaymentForOrder,
} from "./order.api.js";
import { getErrorMessage, formatPrice, formatDate } from "../utils.js";
import { ConfirmationModal } from "../ui/confirmation-modal.js";
import { notification } from "../ui/notification.js";

// Функция для скрытия информационных блоков, заполнения и показа блоков ошибки
function showOrderError(message) {
    const orderDetailsElements = document.querySelectorAll(
        "[data-order-details]",
    );
    const errorTextEl = document.getElementById("order-error-text");
    const errorElements = document.querySelectorAll("[data-order-error]");
    if (
        !errorTextEl ||
        orderDetailsElements.length === 0 ||
        errorElements.length === 0
    )
        return;

    orderDetailsElements.forEach((el) => el.classList.add("hidden"));
    errorTextEl.textContent = String(message);
    errorElements.forEach((el) => el.classList.remove("hidden"));
}

// Функция для валидации базовых полей заказа
function validateOrderData(data) {
    const order = data?.order;
    const items = data?.items;

    if (!order || typeof order !== "object") return false;
    if (!Array.isArray(items) || items.length === 0) return false;

    if (
        !Number.isInteger(Number(order.order_id)) ||
        Number(order.order_id) <= 0
    ) {
        return false;
    }
    if (typeof order.status_code !== "string" || !order.status_code) {
        return false;
    }
    if (typeof order.status_name !== "string" || !order.status_name) {
        return false;
    }
    if (
        typeof order.delivery_type_code !== "string" ||
        !order.delivery_type_code
    ) {
        return false;
    }
    if (
        typeof order.delivery_type_name !== "string" ||
        !order.delivery_type_name
    ) {
        return false;
    }

    if (typeof order.created_at !== "string" || !order.created_at) return false;

    if (!Number.isFinite(Number(order.total_price))) return false;
    if (!Number.isFinite(Number(order.delivery_cost))) return false;

    return true;
}

// Функция для заполнения базовых полей страницы заказа (если их нет - уходим в ошибку на странице)
function fillBasicInfo(order) {
    const statusEl = document.getElementById("order-status");
    const createdAtEl = document.getElementById("order-created-at");
    const deliveryTypeEl = document.getElementById("order-delivery-type");
    const totalPriceEl = document.getElementById("order-total-price");

    if (!statusEl || !createdAtEl || !deliveryTypeEl || !totalPriceEl) {
        console.error(
            "[order-page] Не найдены элементы страницы для базовой информации о заказе",
        );
        return false;
    }

    // Деструкторизация полей с базовой инфой о заказе
    const {
        status_name: statusName,
        created_at: createdAt,
        delivery_type_name: deliveryTypeName,
        delivery_cost: deliveryCostRaw,
        total_price: itemsPriceRaw, // стоимость товаров, к ней нужно прибавлять deliveryCost
    } = order;

    const deliveryCost = Number(deliveryCostRaw);
    const itemsPrice = Number(itemsPriceRaw);

    statusEl.textContent = String(statusName).toLowerCase();
    createdAtEl.textContent = formatDate(createdAt);
    deliveryTypeEl.textContent = String(deliveryTypeName).toLowerCase();
    totalPriceEl.textContent = formatPrice(
        Number(itemsPrice) + Number(deliveryCost),
    );

    return true;
}

// Функция для создания строчки с товаром
function createOrderItemEl(item) {
    // Создаем элемент товара
    const itemDiv = document.createElement("div");
    itemDiv.classList.add("item_row");

    const name = String(item.name);
    const amount = String(item.amount);
    const totalPrice = String(
        formatPrice(Number(item.amount) * Number(item.price)),
    );

    // Заполняем блок товара
    itemDiv.textContent = `${name} (${amount} шт.) - ${totalPrice} ₽`;

    return itemDiv;
}

// Функция для заполнения списка товаров
function fillOrderItems(items) {
    const itemsContainer = document.getElementById("order-items-container");
    if (!itemsContainer) {
        console.error(
            "[order-page] Не найден контейнер для товаров order-items-container",
        );
        return false;
    }

    // Очищаем содержимое контейнера
    itemsContainer.innerHTML = "";

    items.forEach((item) => {
        // Рендерим через функцию блок товара
        const itemEl = createOrderItemEl(item);

        // Добавляем товар в контейнер
        itemsContainer.appendChild(itemEl);
    });

    return true;
}

// Функция для заполнения span, либо скрытия обертки с data-optional-row
function setTextOrHide(el, value) {
    if (!el) return;

    const row = el.closest("[data-optional-row]") ?? el;
    const text = value == null ? "" : String(value).trim();

    row.classList.toggle("hidden", text === "");
    el.textContent = text;
}

// Функция для заполнения информации о доставке
function fillDeliveryInfo(order) {
    const deliveryTypeCode = order.delivery_type_code;

    const courierAddressEl = document.getElementById("order-courier-address");
    const deliveryPriceEl = document.getElementById("order-delivery-price");
    const pickupStoreEl = document.getElementById("order-pickup-store");

    if (deliveryTypeCode === "courier") {
        if (!courierAddressEl || !deliveryPriceEl) {
            console.error(
                "[order-page] При courier отсутствуют order-courier-address и/или order-delivery-price",
            );
            return false;
        }

        setTextOrHide(courierAddressEl, order.delivery_address_text);
        setTextOrHide(deliveryPriceEl, formatPrice(order.delivery_cost));
    }

    if (deliveryTypeCode === "pickup") {
        if (!pickupStoreEl) {
            console.error(
                "[order-page] При pickup отсутствует order-pickup-store",
            );
            return false;
        }

        setTextOrHide(
            pickupStoreEl,
            order.store_id ? `${order.store_address}` : null,
        );
    }

    return true;
}

// Функция для отображения строки только если есть две даты
function setRangeOrHide(fromEl, toEl, fromRaw, toRaw) {
    if (!fromEl || !toEl) return;

    const row = fromEl.closest("[data-optional-row]");
    const from = fromRaw ? formatDate(fromRaw) : "";
    const to = toRaw ? formatDate(toRaw) : "";

    row?.classList.toggle("hidden", !from || !to);
    fromEl.textContent = from;
    toEl.textContent = to;
}

// Функция для заполнения дат доставки/готовности заказа
function fillDeliveryDates(order) {
    const courierFirst = document.getElementById("order-courier-first-date");
    const courierLast = document.getElementById("order-courier-last-date");
    setRangeOrHide(
        courierFirst,
        courierLast,
        order.delivery_from,
        order.delivery_to,
    );

    const pickupFirst = document.getElementById(
        "order-ready-for-pickup-first-date",
    );
    const pickupLast = document.getElementById(
        "order-ready-for-pickup-last-date",
    );
    setRangeOrHide(
        pickupFirst,
        pickupLast,
        order.delivery_from,
        order.delivery_to,
    );
}

function showOrderSections(order) {
    const statusCode = order.status_code;
    const deliveryTypeCode = order.delivery_type_code;

    // Скрываем все блоки у которых  data-delivery-visible-type не совпадает, и показываем где совпадает
    document.querySelectorAll("[data-delivery-visible-type]").forEach((el) => {
        const visibleType = el.dataset.deliveryVisibleType;
        el.classList.toggle("hidden", visibleType !== deliveryTypeCode);
    });

    // Показываем подходищие по статусу блоки и скрываем неподходящие
    document.querySelectorAll("[data-visible-status]").forEach((el) => {
        const allowed = (el.dataset.visibleStatus || "")
            .split(",") // разделяет по запятым
            .map((s) => s.trim()) // обрезает пробелы
            .filter(Boolean); // выкидывает пустые строки

        el.classList.toggle("hidden", !allowed.includes(statusCode));
    });
}

// Главная функция для рендера инфы о заказе и товарах
function renderOrderInfo(data) {
    const order = data.order;
    const items = data.items;

    // Заполнение базовой информации
    if (!fillBasicInfo(order)) return false;
    // Заполнение списка товаров
    if (!fillOrderItems(items)) return false;
    // Заполнение информации о доставке
    if (!fillDeliveryInfo(order)) return false;
    // Заполнение дат доставки/готовности заказа
    fillDeliveryDates(order);
    // Показ опцианальных по статусу блоков страницы
    showOrderSections(order);

    return true;
}

window.addEventListener("DOMContentLoaded", async () => {
    const orderContainer = document.getElementById("order-container");
    const orderIdEl = document.getElementById("order-id");
    if (!orderContainer || !orderIdEl) return;

    const orderId = Number(orderContainer.dataset.orderId);
    // Проверяем orderId с бэка на валидность, если невалидный - заполняем и показываем блоки ошибки
    if (!Number.isInteger(orderId) || orderId <= 0) {
        console.error(
            "[order-page] Некорректный orderId:",
            orderContainer.dataset.orderId,
        );
        showOrderError(
            "Некорректный номер заказа. Перейдите в историю заказов.",
        );
        return;
    }

    orderIdEl.textContent = String(orderId);

    try {
        const data = await getOrderById(orderId);

        // Валидация заказа
        // Функции возвращают boolean
        if (!validateOrderData(data)) {
            console.error("[order-page] Невалидные данные заказа");
            showOrderError(
                "Данные заказа неполны. Перейдите в историю заказов или обратитесь в поддержку",
            );
            return;
        }

        // Рендер заказа
        if (!renderOrderInfo(data)) {
            showOrderError(
                "Не удалось отобразить заказ. Перейдите в историю заказов или обратитесь в поддержку",
            );
            return;
        }

        // Синхронизируем статус заказа если его статус pending_payment
        if (data.order.status_code === "pending_payment") {
            try {
                await syncPaymentForOrder(orderId);
                window.location.reload();
            } catch (e) {
                // Логирование в консоль с полным контекстом
                console.error(
                    "[order-page] Не удалось синхронизировать статус заказа",
                    {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload, // data
                    },
                );
            }
        }
    } catch (e) {
        console.error("[order-page] Не удалось загрузить заказ", {
            message: e.message,
            code: e.code,
            status: e.status,
            payload: e.payload,
        });

        const message = getErrorMessage(e.code, e.status);
        showOrderError(message);
    }

    // Если нет кнопки отмены - тихо выходим (она есть не для всех статусов)
    const cancelButton = document.getElementById("order-cancel-btn");
    if (!cancelButton) return;

    // Настраиваем модалку подтверждения и навешиваем обработчик отмены
    const confirmModal = new ConfirmationModal("confirmation-modal");

    // Навешиваем открытие модалки на клик по кнопке "отменить"
    cancelButton.addEventListener("click", () => {
        confirmModal.open({
            title: "Отмена заказа",
            message: `Отменить заказ №${orderId}?`,
            warning: "Это действие необратимо.",
            confirmText: "Да, отменить заказ",
            cancelText: "Нет, оставить",
            onConfirm: async () => {
                try {
                    await markOrderAsCancelled(orderId);
                    window.location.reload();
                } catch (e) {
                    // Логирование в консоль с полным контекстом
                    console.error("[order-page] Не удалось отменить заказ", {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload, // data
                    });

                    // Показ ошибки пользователю
                    const message = getErrorMessage(e.code, e.status);
                    notification.open(message);

                    // Пробрасываем ошибку, при этом модалка не закрывается
                    throw e;
                }
            },
        });
    });

    // Если нет кнопки оплаты - тихо выходим (она есть не для всех статусов)
    const payButton = document.getElementById("order-pay-btn");
    if (!payButton) return;

    // Навешиваем открытие модалки на клик по кнопке "оплатить"
    payButton.addEventListener("click", async () => {
        try {
            const paymentUrl = await getPaymentForOrder(orderId);
            window.location.href = paymentUrl;
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error("[order-page] Не удалось оплатить заказ", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // data
            });

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        }
    });
});
