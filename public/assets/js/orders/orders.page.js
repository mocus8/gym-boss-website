import { getUserOrders } from "./orders.api.js";
import { getErrorMessage, formatPrice, formatDate } from "../utils.js";
import { notification } from "../ui/notification.js";

// Функция для рендера элемента заказа
function createOrderElement(order) {
    const orderItem = document.createElement("li");
    orderItem.classList.add("orders__item");

    // Номер заказа и дата оформления
    const numberDate = document.createElement("p");
    numberDate.textContent = `Заказ №${order.id} (оформлен ${formatDate(order.created_at)})`;
    orderItem.appendChild(numberDate);

    // Стоимость заказа
    const price = document.createElement("p");
    const orderTotalPrice =
        Number(order.total_price) + Number(order.delivery_cost);
    price.textContent = `Стоимость: ${formatPrice(orderTotalPrice)} ₽`;
    orderItem.appendChild(price);

    // Способ получения
    const deliveryType = document.createElement("p");
    deliveryType.textContent = `Способ получения: ${String(order.delivery_type_name).toLowerCase()}`;
    orderItem.appendChild(deliveryType);

    // Адрес доставки/получения
    const address = document.createElement("p");
    const deliveryTypeCode = order.delivery_type_code;
    if (deliveryTypeCode === "courier") {
        address.textContent = `Адрес доставки: ${String(order.delivery_address_text)}`;
    } else if (deliveryTypeCode === "pickup") {
        address.textContent = `Пункт выдачи: ${String(order.store_address)}`;
    }
    orderItem.appendChild(address);

    // Статус заказа
    const status = document.createElement("p");
    status.textContent = `Статус заказа: ${String(order.status_name).toLowerCase()}`;
    orderItem.appendChild(status);

    // Кнопка-ссылка для перехода к заказу
    const linkShell = document.createElement("a");
    linkShell.href = `/orders/${Number(order.id)}`;
    linkShell.classList.add("link-shell");

    const linkBtn = document.createElement("span");
    linkBtn.classList.add("btn", "primary-btn", "shape-cut-corners--diagonal");
    linkBtn.textContent = "Перейти к заказу";
    linkShell.appendChild(linkBtn);

    orderItem.appendChild(linkShell);

    return orderItem;
}

function showOrdersMessage(ordersContainer, message) {
    const messageEl = document.createElement("p");
    messageEl.classList.add("orders__message");
    messageEl.textContent = message;

    ordersContainer.innerHTML = "";
    ordersContainer.appendChild(messageEl);
}

// Функция для рендера списка заказов внутри контейнера
async function renderOrdersPage(ordersContainer, ordersLoader) {
    // Находим и заполняем список, либо показываем пустой
    try {
        const data = await getUserOrders();
        const orders = Array.isArray(data) ? data : [];

        if (orders.length === 0) {
            // Рендерим пустой список
            showOrdersMessage(ordersContainer, "У вас еще нет заказов");
        } else {
            // Очищаем содержимое контейнера
            ordersContainer.innerHTML = "";

            orders.forEach((order) => {
                // Рендерим через функцию блок товара
                const orderEl = createOrderElement(order);

                // Добавляем заказ в контейнер
                ordersContainer.appendChild(orderEl);
            });
        }
    } catch (e) {
        console.error("[orders-page] Не удалось загрузить список заказов", {
            message: e.message,
            code: e.code,
            status: e.status,
            payload: e.payload,
        });

        // Кладем ошибку в верстку
        showOrdersMessage(
            ordersContainer,
            "Не удалось загрузить список заказов",
        );

        // Показ уведомления с ошибкой
        const message = getErrorMessage(e.code, e.status);
        notification.open(message);
    } finally {
        ordersLoader.hidden = true;
    }
}

// Находим контейнер для заказов и лоадер
const ordersContainer = document.getElementById("orders-container");
const ordersLoader = document.getElementById("orders-loader");

// Если нашелся контейнер и лоадер - рендерим заказы
if (!ordersContainer || !ordersLoader) {
    console.error(
        "[orders-page] Не найдены orders-container или orders-loader",
    );
} else {
    renderOrdersPage(ordersContainer, ordersLoader);
}
