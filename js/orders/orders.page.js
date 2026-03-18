import { getUserOrders } from "./orders.api.js";
import { getErrorMessage, formatPrice, formatDate } from "../utils.js";
import { notification } from "../ui/notification.js";

// Функция для рендера элемента заказа
function createOrderElement(order) {
    // Корневой блок
    const orderDiv = document.createElement("div");
    orderDiv.classList.add("order");

    // Номер заказа и дата оформления
    const numberDate = document.createElement("div");
    numberDate.classList.add("order_number");
    const numberDateText = `Заказ №${order.order_id} (оформлен ${formatDate(order.created_at)})`;
    numberDate.textContent = numberDateText;
    orderDiv.appendChild(numberDate);

    // Стоимость заказа
    const price = document.createElement("div");
    price.classList.add("order_data");
    const orderTotalPrice =
        Number(order.total_price) + Number(order.delivery_cost);
    const priceText = `Стоимость: ${formatPrice(orderTotalPrice)} ₽`;
    price.textContent = priceText;
    orderDiv.appendChild(price);

    // Способ получения
    const deliveryType = document.createElement("div");
    deliveryType.classList.add("order_data");
    deliveryType.textContent = `Способ получения: ${String(order.delivery_type_name).toLowerCase()}`;
    orderDiv.appendChild(deliveryType);

    // Адрес доставки/получения
    const address = document.createElement("div");
    address.classList.add("order_data_address");
    const deliveryTypeCode = order.delivery_type_code;
    if (deliveryTypeCode === "courier") {
        address.textContent = `Адрес доставки: ${String(order.delivery_address_text)}`;
    } else if (deliveryTypeCode === "pickup") {
        address.textContent = `Пункт выдачи: ${String(order.store_address)}`;
    }
    orderDiv.appendChild(address);

    // Статус заказа
    const status = document.createElement("div");
    status.classList.add("order_data");
    status.textContent = `Статус заказа: ${String(order.status_name).toLowerCase()}`;
    orderDiv.appendChild(status);

    // Кнопка-ссылка для перехода к заказу
    const link = document.createElement("a");
    link.href = `/orders/${Number(order.order_id)}`;
    link.classList.add("order_action_button");
    link.textContent = "Перейти к заказу";
    orderDiv.appendChild(link);

    // Возвращаем полученный элемент заказа
    return orderDiv;
}

// Функция для рендера списка заказов внутри контейнера
async function renderOrdersPage(ordersContainer) {
    // Находим и заполняем список, либо показываем пустой
    try {
        const data = await getUserOrders();
        const orders = Array.isArray(data) ? data : [];

        if (orders.length === 0) {
            // Рендерим пустой список
            ordersContainer.innerHTML =
                '<div class="cart_empty">У вас еще нет заказов</div>';
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
        ordersContainer.innerHTML =
            '<div class="cart_empty">Не удалось загрузить список заказов</div>';

        // Показ уведомления с ошибкой
        const message = getErrorMessage(e.code, e.status);
        notification.open(message);
    }
}

// Находим контейнер для заказов
const ordersContainer = document.getElementById("orders-container");

// Если нашелся контейнер - рендерим заказы
if (!ordersContainer) {
    console.error("[orders-page] Не найден orders-container");
} else {
    renderOrdersPage(ordersContainer);
}
