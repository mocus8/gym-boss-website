// Импортируем js (подключение этих js в других файлах не требуется)
import { getErrorMessage } from "../utils.js";
import { loadYandexMapsScripts, StoresMap } from "../maps/index.js";
import { notification } from "../ui/notification.js";
import { getStores } from "../stores/stores.api.js";

// Функция для получения элемента магазина для списка магазинов
function createStoreEl(store) {
    const li = document.createElement("li");
    li.classList.add("stores__item");
    li.classList.add("shape-cut-corners--diagonal");

    // Название магазина
    const title = document.createElement("p");
    title.classList.add("stores__item-title");
    title.textContent = store.name;
    li.appendChild(title);

    // Адрес
    const addressTitle = document.createElement("p");
    addressTitle.textContent = "Адрес:";
    li.appendChild(addressTitle);

    const address = document.createElement("p");
    address.textContent = store.address;
    li.appendChild(address);

    // Время работы
    const hoursTitle = document.createElement("p");
    hoursTitle.textContent = "Время работы:";
    li.appendChild(hoursTitle);

    const hours = document.createElement("p");
    hours.textContent = store.work_hours;
    li.appendChild(hours);

    // Телефон
    const phone = document.createElement("a");
    phone.href = `tel:${store.phone}`;
    phone.textContent = `Телефон: ${store.phone}`;
    li.appendChild(phone);

    // Ссылка на карте
    const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address)}`;

    const linkShell = document.createElement("a");
    linkShell.classList.add("link-shell");
    linkShell.href = yandexMapsUrl;
    linkShell.target = "_blank";
    linkShell.rel = "noopener noreferrer";

    const linkBtn = document.createElement("span");
    linkBtn.classList.add("btn", "shape-cut-corners--diagonal");
    linkBtn.textContent = "На карте";
    linkShell.appendChild(linkBtn);

    li.appendChild(linkShell);

    return li;
}

// Функция для рендера списка магазинов
function renderStoresList(stores) {
    const storesContainer = document.getElementById("stores-container");
    if (!storesContainer) return;

    // Очищаем содержимое контейнера
    storesContainer.innerHTML = "";

    // Заполняем контейнер
    stores.forEach((store) => {
        // Рендерим через функцию блок товара
        const storeEl = createStoreEl(store);

        // Добавляем товар в контейнер
        storesContainer.appendChild(storeEl);
    });
}

// Функция для скрытия лоадера на карте магазинов
function hideMapLoader() {
    const loader = document.getElementById("stores-map-loader");
    if (!loader) return;

    loader.hidden = true;
}

// Функция для обработки ошибки карты магазинов
function handleStoresMapError(e) {
    const storesMapErrorEl = document.getElementById("stores-map-error");
    if (!storesMapErrorEl) return;

    console.error("[stores-page] Ошибка инициализации карты магазинов", e);

    hideMapLoader();
    storesMapErrorEl.hidden = false;
}

// Функция для инициализации и отображения карты магазинов
async function initStoresMap(stores) {
    try {
        // Дожидаемся загрузки скриптов карт
        await loadYandexMapsScripts();

        // Создаем и возвращаем объект карты самовывоза
        const storesMap = new StoresMap("stores-map", {
            onError: handleStoresMapError,
        });

        // Рендерим метки на карте самовывоза
        storesMap.renderStores(stores);

        // Прячем лоадер
        hideMapLoader();

        // Возвращаем объект карты магазинов
        return storesMap;
    } catch (e) {
        handleStoresMapError(e);
    }
}

// Показ ошибки списка магазинов
function showStoresListError() {
    const container = document.getElementById("stores-container");
    if (!container) return;

    const messageEl = document.createElement("p");
    messageEl.classList.add("stores__container-message");
    messageEl.textContent = "Список магазинов временно недоступен :(";

    container.innerHTML = "";
    container.appendChild(messageEl);
}

window.addEventListener("DOMContentLoaded", async () => {
    const storesLoader = document.getElementById("stores-loader");
    if (!storesLoader) return;

    try {
        const stores = await getStores();

        renderStoresList(stores);
        initStoresMap(stores);
    } catch (e) {
        // Логирование в консоль с полным контекстом
        console.error("[stores-page] Не удалось получить список маназинов", {
            message: e.message,
            code: e.code,
            status: e.status,
            payload: e.payload, // тот самый data
        });

        // Показ ошибки пользователю
        showStoresListError();
        handleStoresMapError(e);
        const message = getErrorMessage(e.code, e.status);
        notification.open(message);
    } finally {
        storesLoader.hidden = true;
    }
});
