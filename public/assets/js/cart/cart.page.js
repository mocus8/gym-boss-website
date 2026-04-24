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

// Функция для инициализации страницы (рендер инфы и навешивание обработчиков)
async function initCartPage(productContainer, startOrderBtn) {
    // Функция для рендера сообщения в списке товаров в корзине
    function renderCartItemsMessage(message) {
        productContainer.innerHTML = "";

        const cartItemsMessage = document.createElement("div");
        cartItemsMessage.classList.add("cart__items-message");
        cartItemsMessage.classList.add("flex-center");
        cartItemsMessage.textContent = String(message);

        productContainer.appendChild(cartItemsMessage);
    }

    // Функция-обертка для рендера пустой корзины
    function renderEmptyCart() {
        const message = "Корзина пуста";
        renderCartItemsMessage(message);

        startOrderBtn.hidden = true;
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

            // Создаем список-обертку для товаров
            const itemsList = document.createElement("ul");
            itemsList.classList.add("list-reset");
            itemsList.classList.add("cart__items-list");
            productContainer.appendChild(itemsList);

            cartItems.forEach((item) => {
                // Рендерим через функцию блок товара
                const itemEl = createCartProductElement(item);

                // Добавляем товар в список
                itemsList.appendChild(itemEl);
            });

            // Показываем кнопку "Оформить заказ"
            startOrderBtn.hidden = false;
        }
    } catch (e) {
        console.error("[cart-page] Не удалось загрузить корзину", {
            message: e.message,
            code: e.code,
            status: e.status,
            payload: e.payload,
        });

        // Кладем ошибку в верстку
        renderCartItemsMessage("Не удалось загрузить корзину");

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

// Функция для рендера товара в корзине
function createCartProductElement(item) {
    const itemWrapper = document.createElement("li");
    itemWrapper.classList.add("cart__item");
    itemWrapper.dataset.productId = String(item.id);
    itemWrapper.dataset.price = String(item.price);

    const productCard = document.createElement("div");
    productCard.classList.add("product-card");
    productCard.classList.add("shape-cut-corners--diagonal");
    itemWrapper.appendChild(productCard);

    const linkWrapper = document.createElement("a");
    linkWrapper.classList.add("link-shell");
    linkWrapper.classList.add("full-size");
    linkWrapper.href = `/products/${encodeURIComponent(item.slug)}`;
    productCard.appendChild(linkWrapper);

    const productImg = document.createElement("img");
    productImg.classList.add("shape-cut-corners--diagonal");
    productImg.src = item.image_path;
    productImg.alt = item.name;
    linkWrapper.appendChild(productImg);

    const productName = document.createElement("h3");
    productName.textContent = item.name;
    linkWrapper.appendChild(productName);

    const interactionBlock = document.createElement("div");
    interactionBlock.classList.add("cart__item-interaction");
    interactionBlock.classList.add("shape-cut-corners--diagonal");
    productCard.appendChild(interactionBlock);

    const minusBtnWrapper = document.createElement("button");
    minusBtnWrapper.classList.add("btn-reset");
    minusBtnWrapper.classList.add("btn-shell");
    minusBtnWrapper.type = "button";
    minusBtnWrapper.setAttribute("data-subtract-cart", "");
    minusBtnWrapper.dataset.productId = String(item.id);
    interactionBlock.appendChild(minusBtnWrapper);

    const minusBtn = document.createElement("span");
    minusBtn.textContent = "–";
    minusBtn.classList.add("cart__item-btn-sign");
    minusBtnWrapper.appendChild(minusBtn);

    const quantity = document.createElement("p");
    quantity.id = `cart-item-counter-${item.id}`;
    quantity.textContent = String(item.quantity);
    interactionBlock.appendChild(quantity);

    const plusBtnWrapper = document.createElement("button");
    plusBtnWrapper.classList.add("btn-reset");
    plusBtnWrapper.classList.add("btn-shell");
    plusBtnWrapper.type = "button";
    plusBtnWrapper.setAttribute("data-add-cart", "");
    plusBtnWrapper.dataset.productId = String(item.id);
    interactionBlock.appendChild(plusBtnWrapper);

    const plusBtn = document.createElement("span");
    plusBtn.textContent = "+";
    plusBtn.classList.add("cart__item-btn-sign");
    plusBtnWrapper.appendChild(plusBtn);

    const removeBtn = document.createElement("button");
    removeBtn.classList.add("btn-reset");
    removeBtn.classList.add("btn-shell");
    removeBtn.type = "button";
    removeBtn.setAttribute("data-remove-cart", "");
    removeBtn.dataset.productId = String(item.id);

    const removeImg = document.createElement("img");
    removeImg.classList.add("cart__item-remove-icon");
    removeImg.src = "/assets/images/ui/trash.svg";
    removeImg.alt = "Удалить из корзины";
    removeBtn.appendChild(removeImg);
    interactionBlock.appendChild(removeBtn);

    const productPrice = document.createElement("p");
    productPrice.classList.add("product-card__price");
    productPrice.id = `cart-item-total-${item.id}`;
    productPrice.textContent = `${formatPrice(item.price * item.quantity)} ₽`;
    productCard.appendChild(productPrice);

    return itemWrapper;
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
