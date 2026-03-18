// Импортируем js (подключение этих js в других файлах не требуется)
import {
    getErrorMessage,
    setButtonLoading,
    formatPrice,
    formatDate,
} from "../utils.js";
import { loadYandexMapsScripts, CourierMap, PickupMap } from "../maps/index.js";
import { notification } from "../ui/notification.js";
import { getCart } from "../cart/cart.api.js";
import { createOrderFromCart } from "./orders.api.js";
import { getStores } from "../stores/stores.api.js";

// Функция убирания лоадера
function hideMapLoader(loaderId) {
    const loader = document.getElementById(loaderId);
    if (!loader) return;

    // Из-за стилей переход плавный
    loader.classList.add("checkout_map_loader_hidden");
}

// Функция для обработки ошибки курьерской карты
function handleCourierMapError(error) {
    console.error(
        "[checkout-page] Ошибка инициализации карты курьерской доставки",
        error,
    );

    // Получаем код из ошибки
    const code = error?.message;

    switch (code) {
        // Инициализация карты / скрипта
        case "YMAPS_API_NOT_LOADED":
        case "YMAPS_API_NOT_AVAILABLE":
        case "YMAPS_SCRIPT_LOAD_FAILED":
        case "YANDEX_MAPS_KEY_NOT_FOUND":
        case "MAP_CONTAINER_NOT_FOUND":
        case "COURIER_MAP_REQUIRED_ELEMENTS_NOT_FOUND":
            hideMapLoader("courier-map-loader");
            document
                .getElementById("courier-map-error")
                ?.classList.remove("hidden");
            courierMap?.disableSuggestions();
            break;

        // Пустой адресс при поиске
        case "EMPTY_ADDRESS":
            notification.open("Введите адрес для поиска");
            break;

        // Адрес не найден
        case "ADDRESS_NOT_FOUND":
            notification.open("Адрес не найден");
            break;

        // Адрес не точный (только улица, только дом и тп)
        case "ADDRESS_TOO_IMPRECISE":
            notification.open("Уточните адрес");
            break;

        // Регион не москва или область
        case "INVALID_ADDRESS_REGION":
            notification.open(
                "Доставка осуществляется только по Москве и области",
            );
            break;

        // Время для поиска вышло
        case "GEOCODE_TIMEOUT":
            notification.open(
                "Поиск адреса занял слишком много времени, попробуйте ещё раз",
            );
            break;

        // Ошибки DaData - карта работает, подсказок нет
        case "DADATA_ERROR":
        case "VALIDATION_ERROR":
            notification.open("Подсказки по адресу временно не доступны");
            courierMap?.disableSuggestions();
            break;

        default:
            hideMapLoader("courier-map-loader");
            document
                .getElementById("courier-map-error")
                ?.classList.remove("hidden");
            courierMap?.disableSuggestions();
    }
}

// Функция для обработки ошибки карты самовывоза
function handlePickupMapError(error) {
    console.error(
        "[checkout-page] Ошибка инициализации карты самовывоза",
        error,
    );
    hideMapLoader("pickup-map-loader");
    document.getElementById("pickup-map-error")?.classList.remove("hidden");
}

// Функция для переключения видимости блоков с датой курьерской доставки/самовывоза
function toggleDeliveryDate() {
    const courierDateRow = document.getElementById("courier-date-row");
    const pickupDateRow = document.getElementById("pickup-date-row");
    const courierPostalCode =
        document.getElementById("courier-address")?.dataset.postalCode;
    const pickupStoreId =
        document.getElementById("pickup-address")?.dataset.storeId;

    if (!courierDateRow || !pickupDateRow) return;

    const deliveryType = getCurrentDeliveryType();
    const isCourier = deliveryType === "courier";

    courierDateRow.classList.toggle("hidden", !isCourier || !courierPostalCode);
    pickupDateRow.classList.toggle("hidden", isCourier || !pickupStoreId);
}

// Функция для обработки выбранного адреса курьерской доставки
function onCourierAddressSelected({ address, postalCode }) {
    const addressEl = document.getElementById("courier-address");
    if (!addressEl) return;

    addressEl.textContent = String(address);
    addressEl.dataset.postalCode = String(postalCode ?? "");

    toggleDeliveryDate();
}

// Функция для обработки выбранного магазина для самовывоза
function onPickupStoreSelected({ address, storeId }) {
    const addressEl = document.getElementById("pickup-address");
    if (!addressEl) return;

    addressEl.textContent = String(address);
    addressEl.dataset.storeId = String(storeId ?? "");

    toggleDeliveryDate();
}

// Функция для сверки адреса с уже выбранным
function isAddressSelected({ type, address, storeId }) {
    if (type === "courier") {
        const courierAddressEl = document.getElementById("courier-address");
        if (!courierAddressEl) return false;

        return courierAddressEl.textContent.trim() === String(address);
    }

    if (type === "pickup") {
        const pickupAddressEl = document.getElementById("pickup-address");
        if (!pickupAddressEl) return false;

        return Number(pickupAddressEl.dataset.storeId) === Number(storeId);
    }

    // False если типы не подошли
    return false;
}

// Инициализируем карты
let yandexMapsPromise = null; // промис с загрузкой скриптов Яндекс.Карт
let courierMap = null;
let pickupMap = null;

// Загрузка скриптов для карт, возвращает промис
function loadYandexMapsOnce() {
    if (!yandexMapsPromise) {
        // Передаем промис с загрузкой
        yandexMapsPromise = loadYandexMapsScripts();
    }

    return yandexMapsPromise; // возвращаем промис
}

// Инициализация карты, возвращает объект карт который можно потом использовать
async function initCourierMapOnce() {
    if (courierMap) return courierMap;

    try {
        // Дожидаемся загрузки скриптов карт
        await loadYandexMapsOnce();

        // Создаем объект курьерской карты, в параметрах id элемента для вставки и объект с функциями
        courierMap = new CourierMap("courier-map", {
            addressInputId: "address-search-input",
            suggestionsContainerId: "suggestions-container",
            searchButtonId: "address-search-btn",
            onError: handleCourierMapError,
            onCourierAddressSelected: onCourierAddressSelected,
            isAddressSelected: isAddressSelected,
        });

        // Прячем лоадер и возвращаем объект курьерской карты
        hideMapLoader("courier-map-loader");
        return courierMap;
    } catch (e) {
        handleCourierMapError(e);
    }
}

// Инициализация карты, возвращает объект карт который можно потом использовать
async function initPickupMapOnce() {
    if (pickupMap) return pickupMap;

    try {
        // Дожидаемся загрузки скриптов карт
        await loadYandexMapsOnce();

        // Создаем и возвращаем объект карты самовывоза
        pickupMap = new PickupMap("pickup-map", {
            onError: handlePickupMapError,
            onPickupStoreSelected: onPickupStoreSelected,
            isAddressSelected: isAddressSelected,
        });

        // Загружаем список магазинов
        const stores = await getStores();

        // Рендерим метки на карте самовывоза
        pickupMap.renderStores(stores);

        // Прячем лоадер и возвращаем объект карты самовывоза
        hideMapLoader("pickup-map-loader");
        return pickupMap;
    } catch (e) {
        handlePickupMapError(e);
    }
}

// Функция переключения типа доставки
function setDeliveryMode(mode) {
    // Булева переменная
    const isCourier = mode === "courier";

    const selectCourierBtn = document.getElementById("order-type-courier");
    const selectPickupBtn = document.getElementById("order-type-pickup");

    // Переключаем состояние кнопок
    // Toggle автоматически добавляет или удаляет класс
    if (selectCourierBtn && selectPickupBtn) {
        selectCourierBtn.classList.toggle("chosen", isCourier);
        selectPickupBtn.classList.toggle("chosen", !isCourier);
    }

    // Переключаем видимость элементов
    document
        .querySelectorAll('[data-order-type="courier"]')
        .forEach((el) => el.classList.toggle("hidden", !isCourier));
    document
        .querySelectorAll('[data-order-type="pickup"]')
        .forEach((el) => el.classList.toggle("hidden", isCourier));

    if (checkoutCart) {
        updateCheckoutInfo(checkoutCart);
    }

    if (isCourier) {
        // Запускаем инициализацию нужной карты
        initCourierMapOnce();
    } else {
        initPickupMapOnce();
    }

    toggleDeliveryDate();
}

// Функция для создания отдельного блока с товаром
function createCheckoutItemEl(item) {
    // Создаем блок товара
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

// Функция для заполнения инф о всех товарах из корзины
function fillCheckoutItems(cartItems) {
    const itemsContainer = document.getElementById("checkout-items-container");
    if (!itemsContainer || !cartItems.length) return;

    // Очищаем содержимое контейнера
    itemsContainer.innerHTML = "";

    cartItems.forEach((item) => {
        // Рендерим через функцию блок товара
        const itemEl = createCheckoutItemEl(item);

        // Добавляем товар в контейнер
        itemsContainer.appendChild(itemEl);
    });
}

// Обновление отображения условий бесплатной доставки
function updateCourierDeliveryNote() {
    const deliveryNoteEl = document.getElementById("checkout-delivery-note");
    if (!deliveryNoteEl) return;

    const deliveryConfig = window.GYM_BOSS_DELIVERY ?? {};
    const threshold = Number(deliveryConfig.courierFreeThreshold ?? 0);

    if (threshold > 0) {
        deliveryNoteEl.textContent = `(бесплатно при заказе от ${formatPrice(threshold)} ₽)`;
    }
}

// Получение выбранного способа доставки: courier или pickup
function getCurrentDeliveryType() {
    const courierBtn = document.getElementById("order-type-courier");
    if (!courierBtn) return "courier"; // значение по умолчанию

    return courierBtn.classList.contains("chosen") ? "courier" : "pickup";
}

// Функция для подсчета стоимости доставки
function calcDeliveryPrice(cartTotal, deliveryType) {
    const deliveryConfig = window.GYM_BOSS_DELIVERY ?? {};
    const threshold = Number(deliveryConfig.courierFreeThreshold ?? 0);
    const deliveryPrice = Number(deliveryConfig.courierPrice ?? 0);

    // Если доставка не курьрером - то бесплатная
    if (deliveryType !== "courier") {
        return 0;
    }

    // Если стоимость корзины меньше порога возвращаем стоимость доставки, иначе 0
    return cartTotal < threshold ? deliveryPrice : 0;
}

// Функция для получения текста даты доставки (с ... до ...) по типу доставки (по переменным из конфига)
function getDeliveryDateText(deliveryType) {
    const now = new Date();
    const deliveryConfig = window.GYM_BOSS_DELIVERY ?? {};

    // Объявляем переменные окна времени доставки/готовности самовывоза
    let fromHours = null;
    let toHours = null;

    // Заполняем переменные
    if (deliveryType === "courier") {
        fromHours = deliveryConfig.courierDeliveryFromHours
            ? Number(deliveryConfig.courierDeliveryFromHours)
            : null;

        toHours = deliveryConfig.courierDeliveryToHours
            ? Number(deliveryConfig.courierDeliveryToHours)
            : null;
    } else if (deliveryType === "pickup") {
        fromHours = deliveryConfig.pickupReadyFromHours
            ? Number(deliveryConfig.pickupReadyFromHours)
            : null;

        toHours = deliveryConfig.pickupReadyToHours
            ? Number(deliveryConfig.pickupReadyToHours)
            : null;
    }

    // Проверяем что часы заданы
    if (!fromHours || !toHours) {
        console.warn(
            "[order-page] Часы доставки не заполнены для доставки типа:",
            deliveryType,
        );
        return null;
    }

    // Вычисляем даты
    const fromDate = new Date(now.getTime() + fromHours * 60 * 60 * 1000);
    const toDate = new Date(now.getTime() + toHours * 60 * 60 * 1000);

    // Форматируем даты
    const fromText = formatDate(fromDate);
    const toText = formatDate(toDate);

    if (!fromText || !toText) {
        console.error("[order-page] Не удалось форматировать даты");
        return null;
    }

    return `с ${fromText} до ${toText}`;
}

// Функция для обновления общей инфы из корзины
function updateCheckoutInfo(cart) {
    const itemsAmountEl = document.getElementById("checkout-items-count");
    const itemsPriceEl = document.getElementById("checkout-items-price");
    const deliveryPriceEl = document.getElementById("checkout-delivery-price");
    const totalPriceEl = document.getElementById("checkout-total-price");
    const courierDateEl = document.getElementById("courier-date-text");
    const pickupDateEl = document.getElementById("pickup-date-text");
    if (!itemsAmountEl || !itemsPriceEl || !deliveryPriceEl || !totalPriceEl) {
        return;
    }

    const currentDeliveryType = getCurrentDeliveryType();
    const cartItemsTotal = Number(cart.total ?? 0);

    const deliveryPrice = calcDeliveryPrice(
        cartItemsTotal,
        currentDeliveryType,
    );

    const totalPrice = cartItemsTotal + deliveryPrice;

    itemsAmountEl.textContent = String(cart.count);
    itemsPriceEl.textContent = String(formatPrice(cartItemsTotal));
    deliveryPriceEl.textContent = String(formatPrice(deliveryPrice));
    totalPriceEl.textContent = String(formatPrice(totalPrice));
    courierDateEl.textContent = getDeliveryDateText("courier");
    pickupDateEl.textContent = getDeliveryDateText("pickup");
}

// Глобальная переменная с полной ифной о корзине, заполняется один раз при загрузке страницы
let checkoutCart = null;

// При загрузке страницы по умолчанию ставим доставку курьером, загружаем корзину и заполняем контейнер товарами
window.addEventListener("DOMContentLoaded", async () => {
    setDeliveryMode("courier");

    try {
        const cart = await getCart();
        const cartItems = cart.items ?? [];
        // Если в корзине нет товаров на момент оформления заказа то перекидываем на страницу корзины
        if (!cartItems.length || Number(cart.count ?? 0) === 0) {
            window.location.href = "/cart";
            return;
        }

        // Сохраняем полученную корзину в локальную переменную
        checkoutCart = cart;

        // Рендерим информацию о заказе и товарах
        fillCheckoutItems(cartItems);
        updateCourierDeliveryNote();
        updateCheckoutInfo(cart);
    } catch (e) {
        // Логирование в консоль с полным контекстом
        console.error(
            "[checkout-page] Не удалось получить корзину пользователя",
            {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // тот самый data
            },
        );

        // Показ ошибки пользователю
        const message = getErrorMessage(e.code, e.status);
        notification.open(message);
    }
});

// Обработчик кнопки переключения на курьерскую доставку
document.getElementById("order-type-courier").addEventListener("click", () => {
    // Если тип уже выбран - ничего не делаем
    if (getCurrentDeliveryType() === "courier") return;

    setDeliveryMode("courier");
});

// Обработчик кнопки переключения на самовывоз
document.getElementById("order-type-pickup").addEventListener("click", () => {
    // Если тип уже выбран - ничего не делаем
    if (getCurrentDeliveryType() === "pickup") return;

    setDeliveryMode("pickup");
});

// Находим кнопку "оформить заказ"
const createOrderBtn = document.getElementById("create-order-btn");
// Если кнопка нашлась - вешаем обработчик
if (!createOrderBtn) {
    console.error("[checkout-page] Не найдена createOrderBtn");
} else {
    createOrderBtn.addEventListener("click", async () => {
        // Определяем переменные для использования метода создания заказа
        const selectedDeliveryType = getCurrentDeliveryType(); // выбранный тип доставки
        const checkoutTypeId = selectedDeliveryType === "courier" ? 1 : 2; // id выбранного типа доставки

        let checkoutAddressText = null;
        let checkoutAddressPostCode = null;
        let checkoutStoreId = null;

        // Присваиваем адресу выбранное значение, если выбрана курьерская доставка
        if (selectedDeliveryType === "courier") {
            const courierAddressEl = document.getElementById("courier-address");
            if (!courierAddressEl) {
                console.error(
                    "[checkout-page] Не удалось найти  courierAddressEl при оформлении заказа",
                );
                notification.open(
                    "Не удалось оформить заказ, обновите страницу или попробуйте позже",
                );
                return;
            }

            checkoutAddressText = courierAddressEl.textContent?.trim() ?? null;
            checkoutAddressPostCode =
                courierAddressEl.dataset.postalCode ?? null;
            if (!checkoutAddressPostCode) {
                notification.open("Укажите адрес доставки");
                return;
            }
        }

        // Присваиваем адресу выбранное значение, если выбран самовывоз
        if (selectedDeliveryType === "pickup") {
            const pickupAddressEl = document.getElementById("pickup-address");
            if (!pickupAddressEl) {
                console.error(
                    "[checkout-page] Не удалось найти pickupAddressEl при оформлении заказа",
                );
                notification.open(
                    "Не удалось оформить заказ, обновите страницу или попробуйте позже",
                );
                return;
            }

            checkoutStoreId = pickupAddressEl.dataset.storeId
                ? Number(pickupAddressEl.dataset.storeId)
                : null;
            if (!checkoutStoreId) {
                notification.open("Выберите магазин для самовывоза");
                return;
            }
        }

        try {
            // Устанавливаем состояние загрузки на кнопку
            setButtonLoading(createOrderBtn, true);

            const result = await createOrderFromCart({
                deliveryTypeId: checkoutTypeId,
                deliveryAddressText: checkoutAddressText,
                deliveryPostalCode: checkoutAddressPostCode,
                storeId: checkoutStoreId,
            });

            const orderId = result?.order_id;
            if (!orderId) {
                console.error(
                    "[checkout-page] Не удалось найти orderId в ответе от сервера",
                );
                notification.open("Не удалось создать заказ, попробуйте позже");
                return;
            }

            const uriOrderId = encodeURI(String(orderId));

            window.location.href = `/orders/${uriOrderId}`;
        } catch (e) {
            // Логирование в консоль с полным контекстом
            console.error("[checkout-page] Не удалось оформить заказ", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload, // тот самый data
            });

            // Показ ошибки пользователю
            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Убираем состояние загрузки с кнопки
            setButtonLoading(createOrderBtn, false);
        }
    });
}
