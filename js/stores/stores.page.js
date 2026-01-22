// Показ ошибки списка магазинов
function showStoresListError(error) {
    console.error("Ошибка загрузки магазинов:", error);
    const container = document.getElementById("stores-list");
    if (container) {
        container.innerHTML = "<p>Магазины временно недоступны</p>";
    }
}

// Список магазинов
async function initStoresList() {
    // Загружаем магазины из БД, перебор магазинов и отображение в список
    try {
        // Если нет контейнера - ошибку
        const container = document.getElementById("stores-list");
        if (!container) {
            throw new Error("stores-list не найден");
        }

        // Получаем магазины из api
        const response = await fetch("/src/getStores.php");
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const stores = await response.json();

        // Очищаем содержимое блока
        container.innerHTML = "";

        stores.forEach((store) => {
            if (store.coordinates && store.coordinates.length === 2) {
                // Получаем ссылку на магазин на картах (на адресс)
                const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address.replace(/<br>/g, ", "))}`;

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

                // Адрес магазина
                const addressDiv = document.createElement("div");
                addressDiv.className = "store_address";

                const addressTitle = document.createElement("span");
                addressTitle.innerHTML = "Адрес:<br>";
                const addressContent = document.createElement("span");
                addressContent.innerHTML = store.address;

                addressDiv.appendChild(addressTitle);
                addressDiv.appendChild(addressContent);

                // Время работы
                const timeDiv = document.createElement("div");
                timeDiv.className = "store_time";

                const timeTitle = document.createElement("span");
                timeTitle.innerHTML = "Время работы:<br>";

                const timeContent = document.createElement("span");
                timeContent.innerHTML = store.work_hours.replace(/\n/g, "<br>");

                timeDiv.appendChild(timeTitle);
                timeDiv.appendChild(timeContent);

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

                // Кнопка "На карте"
                const mapLink = document.createElement("a");
                mapLink.href = yandexMapsUrl;
                mapLink.target = "_blank";

                const mapButton = document.createElement("div");
                mapButton.className = "store_button";
                mapButton.textContent = "На карте";

                mapLink.appendChild(mapButton);

                // Собираем всё в один блок
                storeDiv.appendChild(nameDiv);
                storeDiv.appendChild(addressDiv);
                storeDiv.appendChild(timeDiv);
                storeDiv.appendChild(phoneDiv);
                storeDiv.appendChild(mapLink);

                // Добавляем магазин в контейнер
                container.appendChild(storeDiv);

                // // Меняем HTML блока через .innerHTML

                // const storeHTML = `
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
                // container.innerHTML += storeHTML;
            }
        });
    } catch (error) {
        showStoresListError(error);
    }
}

// нужно инициализировать карту магазинов, после этого скрывать лоадер
