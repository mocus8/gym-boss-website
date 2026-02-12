// Импортируем js (подключение этих js в других файлах не требуется)
import { getErrorMessage } from "../utils.js";
import { loadYandexMapsScripts, StoresMap } from "../maps/index.js";
import { notification } from "../ui/notification.js";
import { getStores } from "../store/store.api.js";

// Функция для получения элемента магазина для списка магазинов
function createStoreEl(store) {
    // Меняем HTML блока через работу с DOM узлами

    // Корневой блок магазина
    const storeDiv = document.createElement("div");
    storeDiv.className = "store";

    // Название магазина
    const nameDiv = document.createElement("div");
    nameDiv.className = "store_name";
    const nameStrong = document.createElement("strong");
    nameStrong.textContent = store.name;
    nameDiv.appendChild(nameStrong);
    storeDiv.appendChild(nameDiv);

    // Адрес магазина
    const addressDiv = document.createElement("div");
    addressDiv.className = "store_address";
    const addressTitle = document.createElement("span");
    addressTitle.innerHTML = "Адрес:<br>";
    const addressContent = document.createElement("span");
    addressContent.innerText = store.address;
    addressDiv.appendChild(addressTitle);
    addressDiv.appendChild(addressContent);
    storeDiv.appendChild(addressDiv);

    // Время работы
    const timeDiv = document.createElement("div");
    timeDiv.className = "store_time";
    const timeTitle = document.createElement("span");
    timeTitle.innerHTML = "Время работы:<br>";
    const timeContent = document.createElement("span");
    timeContent.innerText = store.work_hours.replace(/\n/g, "\n");
    timeDiv.appendChild(timeTitle);
    timeDiv.appendChild(timeContent);
    storeDiv.appendChild(timeDiv);

    // Телефон
    const phoneDiv = document.createElement("div");
    phoneDiv.className = "store_time";
    const phoneTitle = document.createElement("span");
    phoneTitle.innerHTML = "Телефон:<br>";
    const phoneLink = document.createElement("a");
    phoneLink.href = `tel:${store.phone}`;
    phoneLink.className = "colour_href";
    const phoneInnerDiv = document.createElement("div");
    phoneInnerDiv.style.marginTop = "10px";
    phoneInnerDiv.textContent = store.phone;
    phoneLink.appendChild(phoneInnerDiv);
    phoneDiv.appendChild(phoneTitle);
    phoneDiv.appendChild(phoneLink);
    storeDiv.appendChild(phoneDiv);

    // Кнопка "На карте"
    const mapLink = document.createElement("a");
    // Получаем ссылку на магазин на картах (на адресс)
    const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address.replace(/<br>/g, ", "))}`;
    mapLink.href = yandexMapsUrl;
    mapLink.target = "_blank";
    const mapButton = document.createElement("div");
    mapButton.className = "store_button";
    mapButton.textContent = "На карте";
    mapLink.appendChild(mapButton);
    storeDiv.appendChild(mapLink);

    // // Меняем HTML блока через .innerHTML

    // const storeDiv = `
    //     <div class="store">
    //         <div class="store_name">
    //         <strong>${store.name}</strong>
    //         </div>
    //         <div class="store_address">
    //             Адрес:<br>
    //             ${store.address}
    //         </div>
    //         <div class="store_time">
    //             Время работы:<br>
    //             ${store.work_hours.replace(/\n/g, "<br>")}
    //         </div>
    //         <div class="store_time">
    //             Телефон:<br>
    //             <a href='tel: ${store.phone}' class="colour_href">
    //                 <div style="margin-top: 10px;">${store.phone}</div>
    //             </a>
    //         </div>
    //         <a href="${yandexMapsUrl}" target="_blank">
    //             <div class="store_button">
    //                 На карте
    //             </div>
    //         </a>
    //     </div>
    // `;

    return storeDiv;
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
    loader.classList.add("stores_map_loader_hidden"); // из-за стилей переход плавный
}

// Функция для обработки ошибки карты магазинов
function handleStoresMapError(e) {
    console.error("[stores-page] Ошибка инициализации карты магазинов", e);
    hideMapLoader();
    document.getElementById("stores-map-error")?.classList.remove("hidden");
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
    if (container) {
        container.innerHTML =
            '<div class="cart_empty">Список магазинов временно недоступен</div>';
    }
}

window.addEventListener("DOMContentLoaded", async () => {
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
    }
});
