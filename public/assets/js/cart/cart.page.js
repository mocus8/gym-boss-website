// Импортируем js (подключение этих js в других файлах не требуется)
import {
    getCart,
    addCartItem,
    updateCartItemQty,
    removeCartItem,
} from "./cart.api.js";
import {
    updateHeaderCounter,
    getErrorMessage,
    setButtonLoading,
    formatPrice,
} from "../utils.js";
import { notification } from "../ui/notification.js";

// Функция для рендера блока товара
function createCartProductElement(item) {
    // Корневой блок
    const itemDiv = document.createElement("div");
    itemDiv.classList.add("cart_product");
    itemDiv.dataset.productId = String(item.id);
    itemDiv.dataset.price = String(item.price);

    const productClick = document.createElement("div");
    productClick.classList.add("product_click");
    itemDiv.appendChild(productClick);

    // Ссылка на товар
    const link = document.createElement("a");
    link.href = `products/${item.slug}`;
    productClick.appendChild(link);

    const img = document.createElement("img");
    img.classList.add("product_img_1");
    img.src = item.image_path;
    img.alt = item.name;
    link.appendChild(img);

    const name = document.createElement("div");
    name.classList.add("cart_product_name");
    name.textContent = item.name;
    link.appendChild(name);

    // Цена за позицию
    const price = document.createElement("div");
    price.classList.add("cart_product_price");
    price.id = `cart-item-total-${item.id}`;
    price.textContent = `${formatPrice(item.price * item.quantity)} ₽`;
    productClick.appendChild(price);

    // Блок взаимодействия
    const interaction = document.createElement("div");
    interaction.classList.add("product_interaction");
    productClick.appendChild(interaction);

    const countWrap = document.createElement("div");
    countWrap.classList.add("product_interaction_count");
    interaction.appendChild(countWrap);

    // Кнопка минус
    const minusBtn = document.createElement("button");
    minusBtn.classList.add("product_sign_button");
    minusBtn.type = "button";
    minusBtn.setAttribute("data-subtract-cart", "");
    minusBtn.dataset.productId = String(item.id);
    const minusImg = document.createElement("img");
    minusImg.classList.add("product_interaction_sign");
    minusImg.src = "/assets/images/ui/minus.png";
    minusBtn.appendChild(minusImg);
    countWrap.appendChild(minusBtn);

    // Количество
    const quantity = document.createElement("div");
    quantity.classList.add("product_interaction_amount");
    quantity.id = `cart-item-counter-${item.id}`;
    quantity.textContent = String(item.quantity);
    countWrap.appendChild(quantity);

    // Кнопка плюс
    const plusBtn = document.createElement("button");
    plusBtn.classList.add("product_sign_button");
    plusBtn.type = "button";
    plusBtn.setAttribute("data-add-cart", "");
    plusBtn.dataset.productId = String(item.id);
    const plusImg = document.createElement("img");
    plusImg.classList.add("product_interaction_sign");
    plusImg.src = "/assets/images/ui/plus.png";
    plusBtn.appendChild(plusImg);
    countWrap.appendChild(plusBtn);

    // Кнопка удалить
    const removeBtn = document.createElement("button");
    removeBtn.classList.add("product_sign_button");
    removeBtn.type = "button";
    removeBtn.setAttribute("data-remove-cart", "");
    removeBtn.dataset.productId = String(item.id);
    const removeImg = document.createElement("img");
    removeImg.classList.add("product_interaction_delete");
    removeImg.src = "/assets/images/ui/trash.png";
    removeBtn.appendChild(removeImg);
    interaction.appendChild(removeBtn);

    return itemDiv;
}

// Функция обновления счетчика товара в корзине
function updateCartItemCounter(productId, qty) {
    const qtyNumber = Number(qty) || 0;

    const productElement = document.querySelector(
        `[data-product-id="${productId}"]`,
    );
    if (!productElement) return;

    const itemPrice = Number(productElement.dataset.price) || 0;

    const productCounter = document.getElementById(
        `cart-item-counter-${productId}`,
    );
    if (!productCounter) return;

    const productTotal = document.getElementById(
        `cart-item-total-${productId}`,
    );
    if (!productTotal) return;

    if (qtyNumber === 0) {
        productElement.remove();
    } else {
        productCounter.textContent = String(qtyNumber);
        productTotal.textContent = `${formatPrice(qtyNumber * itemPrice)} ₽`;
    }
}

// Функция обновления информации о корзине
function updateCartInfo(cartData) {
    const itemsTotalQty = document.getElementById("items-total-qty");
    const itemsTotalPrice = document.querySelectorAll(
        "[data-items-total-price]",
    );
    if (!itemsTotalQty) return;

    const count = Number(cartData?.count ?? 0);
    const total = formatPrice(Number(cartData?.total ?? 0));

    itemsTotalQty.textContent = String(count);
    itemsTotalPrice.forEach((item) => {
        item.textContent = String(total);
    });
}

// Функция для инициализации страницы (рендер инфы и навешивание обработчиков)
async function initCartPage(productContainer, startOrderBtn) {
    // Функция для рендера пустой корзины
    function renderEmptyCart() {
        productContainer.innerHTML =
            '<div class="cart_empty">Корзина пуста</div>';
        startOrderBtn.classList.add("hidden");
    }

    // Находим и заполняем корзину, либо показываем пустую
    try {
        const cart = await getCart();
        const cartItems = cart.items ?? [];

        updateCartInfo(cart);

        if (cartItems.length === 0) {
            // Рендерим пустую корзину
            renderEmptyCart();
        } else {
            // Очищаем содержимое контейнера
            productContainer.innerHTML = "";

            cartItems.forEach((item) => {
                // Рендерим через функцию блок товара
                const itemEl = createCartProductElement(item);

                // Добавляем товар в контейнер
                productContainer.appendChild(itemEl);
            });

            // Показываем кнопку "Оформить заказ"
            startOrderBtn.classList.remove("hidden");
        }
    } catch (e) {
        console.error("[cart-page] Не удалось загрузить корзину", {
            message: e.message,
            code: e.code,
            status: e.status,
            payload: e.payload,
        });

        // Кладем ошибку в верстку
        productContainer.innerHTML =
            '<div class="cart_empty">Не удалось загрузить корзину</div>';

        // Показ ошибки пользователю
        const message = getErrorMessage(e.code, e.status);
        notification.open(message);
    }

    // Делигирование событий для взаимодествия с товарами в корзине
    productContainer.addEventListener("click", async (e) => {
        const target = e.target;

        // Минус
        const subtractBtn = target.closest("[data-subtract-cart]");
        if (subtractBtn) {
            const productId = Number(subtractBtn.dataset.productId);
            if (!productId) return;

            const counterEl = document.getElementById(
                `cart-item-counter-${productId}`,
            );

            // Если элемент есть, и если у него валидный textContent, то берем это, иначе 0
            const currentQty = counterEl
                ? Number(counterEl.textContent) || 0
                : 0;
            // Вычитаем, при этом берем 0 если получается меньше
            const newQty = Math.max(currentQty - 1, 0);

            try {
                setButtonLoading(subtractBtn, true);

                const response = await updateCartItemQty(productId, newQty);

                const { items, count } = response;

                const itemInCart = items.find(
                    (el) => Number(el.id) === productId,
                );

                const qty = itemInCart ? Number(itemInCart.quantity) : 0;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, qty);
                updateCartInfo(response);
                if (count === 0) renderEmptyCart();
            } catch (e) {
                // Логирование в консоль с полным контекстом
                console.error(
                    "[cart-page] Не удалось убрать товар из корзины",
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
                setButtonLoading(subtractBtn, false);
            }

            return;
        }

        // Плюс
        const addBtn = target.closest("[data-add-cart]");
        if (addBtn) {
            const productId = Number(addBtn.dataset.productId);
            if (!productId) return;

            try {
                setButtonLoading(addBtn, true);

                const response = await addCartItem(productId, 1); // response = { success: true, data: { items, count, total } }

                const { items, count } = response;

                const itemInCart = items.find(
                    (el) => Number(el.id) === productId,
                );

                const qty = itemInCart ? Number(itemInCart.quantity) : 0;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, qty);
                updateCartInfo(response);
            } catch (e) {
                // Логирование в консоль с полным контекстом
                console.error("[cart-page] Не удалось добавить товар", {
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
                setButtonLoading(addBtn, false);
            }

            return;
        }

        // Удаление
        const removeBtn = target.closest("[data-remove-cart]");
        if (removeBtn) {
            const productId = Number(removeBtn.dataset.productId);
            if (!productId) return;

            try {
                setButtonLoading(removeBtn, true);

                const response = await removeCartItem(productId);

                const { count } = response;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, 0);
                updateCartInfo(response);
                if (count === 0) renderEmptyCart();
            } catch (e) {
                // Логирование в консоль с полным контекстом
                console.error(
                    "[cart-page] Не удалось удалить товар из корзины",
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
                setButtonLoading(removeBtn, false);
            }

            return;
        }
    });
}

// Находим контейнер и кнопку "оформить заказ"
const productContainer = document.getElementById("product-container");
const startOrderBtn = document.getElementById("start-order-btn");

// Если нашлись элементы - рендерим инфу и вешаем обработчики
if (!productContainer || !startOrderBtn) {
    console.error(
        "[cart-page] Не найдены product-container или start-order-btn",
    );
} else {
    initCartPage(productContainer, startOrderBtn);
}
