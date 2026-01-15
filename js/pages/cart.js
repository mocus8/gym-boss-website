// Импортируем js (подключение этих js в других файлах не требуется)
import {
    getCart,
    addCartItem,
    updateCartItemQty,
    removeCartItem,
} from "../cart/api.js";
import { updateHeaderCounter, getErrorMessage, formatPrice } from "../utils.js";
import { notification } from "../ui/Notification.js";

// Функция для рендера блока товара
function createCartProductElement(item) {
    // Корневой блок
    const itemDiv = document.createElement("div");
    itemDiv.classList.add("cart_product");
    itemDiv.dataset.productId = String(item.product_id);
    itemDiv.dataset.price = String(item.price);

    const productClick = document.createElement("div");
    productClick.classList.add("product_click");
    itemDiv.appendChild(productClick);

    // Ссылка на товар
    const link = document.createElement("a");
    link.href = `product/${item.slug}`;
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
    price.id = `cart-item-total-${item.product_id}`;
    price.textContent = `${formatPrice(item.price * item.amount)} ₽`;
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
    const minusImg = document.createElement("img");
    minusImg.classList.add("product_interaction_sign");
    minusImg.src = "/img/minus.png";
    minusImg.setAttribute("data-subtract-cart", "");
    minusImg.dataset.productId = String(item.product_id);
    minusBtn.appendChild(minusImg);
    countWrap.appendChild(minusBtn);

    // Количество
    const amount = document.createElement("div");
    amount.classList.add("product_interaction_amount");
    amount.id = `cart-item-counter-${item.product_id}`;
    amount.textContent = String(item.amount);
    countWrap.appendChild(amount);

    // Кнопка плюс
    const plusBtn = document.createElement("button");
    plusBtn.classList.add("product_sign_button");
    plusBtn.type = "button";
    const plusImg = document.createElement("img");
    plusImg.classList.add("product_interaction_sign");
    plusImg.src = "/img/plus.png";
    plusImg.setAttribute("data-add-cart", "");
    plusImg.dataset.productId = String(item.product_id);
    plusBtn.appendChild(plusImg);
    countWrap.appendChild(plusBtn);

    // Кнопка удалить
    const removeBtn = document.createElement("button");
    removeBtn.classList.add("product_sign_button");
    removeBtn.type = "button";
    const removeImg = document.createElement("img");
    removeImg.classList.add("product_interaction_delete");
    removeImg.src = "/img/trash.png";
    removeImg.setAttribute("data-remove-cart", "");
    removeImg.dataset.productId = String(item.product_id);
    removeBtn.appendChild(removeImg);
    interaction.appendChild(removeBtn);

    return itemDiv;
}

// Функция обновления счетчика товара в корзине
function updateCartItemCounter(productId, qty) {
    const qtyNumber = Number(qty) || 0;

    const productElement = document.querySelector(
        `[data-product-id="${productId}"]`
    );
    if (!productElement) return;

    const itemPrice = Number(productElement.dataset.price) || 0;

    const productCounter = document.getElementById(
        `cart-item-counter-${productId}`
    );
    if (!productCounter) return;

    const productTotal = document.getElementById(
        `cart-item-total-${productId}`
    );
    if (!productTotal) return;

    if (qtyNumber === 0) {
        productElement.remove();
    } else {
        productCounter.textContent = String(qtyNumber);
        productTotal.textContent = `${qtyNumber * itemPrice} ₽`;
    }
}

// Функция обновления информации о корзине
function updateCartInfo(cartData) {
    const itemsTotalQty = document.getElementById("items-total-qty");
    const itemsTotalPrice = document.querySelectorAll(
        "[data-items-total-price]"
    );
    if (!itemsTotalQty) return;

    const count = Number(cartData?.count ?? 0);
    const total = formatPrice(Number(cartData?.total ?? 0));

    itemsTotalQty.textContent = String(count);
    itemsTotalPrice.forEach((item) => {
        item.textContent = String(total);
    });
}

// Находим контейнер и кнопку "оформить заказ"
const productContainer = document.getElementById("product-container");
const startOrderBtn = document.getElementById("start-order-btn");

// Если нашли контейнер, то навешиваем разные обработчики
if (!productContainer || !startOrderBtn) {
    console.warn(
        "[cart-page] product-container или start-order-btn не найдены"
    );
} else {
    // Функия для рендера пустой корзины
    function renderEmptyCart() {
        productContainer.innerHTML =
            '<div class="cart_empty">Корзина пуста</div>';
        startOrderBtn.classList.add("hidden");
    }

    // Находим и заполняем корзину при прогрузке страницы, либо показываем пустую
    window.addEventListener("load", async function () {
        try {
            const cart = await getCart();
            const cartItems = cart.data?.items ?? [];

            updateCartInfo(cart.data);

            if (cartItems.length === 0) {
                renderEmptyCart();
            } else {
                // Очищаем содержимое контейнера
                productContainer.innerHTML = "";

                cartItems.forEach((item) => {
                    // Рендерим через функцию блок товара
                    const itemDiv = createCartProductElement(item);

                    // Добавляем магазин в контейнер
                    productContainer.appendChild(itemDiv);
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
    });

    // Делигирование событий для взаимодествия с товарами в корзине
    productContainer.addEventListener("click", async (e) => {
        const target = e.target;

        // Минус
        const subtractItem = target.closest("[data-subtract-cart]");
        if (subtractItem) {
            const productId = Number(subtractItem.dataset.productId);
            if (!productId) return;

            const counterEl = document.getElementById(
                `cart-item-counter-${productId}`
            );

            // Если элемент есть, и если у него валидный textContent, то берем это, иначе 0
            const currentQty = counterEl
                ? Number(counterEl.textContent) || 0
                : 0;
            // Вычитаем, при этом берем 0 если получается меньше
            const newQty = Math.max(currentQty - 1, 0);

            try {
                const response = await updateCartItemQty(productId, newQty);

                const { items, count } = response.data;

                const itemInCart = items.find(
                    (el) => Number(el.product_id) === productId
                );

                const qty = itemInCart ? Number(itemInCart.amount) : 0;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, qty);
                updateCartInfo(response.data);
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
                    }
                );

                // Показ ошибки пользователю
                const message = getErrorMessage(e.code, e.status);
                notification.open(message);
            }

            return;
        }

        // Плюс
        const addItem = target.closest("[data-add-cart]");
        if (addItem) {
            const productId = Number(addItem.dataset.productId);
            if (!productId) return;

            try {
                const response = await addCartItem(productId, 1); // response = { success: true, data: { items, count, total } }

                const { items, count } = response.data;

                const itemInCart = items.find(
                    (el) => Number(el.product_id) === productId
                );

                const qty = itemInCart ? Number(itemInCart.amount) : 0;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, qty);
                updateCartInfo(response.data);
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
            }

            return;
        }

        // Удаление
        const removeItem = target.closest("[data-remove-cart]");
        if (removeItem) {
            const productId = Number(removeItem.dataset.productId);
            if (!productId) return;

            try {
                const response = await removeCartItem(productId);

                const { count } = response.data;

                // Обновляем UI
                updateHeaderCounter(count);
                updateCartItemCounter(productId, 0);
                updateCartInfo(response.data);
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
                    }
                );

                // Показ ошибки пользователю
                const message = getErrorMessage(e.code, e.status);
                notification.open(message);
            }

            return;
        }
    });
}
