// Импортируем js (подключение этих js в других файлах не требуется)
import { getCart, addCartItem, updateCartItemQty } from "../cart/cart.api.js";
import {
    getErrorMessage,
    updateHeaderCounter,
    setButtonLoading,
} from "../utils.js";
import { notification } from "../ui/notification.js";

// Получение id товара
function getProductId() {
    const productId = document.getElementById("button-add")?.dataset.productId;
    if (!productId) return;

    return Number(productId);
}

// Функция для включения залипания кнопок на время обработки запроса
function setProductButtonsLoading(isLoading) {
    const btnAdd = document.getElementById("button-add");
    const btnChangeQtyWrap = document.getElementById("button-change-qty");
    if (!btnAdd || !btnChangeQtyWrap) return;

    // Отключаем кликабельность для кнопок
    setButtonLoading(btnAdd, isLoading);
    btnChangeQtyWrap
        .querySelectorAll("button")
        .forEach((btn) => setButtonLoading(btn, isLoading));
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
window.addEventListener("DOMContentLoaded", async function () {
    const productId = getProductId();
    if (!productId) return;

    try {
        // Делаем залипание на все кнопки
        setProductButtonsLoading(true);

        const cart = await getCart();
        const cartItems = cart.items ?? [];

        // Узнаем, есть ли товар в корзине, и сколько его там
        const itemInCart = cartItems.find(
            (el) => Number(el.product_id) === productId,
        );

        const cartCount = itemInCart ? Number(itemInCart.amount) : 0;

        updateProductCounter(cartCount);

        toggleProductButtons(cartCount);
    } catch (e) {
        // Логирование в консоль с полным контекстом
        console.error(
            "[product-page] Не удалось прогрузить состояние кнопок товара",
            {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            },
        );
    } finally {
        // Отключаем залипание
        setProductButtonsLoading(false);
    }
});

// Делигирование событий при клике на странице
document.addEventListener("click", async function (e) {
    // Определяем куда пришелся click
    const addBtn = e.target.closest("[data-product-add-cart]");
    const subBtn = e.target.closest("[data-product-subtract-cart]");

    // Если нажали на "+" или "добавить в корзину"
    if (addBtn) {
        const productId = getProductId();
        if (!productId) return;

        try {
            // Делаем залипание на все кнопки
            setProductButtonsLoading(true);

            const response = await addCartItem(productId, 1); // response = { success: true, data: { items, count, total } }

            const { items, count } = response; // деструктуризация объекта
            // Тоже самое что это:
            // const items = response.items;
            // const count = response.count;

            // Узнаем, сколько там этого товара
            const itemInCart = items.find(
                (el) => Number(el.product_id) === productId,
            );

            // Тернарный оператор, если item не null то левую часть, иначе правую
            const qty = itemInCart ? Number(itemInCart.amount) : 0;

            updateHeaderCounter(count);
            updateProductCounter(qty);
            toggleProductButtons(qty);
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error(
                "[product-page] Не удалось добавить товар в корзину",
                {
                    message: e.message,
                    code: e.code,
                    status: e.status,
                    payload: e.payload, // тот самый data
                    productId,
                },
            );

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Отключаем залипание
            setProductButtonsLoading(false);
        }
    }

    // Если нажали на "-"
    if (subBtn) {
        const productId = getProductId();
        if (!productId) return;

        const counterEl = document.getElementById("product-cart-counter");
        // Если элемент есть, и если у него валидный textContent, то берем это, иначе 0
        const currentQty = counterEl ? Number(counterEl.textContent) || 0 : 0;
        // Вычитаем, при этом берем 0 если получается меньше
        const newQty = Math.max(currentQty - 1, 0);

        try {
            // Делаем залипание на все кнопки
            setProductButtonsLoading(true);

            const response = await updateCartItemQty(productId, newQty);

            const { items, count } = response;

            const itemInCart = items.find(
                (el) => Number(el.product_id) === productId,
            );

            const qty = itemInCart ? Number(itemInCart.amount) : 0;

            updateHeaderCounter(count);
            updateProductCounter(qty);
            toggleProductButtons(qty);
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error("[product-page] Не удалось убрать товар из корзины", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // тот самый data
                productId,
            });

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Отключаем залипание
            setProductButtonsLoading(false);
        }
    }
});
