/* global ymaps */

// Кешируемый промис для загрузки скриптов для Яндекс.Карт
// Типа флага, но асинхронный, его можно ожидать await
let yandexMapsPromise = null;

// Функция для загрузки скриптов Яндекс.Карт, возвращает промис
export function loadYandexMapsScripts() {
    // Если промис уже создан, возвращаем его
    if (yandexMapsPromise) return yandexMapsPromise;

    // Присваиваем промису значение функции
    yandexMapsPromise = new Promise((resolve, reject) => {
        // Если скрипт уже загружен, резолвим промис (делаем его готовым)
        if (window.ymaps && typeof window.ymaps.ready === "function") {
            window.ymaps.ready(resolve);
            return;
        }

        // Создаем тег <script> в памяти (не реально в документе)
        const script = document.createElement("script");

        // Получаем api ключ для карт
        const key = document.body?.dataset?.yandexMapsKey;
        if (!key) {
            reject(new Error("YANDEX_MAPS_KEY_NOT_FOUND"));
            return;
        }

        // Собираем URL для скрипта
        const url = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(key)}&lang=ru_RU&load=package.full`;

        // Вставляем в скрипт URL
        script.src = url;
        script.async = true;

        // Обработчик на загрузку скрипта
        script.onload = () => {
            if (!window.ymaps || typeof window.ymaps.ready !== "function") {
                reject(new Error("YMAPS_API_NOT_AVAILABLE"));
                return;
            }
            window.ymaps.ready(resolve); // после загрузки карт промис выполнен успешно
        };

        // Обработчик на ошибку скрипта
        script.onerror = () => {
            reject(new Error("YMAPS_SCRIPT_LOAD_FAILED"));
        };

        // Вставляем скрипт в DOM
        document.head.appendChild(script);
    });

    return yandexMapsPromise;
}

// Кешируемый промис для загрузки скриптов для DaData
// Типа флага, но асинхронный, его можно ожидать await
let daDataPromise = null;

// Функция для загрузки скриптов DaData, возвращает промис
function loadDaDataScripts() {
    // Если промис уже создан, возвращаем его
    if (daDataPromise) return daDataPromise;

    // Присваиваем промису значение функции
    daDataPromise = new Promise((resolve, reject) => {
        // Если скрипт уже загружен, резолвим промис (делаем его готовым)
        if (window.Dadata) {
            resolve();
            return;
        }

        // Создаем тег <script> в памяти (не реально в документе)
        const script = document.createElement("script");

        // URL для скрипта
        const url = `https://cdn.jsdelivr.net/npm/@dadata/suggestions@25.4.1/dist/suggestions.min.js`;

        // Вставляем в скрипт URL
        script.src = url;
        script.async = true;

        // Обработчик на загрузку скрипта
        script.onload = () => {
            if (!window.Dadata) {
                reject(new Error("DADATA_API_NOT_AVAILABLE"));
                return;
            }
            resolve(); // промис выполнен успешно
        };

        // Обработчик на ошибку скрипта
        script.onerror = () => {
            reject(new Error("DADATA_SCRIPT_LOAD_FAILED"));
        };

        // Вставляем скрипт в DOM
        document.head.appendChild(script);
    });

    return daDataPromise;
}

// Класс StoresMap
export class StoresMap {
    #map = null; // объект Яндекс.Карты
    #isInitialized = false; // флаг создания карты
    #onError; // функция обработки ошибки, передается в конструкторе

    // Конструктор, options = {} - поля с опциями (например функцией для показа ошибки onError)
    constructor(containerId, options = {}) {
        const { onError } = options; // деструктуризация опций, можно добавить другие опции
        this.#onError = typeof onError === "function" ? onError : null; // сохраняем функцию показа ошибки (либо null если не функция)

        try {
            // Проверка API Яндекса
            if (!window.ymaps || typeof ymaps.Map !== "function") {
                throw new Error("YMAPS_API_NOT_LOADED");
            }

            // Принимаем контейнер для карты
            const container = document.getElementById(containerId);
            if (!container) {
                throw new Error("MAP_CONTAINER_NOT_FOUND");
            }

            // Защита от повторного вызова
            if (container.children.length > 0) {
                return;
            }

            // Присваиваем полю карты значение новой карты
            this.#map = new ymaps.Map(containerId, {
                center: [55.76, 37.64],
                zoom: 10,
                controls: ["zoomControl"],
            });

            // Закрываем балуны при клике на карту
            this.#map.events.add("click", () => {
                this.#map.balloon.close();
            });

            this.#isInitialized = true;
        } catch (error) {
            this.#handleError(error);
            throw error;
        }
    }

    // Метод для рендера магазинов на карте
    renderStores(stores) {
        if (!this.#isInitialized) return;

        stores.forEach((store) => {
            // Проверяем валидность координат
            if (
                typeof store.latitude !== "number" ||
                typeof store.longitude !== "number"
            )
                return;

            // Соеденяем широту и долготу в массив кординат
            const coords = [store.latitude, store.longitude];

            const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address)}`;

            // Разбиваем часы работы по переносам строк
            const workHoursHtml = store.workHours
                ? store.workHours
                      .split("\n")
                      .map((line) => line.trim())
                      .filter((line) => line !== "")
                      .map((line) => `<div>${line}</div>`)
                      .join("")
                : "";

            const placemark = new ymaps.Placemark(
                coords,
                {
                    balloonContent: `
                        <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
                            <div style="margin-bottom: 15px;">
                                <strong>
                                    ${store.address}
                                </strong>

                                <br>

                                <div style="margin-top: 10px;">
                                    ${workHoursHtml}
                                </div>
                                <a href='tel:${store.phone}' class="colour_href">
                                    <div style="margin-top: 10px;">
                                        ${store.phone}
                                    </div>
                                </a>
                            </div>

                            <div style="text-align: center;">
                                <a href="${yandexMapsUrl}" target="_blank" style="
                                    display: inline-block;
                                    background: #4B4B4B;
                                    color: black;
                                    border: 0.15vw solid black;
                                    padding: 3% 5%;
                                    border-radius: 0.5vw;
                                ">
                                    Открыть в Картах
                                </a>
                            </div>
                        </div>
                    `,
                },
                {
                    iconLayout: "default#image",
                    iconImageHref: "/img/custom-map-pin.png",
                    iconImageSize: [60, 55],
                    iconImageOffset: [-20, -40],

                    balloonCloseButton: true,
                    balloonAutoPan: true,
                },
            );

            this.#map.geoObjects.add(placemark);
        });

        // Автоподбор области карты по всем меткам
        if (this.#map.geoObjects.getLength() > 0) {
            this.#map.setBounds(this.#map.geoObjects.getBounds(), {
                checkZoomRange: true,
                zoomMargin: 20,
            });
        }
    }

    // Метод уничтожения карты
    destroy() {
        if (!this.#isInitialized) return;

        if (this.#map) {
            this.#map.destroy();
            this.#map = null;
        }

        this.#isInitialized = false;
    }

    // Приватный метод обработки ошибок
    #handleError(error) {
        console.error("[StoresMap]", error);
        if (this.#onError) this.#onError(error);
    }
}

// ES6 Класс для карты доставки, конструктор с параметром (контейнер для карты, поле для опций)
export class CourierMap {
    #map = null; // объект Яндекс.Карты
    #marker = null; // текущий маркер адреса
    #addressInput = null; // input ввода адреса
    #searchBtn = null; // кнопка поиска
    #isInitialized = false; // флаг создания карты
    #daDataInitialized = false; // флаг инициализации DaData
    #onError; // функция обработки ошибки, передается в конструкторе
    #onCourierAddressSelected; // функция обработки выбора адреса, передается в конструкторе
    #isAddressSelected; // функция проверки сравенения адреса с выбраным

    // конструктор с параметром, в параметре id контейнера для карты и опции (например функцией для показа ошибки onError)
    constructor(containerId, options = {}) {
        const {
            addressInputId,
            searchButtonId,
            onError,
            onCourierAddressSelected,
            isAddressSelected,
        } = options; // деструктуризация опций, можно добавить другие опции

        // Проверка что переданы имменно функции
        this.#onCourierAddressSelected =
            typeof onCourierAddressSelected === "function"
                ? onCourierAddressSelected
                : null;
        this.#isAddressSelected =
            typeof isAddressSelected === "function" ? isAddressSelected : null;
        this.#onError = typeof onError === "function" ? onError : null;

        try {
            // Проверка API Яндекса
            if (!window.ymaps || typeof ymaps.Map !== "function") {
                throw new Error("YMAPS_API_NOT_LOADED");
            }

            // Принимаем контейнер для карты
            const container = document.getElementById(containerId);
            if (!container) {
                throw new Error("MAP_CONTAINER_NOT_FOUND");
            }

            // Защита от повторного вызова
            if (container.children.length > 0) return;

            // Получаем элементы страницы
            this.#addressInput = addressInputId
                ? document.getElementById(addressInputId)
                : null;
            this.#searchBtn = searchButtonId
                ? document.getElementById(searchButtonId)
                : null;

            // Если элементы не найдены - ошибку
            if (!this.#addressInput || !this.#searchBtn) {
                throw new Error("COURIER_MAP_REQUIRED_ELEMENTS_NOT_FOUND");
            }

            // Присваиваем полю карты значение новой карты
            this.#map = new ymaps.Map(containerId, {
                center: [55.76, 37.64],
                zoom: 10,
                controls: ["zoomControl"],
            });

            // Инициализируем
            this.#setupEvents();
            // Запускаем асинхронный код и навешиваем на него .catch(...)
            this.#initDaData().catch((error) => this.#handleError(error));
            this.#isInitialized = true;
        } catch (error) {
            this.#handleError(error);
            throw error;
        }
    }

    // Очистка карты
    clear() {
        if (!this.#isInitialized) return;

        if (this.#marker) {
            this.#map.geoObjects.remove(this.#marker);
            this.#marker = null;
        }

        // сбрасываем карту к центру
        if (this.#map) {
            this.#map.setCenter([55.76, 37.64], 8);
        }
    }

    // удаление карты
    destroy() {
        if (!this.#isInitialized) return;

        if (this.#map) {
            this.#map.destroy(); // метод яндекс карт
            this.#map = null;
        }

        // Удаляем обработчик с кнопки "найти"
        if (this.#searchBtn) {
            this.#searchBtn.replaceWith(this.#searchBtn.cloneNode(true));
        }

        // Удаляем обработчик с инпута адреа
        if (this.#addressInput) {
            this.#addressInput.replaceWith(this.#addressInput.cloneNode(true));
        }

        this.#marker = null;
        this.#addressInput = null;
        this.#searchBtn = null;
        this.#isInitialized = false;
        this.#daDataInitialized = false;
    }

    // Геттер для получения состояния карты (инициализирована ли)
    get isInitialized() {
        return this.#isInitialized;
    }

    // Приватный метод обработки ошибок
    #handleError(error) {
        console.error("[CourierMap]", error);
        if (this.#onError) this.#onError(error);
    }

    // Санитизация ввода
    #sanitizeAddress(address) {
        return String(address || "")
            .replace(/[<>"`\\/]/g, "") // Удаляем самые опасные
            .replace(/'/g, "’") // Заменяем апостроф на красивый
            .replace(/&/g, "и") // Заменяем & на "и"
            .replace(/\s+/g, " ")
            .trim()
            .substring(0, 200); // Ограничить длину
    }

    // Показать адрес на карте
    #showAddressOnMap(geoObject) {
        if (!this.#isInitialized) return;

        const coords = geoObject.geometry.getCoordinates();
        const addressRaw = geoObject.getAddressLine();
        const postalCode = geoObject.properties.get(
            "metaDataProperty.GeocoderMetaData.Address.postal_code",
        );

        // Очищаем адрес
        const address = this.#sanitizeAddress(
            addressRaw.replace(/^Россия,\s*/, ""),
        );

        // если уже есть какой-то маркер то убираем его
        if (this.#marker) {
            this.#map.geoObjects.remove(this.#marker);
        }

        this.#marker = new ymaps.Placemark(
            coords,
            {
                balloonContent: `
                <div style="
                    min-width: 250px;
                    font-size: 120% !important;
                    font-family: 'Jost', Arial !important;
                ">
                    <strong>
                        Адрес доставки:
                    </strong>

                    <br>

                    ${address}

                    <div style="
                        margin-top: 5%;
                        text-align: center;
                    ">
                        <button id="select-courier-address" style="
                            border: 0.15vw solid black;
                            padding: 3% 5%;
                            padding-left: 2%;
                            border-radius: 0.5vw;
                            cursor: pointer; 
                            background: #4B4B4B !important;
                        ">
                            ✅ Доставить сюда
                        </button>
                    </div>
                </div>
            `,
            },
            {
                iconLayout: "default#image",
                iconImageHref: "/img/custom-map-pin.png",
                iconImageSize: [60, 55],
                iconImageOffset: [-20, -40],

                balloonCloseButton: true,
                balloonAutoPan: true,
            },
        );

        // Добавляем маркер
        this.#map.geoObjects.add(this.#marker);
        this.#map.setCenter(coords, 16);

        // Обработчик события открытия балуна
        this.#marker.events.add("balloonopen", () => {
            // Ждем минимальную задержку чтобы элемент успел отрендериться
            setTimeout(() => {
                // Находим кнопку выбора адреса из балуна
                const btn = document.getElementById("select-courier-address");
                if (!btn || !this.#onCourierAddressSelected) return;

                // Булева переменная - выбран ли уже этот адрес, в параметре объект с типом и адресом
                const selectedAlready = this.#isAddressSelected
                    ? this.#isAddressSelected({
                          type: "courier",
                          address: address,
                      })
                    : false;

                // Мгновенно обновляем кнопку если адрес выбран
                if (selectedAlready) {
                    btn.textContent = "✅ Адрес выбран";
                    btn.style.cursor = "default";
                    btn.style.pointerEvents = "none";
                    btn.disabled = true;
                    return;
                }

                // Обычная логика для невыбранного адреса

                // Если уже есть обработчик - выходим
                if (btn.hasAttribute("data-listener-added")) return;

                // Навешиваем обработчик на кнопку выбора адреса
                btn.setAttribute("data-listener-added", "true");
                // При клике вызываем внешнею функциею обработки выбора адреса и меняем состояие кнопки
                btn.addEventListener(
                    "click",
                    () => {
                        this.#onCourierAddressSelected({
                            address: address,
                            postalCode,
                        });

                        btn.textContent = "✅ Адрес выбран";
                        btn.style.cursor = "default";
                        btn.style.pointerEvents = "none";
                        btn.disabled = true;
                    },
                    { once: true },
                );
            }, 0); // Минимальная задержка
        });

        this.#marker.balloon.open();
    }

    // Обработка адреса
    async #processAddress(addressInput) {
        try {
            const addressQuery = this.#sanitizeAddress(addressInput);
            if (!addressQuery) {
                throw new Error("EMPTY_ADDRESS");
            }

            const normalizedAddressQuery = addressQuery
                .replace(/[.,]/g, "")
                .replace(/\s+/g, " ")
                .trim();

            if (this.#searchBtn) {
                this.#searchBtn.disabled = true;
                this.#searchBtn.textContent = "Поиск...";
            }

            // Добавляем таймаут на поиск адреса
            let isGeocodeTimedOut = false;
            const geocodeTimeout = setTimeout(() => {
                isGeocodeTimedOut = true; // устанавливаем флаг таймаута
                this.#handleError(new Error("GEOCODE_TIMEOUT"));
            }, 10000); // время таймаута - 10 сек

            const res = await ymaps.geocode(normalizedAddressQuery);

            // Если таймаут сработал то выходим
            if (isGeocodeTimedOut) return;

            // Дошли сюда, таймаут не сработал - очищаем таймаут
            clearTimeout(geocodeTimeout);

            // Получаем первый результат, если его нет - ошибку
            const firstResult = res.geoObjects.get(0);
            if (!firstResult) {
                throw new Error("ADDRESS_NOT_FOUND");
            }

            // Показываем на карте
            this.#showAddressOnMap(firstResult);
        } catch (error) {
            this.#handleError(error);
        } finally {
            if (this.#searchBtn) {
                this.#searchBtn.disabled = false;
                this.#searchBtn.textContent = "Найти";
            }
        }
    }

    // Инициализация подсказок DaData по адресу
    async #initDaData() {
        if (this.#daDataInitialized || !this.#addressInput) return;

        try {
            // Ждем загрузки скриптов DaData
            await loadDaDataScripts();

            // Проверяем доступность DaData
            if (!window.Dadata) {
                throw new Error("DADATA_API_NOT_AVAILABLE");
            }

            const response = await fetch("/src/serviceProxy.php");

            if (!response.ok) {
                console.error(`[DaData] HTTP error status: ${response.status}`);
                throw new Error("DADATA_KEY_HTTP_ERROR");
            }

            const data = await response.json();
            const token = data?.key;

            if (!token) {
                throw new Error("DADATA_KEY_NOT_FOUND");
            }

            // Используем стандартный виджет DaData для создания подсказок
            window.Dadata.createSuggestions(this.#addressInput, {
                token: token,
                type: "address",
                count: 5,
                // Встроенный обработчик выбора подсказки от DaData
                onSelect: (suggestion) => {
                    this.#processAddress(suggestion.value);
                },
            });

            // // Кастомный обработчик выбора подсказки
            // const container = document.getElementById(containerId);
            // if (!container) return;
            // container.addEventListener('click', (e) => {
            //     let target = e.target;
            //     while (target && target !== document.body) {
            //         if (target.classList && target.classList.contains('suggestions-suggestion')) {
            //             setTimeout(() => {
            //                 this.#processAddress(this.#addressInput.value);
            //                 console.log("Обработчик по подсказке сработал");
            //             }, 100);

            //             break;
            //         }

            //         target = target.parentElement;
            //     }
            // });

            this.#daDataInitialized = true;
        } catch (error) {
            this.#handleError(error);
        }
        // Cтарый код, кастомные подсказки без dadata:

        // const suggestionsContainer = document.createElement('div');
        // suggestionsContainer.className = 'address-suggestions';

        // function showSuggestions(suggestions) {
        //     clearSuggestions();

        //     if (suggestions.length === 0) return;

        //     suggestions.forEach((item, index) => {
        //         const div = document.createElement('div');
        //         div.className = 'suggestion-item';
        //         div.textContent = formatAddress(item.getAddressLine());

        //         div.addEventListener('click', function() {
        //             selectSuggestion(item);
        //         });

        //         suggestionsContainer.appendChild(div);
        //     });

        //     suggestionsContainer.style.display = 'block';
        //     addressInput.classList.add('has-suggestions');
        // }
    }

    // Навешиваем обработчики на кнопки и поля
    #setupEvents() {
        // Обработчик кнопки "Найти"
        if (this.#searchBtn && this.#addressInput) {
            this.#searchBtn.addEventListener("click", () => {
                this.#processAddress(this.#addressInput.value);
            });
        }

        // Обработчик кнопки Enter на input адреса
        if (this.#addressInput) {
            this.#addressInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    this.#processAddress(this.#addressInput.value);
                }
            });
        }
    }
}

// ES6 класс для карты самовывоза, конструктор с параметром (контейнер для карты, поле для опций)
export class PickupMap {
    #map = null; // объект Яндекс.Карты
    #selectedStoreMarker = null; // текущий выбранный маркер магазина
    #isInitialized = false; // флаг создания карты
    #onError; // функция обработки ошибки, передается в конструкторе
    #onPickupStoreSelected; // функция обработки выбора магазина для самовывоза, передается в конструкторе
    #isAddressSelected; // функция проверки сравенения адреса с выбраным

    // Конструктор с параметром, в параметре id контейнера для карты (например 'pickup-map')
    constructor(containerId, options = {}) {
        const { onError, onPickupStoreSelected, isAddressSelected } = options;
        this.#onError = typeof onError === "function" ? onError : null;
        this.#onPickupStoreSelected =
            typeof onPickupStoreSelected === "function"
                ? onPickupStoreSelected
                : null;
        this.#isAddressSelected =
            typeof isAddressSelected === "function" ? isAddressSelected : null;

        try {
            // Проверка API Яндекса
            if (!window.ymaps || typeof ymaps.Map !== "function") {
                throw new Error("YMAPS_API_NOT_LOADED");
            }

            // Принимаем контейнер для карты
            const container = document.getElementById(containerId);
            if (!container) {
                throw new Error("MAP_CONTAINER_NOT_FOUND");
            }

            // Защита от повторного вызова
            if (container.children.length > 0) return;

            this.#map = new ymaps.Map(containerId, {
                center: [55.8, 37.64],
                zoom: 8,
                controls: ["zoomControl"],
            });

            this.#isInitialized = true;
        } catch (error) {
            this.#handleError(error);
            throw error;
        }
    }

    // Метод для рендера магазинов на карте
    renderStores(stores) {
        if (!this.#isInitialized) return;

        stores.forEach((store, index) => {
            // Проверяем валидность координат
            if (
                typeof store.latitude !== "number" ||
                typeof store.longitude !== "number"
            )
                return;

            // Соеденяем широту и долготу в массив кординат
            const coords = [store.latitude, store.longitude];

            // Разбиваем часы работы по переносам строк
            const workHoursHtml = store.workHours
                ? store.workHours
                      .split("\n")
                      .map((line) => line.trim())
                      .filter((line) => line !== "")
                      .map((line) => `<div>${line}</div>`)
                      .join("")
                : "";

            const placemark = new ymaps.Placemark(
                coords,
                {
                    balloonContent: `
                        <div style="
                            min-width: 250px;
                            font-family: 'Jost', Arial;
                            font-size: 16px;
                            color: black;
                        ">
                            <div style="margin-bottom: 15px;">
                                <strong>
                                    ${store.address}
                                </strong>
                                
                                <br>

                                <div style="margin-top: 10px;">
                                    ${workHoursHtml}
                                </div>

                                <a href='tel: ${store.phone}' class="colour_href">
                                    <div style="margin-top: 10px;">
                                        ${store.phone}
                                    </div>
                                </a>
                            </div>

                            <div style="text-align: center;">
                                <button data-pickup-index="${index}" style="
                                    border: 0.15vw solid black;
                                    padding: 3% 5%;
                                    border-radius: 0.5vw;
                                    cursor: pointer;
                                    background: #4B4B4B !important;
                                ">
                                    Заберу отсюда
                                </button>
                            </div>
                        </div>
                    `,
                },
                {
                    iconLayout: "default#image",
                    iconImageHref: "/img/custom-map-pin.png",
                    iconImageSize: [60, 55],
                    iconImageOffset: [-20, -40],

                    balloonCloseButton: true,
                    balloonAutoPan: true,
                },
            );

            // Обработчик открытия балуна
            placemark.events.add("balloonopen", () => {
                // Ждем минимальную задержку чтобы элемент успел отрендериться
                setTimeout(() => {
                    // Находим кнопку выбора адреса из балуна
                    const btn = document.querySelector(
                        `button[data-pickup-index="${index}"]`,
                    );
                    if (!btn || !this.#onPickupStoreSelected) return;

                    // Булева переменная - выбран ли уже этот адрес, в параметре объект с типом и адресом
                    const selectedAlready = this.#isAddressSelected
                        ? this.#isAddressSelected({
                              type: "pickup",
                              storeId: store.id,
                          })
                        : false;

                    // Мгновенно обновляем кнопку если адрес выбран
                    if (selectedAlready) {
                        btn.textContent = "✅ Магазин выбран";
                        btn.style.cursor = "default";
                        btn.style.pointerEvents = "none";
                        btn.disabled = true;

                        placemark.options.set({
                            iconImageHref: "/img/custom-map-pin-chosen.png",
                        });

                        this.#selectedStoreMarker = placemark;
                        return;
                    }

                    // Обычная логика для невыбранного адреса

                    // Если уже есть обработчик - выходим
                    if (btn.hasAttribute("data-listener-added")) return;

                    // Навешиваем обработчик на кнопку выбора адреса
                    btn.setAttribute("data-listener-added", "true");
                    // При клике вызываем внешнею функциею обработки выбора адреса и меняем состояие кнопки
                    btn.addEventListener(
                        "click",
                        () => {
                            this.#onPickupStoreSelected({
                                address: store.address,
                                storeId: store.id,
                            });

                            // Если уже выбран другой магазин - сбрасываем его иконку
                            if (this.#selectedStoreMarker) {
                                this.#selectedStoreMarker.options.set({
                                    iconImageHref: "/img/custom-map-pin.png",
                                });
                            }

                            btn.textContent = "✅ Магазин выбран";
                            btn.style.cursor = "default";
                            btn.style.pointerEvents = "none";
                            btn.disabled = true;

                            placemark.options.set({
                                iconImageHref: "/img/custom-map-pin-chosen.png",
                            });

                            this.#selectedStoreMarker = placemark;
                        },
                        { once: true },
                    );
                }, 0); // Минимальная задержка
            });

            this.#map.geoObjects.add(placemark);
        });

        // Автоподбор области карты по всем меткам
        if (this.#map.geoObjects.getLength() > 0) {
            this.#map.setBounds(this.#map.geoObjects.getBounds(), {
                checkZoomRange: true,
                zoomMargin: 20,
            });
        }
    }

    // Очистка карты
    clear() {
        if (!this.#isInitialized) return;

        // Стандартные балуны
        if (this.#selectedStoreMarker) {
            this.#selectedStoreMarker.options.set({
                iconImageHref: "/img/custom-map-pin.png",
            });
            this.#selectedStoreMarker = null;
        }

        // сбрасываем карту к центру
        if (this.#map) {
            this.#map.setCenter([55.76, 37.64], 8);
        }
    }

    // Удаление карты
    destroy() {
        if (!this.#isInitialized) return;

        if (this.#map) {
            this.#map.destroy(); // метод яндекс карт
            this.#map = null;
        }

        this.#selectedStoreMarker = null;
        this.#isInitialized = false;
    }

    // Приватный метод обработки ошибок
    #handleError(error) {
        console.error("[PickupMap]", error);
        if (this.#onError) this.#onError(error);
    }

    // Геттер для получения состояния карты (инициализирована ли)
    get isInitialized() {
        return this.#isInitialized;
    }
}
