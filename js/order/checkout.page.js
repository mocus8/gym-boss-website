// Импортируем js (подключение этих js в других файлах не требуется)
import {
    createOrderFromCart,
    getOrderById,
    getUserOrders,
    markOrderAsCancelled,
} from "./api.js";
import { getErrorMessage, formatPrice } from "../utils.js";
import { notification } from "../ui/Notification.js";

// Инициализируем карты
let deliveryMap = null;
let pickupMap = null;

function initDeliveryMapOnce() {
    if (!deliveryMap) {
        deliveryMap = new DeliveryMap("delivery-map");
    }
}

function initPickupMapOnce() {
    if (!pickupMap) {
        pickupMap = new PickupMap("pickup-map");
    }
}

// Обработчик переключения типа доставки
document.querySelector(".order_types").addEventListener("click", function (e) {
    // e.target - элемент, на котором произошел клик
    // e.currentTarget - элемент, на котором висит обработчик (.order_types)

    // Проверяем, был ли клик по кнопке .order_type
    const target = e.target.closest(".order_type");
    if (!target) return; // если клик был не по кнопке - выходим

    // Дальше работаем с target (нажатой кнопкой)
    const isDelivery = target.id === "order-type-delivery";
    const previousType = document
        .getElementById("order-type-delivery")
        .classList.contains("chosen")
        ? "delivery"
        : "pickup";

    // если кликаем на ту же кнопку
    if (
        (isDelivery && previousType === "delivery") ||
        (!isDelivery && previousType === "pickup")
    ) {
        return;
    }

    // Очищаем интерфейс перед переключением
    clearOrderInterface(previousType);

    // Переключение модалок
    // toggle автоматически добавляет или удаляет класс
    document
        .getElementById("modal-order-type-delivery")
        .classList.toggle("hidden", !isDelivery);
    document
        .getElementById("modal-order-type-pickup")
        .classList.toggle("hidden", isDelivery);

    // инициализируем карты
    setTimeout(() => {
        if (isDelivery && !deliveryMap) {
            // создаем карту если ее нет
            deliveryMap = new DeliveryMap("delivery-map");
        } else if (!isDelivery && !pickupMap) {
            pickupMap = new PickupMap("pickup-map");
        }
    }, 50);

    // Переключение стилей кнопок выбора типа доставки
    document
        .getElementById("order-type-delivery")
        .classList.toggle("chosen", isDelivery);
    document
        .getElementById("order-type-pickup")
        .classList.toggle("chosen", !isDelivery);

    // Обновляем тип доставки в бд
    updateDeliveryTypeInDB(isDelivery ? "delivery" : "pickup");
});

// Показ ошибки карты (по параметру)
function showMapError(type = "all") {
    const VALID_TYPES = ["stores", "delivery", "pickup", "all"];

    // Валидация
    if (!VALID_TYPES.includes(type)) {
        console.error(
            `[MapError] Неверный тип: "${type}". Допустимо: ${VALID_TYPES.join(", ")}`,
        );
        type = "all"; // fallback (резервный вариант, откат)
    }

    console.error("Ошибка Яндекс.Карт:", type);

    if (type === "all") {
        // Показываем все ошибки
        document.querySelectorAll('[class*="error_"]').forEach((block) => {
            block.classList.add("open");
        });
        return;
    }

    const className = `error_${type}_map`;
    const elements = document.querySelectorAll(`.${className}`);

    if (elements.length === 0) {
        console.warn(
            `[MapError] Элемент .${className} не найден, показываю все ошибки`,
        );
        showMapError("all"); // рекурсивный вызов
        return;
    }

    elements.forEach((block) => block.classList.add("open"));
}

// нужна будет функция isAddressSelected, на вход объект:
// isAddressSelected({
//     type,        // "delivery" | "pickup"
//     address,     // для доставки
//     shopId,      // для самовывоза
// });

// Метод убирания лоадера, сделать общую функцию для всех карт
#hideLoader() {
    // Плавно убираем лоадер
    const loader = document.getElementById("delivery-map-loader");
    if (loader) {
        loader.style.opacity = "0";
        loader.style.visibility = "hidden";
        // Через время завершения анимации - полностью убираем
        setTimeout(() => {
            loader.style.display = "none";
        }, 200); // Время должно совпадать с transition (0.4s)
    }
}