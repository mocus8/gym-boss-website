// Импортируем js (подключение этих js в других файлах не требуется)
import { addCartItem, getCart, updateCartItemQty } from "../cart/api.js";
import { getErrorMessage, updateHeaderCounter } from "../utils.js";
import { notification } from "../ui/Notification.js";

// Получение id товара
function getProductId() {
    const productId = document.getElementById("button-add")?.dataset.productId;
    if (!productId) return;

    return Number(productId);
}

// Функция для обновления счетчика товара в корзине
function updateProductCounter(qty) {
    const productCounter = document.getElementById("product-cart-counter");
    if (!productCounter) return;

    productCounter.textContent = Number(qty);
}

// Функция для переключения состояния кнопок (по параметру - кол-во товара в корзине)
function toggleProductButtons(qty) {
    const btnAdd = document.getElementById("button-add");
    const btnChangeQty = document.getElementById("button-change-qty");
    if (!btnAdd || !btnChangeQty) return;

    if (qty > 0) {
        btnAdd.classList.add("hidden");
        btnChangeQty.classList.remove("hidden");
    } else {
        btnAdd.classList.remove("hidden");
        btnChangeQty.classList.add("hidden");
    }
}

// Выбираем состояние кнопок взаимодействия с товаром при прогрузке страницы
window.addEventListener("load", async function () {
    const btnAdd = document.getElementById("button-add");
    const btnChangeQty = document.getElementById("button-change-qty");
    if (!btnAdd || !btnChangeQty) return;

    const productId = getProductId();
    if (!productId) return;

    try {
        const cart = await getCart();
        const cartItems = cart.data?.items ?? [];

        // Узнаем, есть ли товар в корзине, и сколько его там
        const itemInCart = cartItems.find(
            (el) => Number(el.product_id) === productId
        );

        const cartCount = itemInCart ? Number(itemInCart.amount) : 0;

        updateProductCounter(cartCount);

        toggleProductButtons(cartCount);
    } catch (e) {
        console.error("Не удалось загрузить корзину на странице товара", e);
    }
});

document.addEventListener("click", async function (e) {
    const target = e.target;

    // Если нажали на "+" или "добавить в корзину"
    if (target.hasAttribute("data-product-add-cart")) {
        const productId = getProductId();
        if (!productId) return;

        try {
            const response = await addCartItem(productId, 1); // response = { success: true, data: { items, count, total } }

            const { items, count } = response.data; // деструктуризация объекта
            // Тоже самое что это:
            // const items = response.data.items;
            // const count = response.data.count;

            // Узнаем, сколько там этого товара
            const itemInCart = items.find(
                (el) => Number(el.product_id) === productId
            );

            // Тернарный оператор, если item не null то левую часть, иначе правую
            const qty = itemInCart ? Number(itemInCart.amount) : 0;

            updateHeaderCounter(count);
            updateProductCounter(qty);
            toggleProductButtons(qty);
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error("[product] Не удалось добавить товар в корзину", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // тот самый data
                productId,
            });

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        }
    }

    // Если нажали на "-"
    if (target.hasAttribute("data-product-subtract-cart")) {
        const productId = getProductId();
        if (!productId) return;

        const counterEl = document.getElementById("product-cart-counter");
        // Если элемент есть, и если у него валидный textContent, то берем это, иначе 0
        const currentQty = counterEl ? Number(counterEl.textContent) || 0 : 0;
        // Вычитаем, при этом берем 0 если получается меньше
        const newQty = Math.max(currentQty - 1, 0);

        try {
            const response = await updateCartItemQty(productId, newQty);

            const { items, count } = response.data;

            const itemInCart = items.find(
                (el) => Number(el.product_id) === productId
            );

            const qty = itemInCart ? Number(itemInCart.amount) : 0;

            updateHeaderCounter(count);
            updateProductCounter(qty);
            toggleProductButtons(qty);
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error("[product] Не удалось убрать товар из корзины", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // тот самый data
                productId,
            });

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        }
    }
});
