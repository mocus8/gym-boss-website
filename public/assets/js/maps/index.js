import { setButtonLoading, debounce, escapeHtml } from "../utils.js";
import { suggestAddress } from "./dadata.api.js";
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

            // Валидируем поля перед выводом
            const workHours = escapeHtml(store.work_hours);
            const address = escapeHtml(store.address);
            const phone = escapeHtml(store.phone);

            // Разбиваем часы работы по переносам строк
            const workHoursHtml = workHours
                ? workHours
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
                        <div class="map__inner-baloon">
                            <p>${address}</p>
                        
                            <p>${workHoursHtml}</p>
                        
                            <a href='tel:${phone}'>${phone}</a>

                            <a
                                id="start-order-btn"
                                class="link-shell map__inner-baloon-btn-shell"
                                href="${yandexMapsUrl}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span class="btn map__inner-baloon-btn">
                                    Открыть в Картах
                                </span>
                            </a>
                        </div>
                    `,
                },
                {
                    iconLayout: "default#image",
                    iconImageHref: "/assets/images/ui/custom-map-pin.png",
                    iconImageSize: [50, 50],
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
    #suggestionsContainer = null; // контейнер подсказок
    #onOutsideMouseDown = null; // функция клика вне подсказок и input-а, для закрытия подсказок
    #suggestionsAbortController = null; // AbortController для прерывания запросов получения подсказок
    #geocodeRequestId = 0; // счетчик и id запросов геокодинга
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
            suggestionsContainerId,
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
            this.#suggestionsContainer = suggestionsContainerId
                ? document.getElementById(suggestionsContainerId)
                : null;
            this.#searchBtn = searchButtonId
                ? document.getElementById(searchButtonId)
                : null;

            // Если элементы не найдены - ошибку
            if (
                !this.#addressInput ||
                !this.#suggestionsContainer ||
                !this.#searchBtn
            ) {
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
            this.#initDaData();
            this.#isInitialized = true;
        } catch (error) {
            this.#handleError(error);
            throw error;
        }
    }

    // Функция для отключения подсказок
    disableSuggestions() {
        this.suggestionsAbortController?.abort();
        this.suggestionsAbortController = null;

        this.#hideSuggestions();
        if (this.#suggestionsContainer)
            this.#suggestionsContainer.innerHTML = "";

        if (this.#addressInput) this.#addressInput.disabled = true;
        if (this.#searchBtn) this.#searchBtn.disabled = true;
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

        // Удаляем обработчик с контйнера подсказок
        if (this.#suggestionsContainer) {
            this.#suggestionsContainer.replaceWith(
                this.#suggestionsContainer.cloneNode(true),
            );
        }

        // Удаляем обработчик клика по документу (закрытие подсказок)
        if (this.#onOutsideMouseDown) {
            document.removeEventListener("click", this.#onOutsideMouseDown);
            this.#onOutsideMouseDown = null;
        }

        // Отменем запрос на получение подсказок если можем и обнулем контроллер отмены запросов
        this.suggestionsAbortController?.abort();
        this.suggestionsAbortController = null;

        this.#marker = null;
        this.#addressInput = null;
        this.#suggestionsContainer = null;
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
                    <div class="map__inner-baloon">
                        <p>Адрес доставки:</p>

                        <p>${address}</p>
                    
                        <button 
                            id="select-courier-address"
                            class="btn-reset map__inner-baloon-btn-shell btn-shell"
                            type="button"
                        >
                            <span class="btn map__inner-baloon-btn">
                                Доставить сюда
                            </span>
                        </button>
                    </div>
                `,
            },
            {
                iconLayout: "default#image",
                iconImageHref: "/assets/images/ui/custom-map-pin.png",
                iconImageSize: [50, 50],
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
                    btn.textContent = "✓ Адрес выбран";
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

                        btn.textContent = "✓ Адрес выбран";
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
                setButtonLoading(this.#searchBtn, true);
            }

            // Прибавляем номер запроса
            const requestId = ++this.#geocodeRequestId;

            const geocodeTimeout = setTimeout(() => {
                // Если запрос уже не актуальный - ничего не делаем
                if (requestId !== this.#geocodeRequestId) return;

                this.#geocodeRequestId++; // протухаем текущий запрос
                this.#handleError(new Error("GEOCODE_TIMEOUT"));
            }, 10000);

            const res = await ymaps.geocode(normalizedAddressQuery);

            // таймер лучше очистить сразу после await
            clearTimeout(geocodeTimeout);

            // если пока ждали ответ, стартовал новый поиск - игнорируем этот
            if (requestId !== this.#geocodeRequestId) return;

            // Получаем первый результат, если его нет - ошибку
            const firstResult = res.geoObjects.get(0);
            if (!firstResult) {
                throw new Error("ADDRESS_NOT_FOUND");
            }

            // Достаем данные найденного адреса
            const meta = firstResult.properties.get(
                "metaDataProperty.GeocoderMetaData",
            );
            const precision = meta?.precision;

            // Вытаскиваем регион
            const region =
                meta?.AddressDetails?.Country?.AdministrativeArea
                    ?.AdministrativeAreaName;
            if (!region) throw new Error("ADDRESS_REGION_NOT_DETECTED");

            // Если регион не Москва или область - ошибку
            const isValidRegion =
                region === "Москва" || region === "Московская область";
            if (!isValidRegion) throw new Error("INVALID_ADDRESS_REGION");

            // Если нет дома/номера - ошибку
            if (precision !== "exact" && precision !== "number") {
                throw new Error("ADDRESS_TOO_IMPRECISE");
            }

            // Показываем на карте
            this.#showAddressOnMap(firstResult);
        } catch (error) {
            this.#handleError(error);
        } finally {
            if (this.#searchBtn) {
                setButtonLoading(this.#searchBtn, false);
            }
        }
    }

    // Функция для показа подсказок
    #showSuggestions() {
        if (!this.#suggestionsContainer) return;
        this.#suggestionsContainer.hidden = false;
    }

    // Функция для скрытия подсказок
    #hideSuggestions() {
        if (!this.#suggestionsContainer) return;
        this.#suggestionsContainer.hidden = true;
        this.#suggestionsContainer.innerHTML = "";
    }

    // Функция для создания элемента подсказки
    #createSuggestionElement(suggestion) {
        const suggestionValue = String(suggestion.value);

        const suggestionItem = document.createElement("li");
        suggestionItem.classList.add("checkout__address-search-suggestion");

        const suggestionBtn = document.createElement("button");
        suggestionBtn.classList.add("btn-reset");
        suggestionBtn.dataset.suggestionValue = suggestionValue;
        suggestionBtn.type = "button";
        suggestionBtn.textContent = suggestionValue;
        suggestionItem.appendChild(suggestionBtn);

        return suggestionItem;
    }

    // Функция для рендера и показа подсказок
    #renderSuggestions(suggestions) {
        if (!this.#suggestionsContainer) return;

        if (!suggestions?.length) {
            this.#hideSuggestions();
            return;
        }

        // Очищаем содержимое контейнера
        this.#suggestionsContainer.innerHTML = "";

        // Заполняем контейнер
        suggestions.forEach((suggestion) => {
            // Рендерим через функцию блок товара
            const suggestionEl = this.#createSuggestionElement(suggestion);

            // Добавляем подсказку в контейнер
            this.#suggestionsContainer.appendChild(suggestionEl);
        });

        // Показываем контейнер
        this.#showSuggestions();
    }

    // Инициализация подсказок DaData по адресу
    #initDaData() {
        if (
            this.#daDataInitialized ||
            !this.#addressInput ||
            !this.#suggestionsContainer
        )
            return;

        // Сохраняем в переменную функцию для получения подсказок
        const suggestionsOnInput = async () => {
            const q = this.#addressInput.value.trim();
            if (q.length < 3) {
                this.#hideSuggestions();

                this.#suggestionsAbortController?.abort(); // если можем - прерываем запрос
                this.#suggestionsAbortController = null; // обнуляем контроллер отмены

                return;
            }

            this.#suggestionsAbortController?.abort(); // если можем - прерываем запрос
            this.#suggestionsAbortController = new AbortController(); // создаем новый контроллер отмены

            try {
                // Получаем массив подсказок через запрос
                const res = await suggestAddress(q, 5, {
                    signal: this.#suggestionsAbortController.signal,
                });
                const suggestions = res?.suggestions ?? [];

                this.#renderSuggestions(suggestions);
            } catch (error) {
                if (error?.name === "AbortError") return; // если прервано из-за контроллера - тихо выходим
                this.#hideSuggestions();
                this.#handleError(error);
            }
        };

        // Оборачиваем функцию suggestionsOnInput в debounce-обертку
        const debouncedSuggestionsOnInput = debounce(suggestionsOnInput, 300);

        // Функция для обработки клика по подсказкам (делегирование внутренних подсказок по дата-атрибутам)
        const suggestionOnClick = async (e) => {
            const suggestion = e.target.closest("[data-suggestion-value]");
            if (!suggestion) return;

            const value = suggestion.dataset.suggestionValue;
            if (!value) return;

            // Отменяем отложенный вызов если он есть
            debouncedSuggestionsOnInput.cancel(); // через дебаунс-функцию
            this.suggestionsAbortController?.abort(); // и через контроллер отмены запроса
            this.suggestionsAbortController = null;

            this.#hideSuggestions();
            this.#addressInput.value = value;

            try {
                await this.#processAddress(value);
            } catch (error) {
                this.#hideSuggestions();
                this.#handleError(error);
            }
        };

        // Навешиваем на ввод адреса debounced-обертку функции suggestionsOnInput
        this.#addressInput.addEventListener(
            "input",
            debouncedSuggestionsOnInput,
        );

        // Навешиваем обработчик на контейнер подсказок
        this.#suggestionsContainer.addEventListener("click", suggestionOnClick);

        // Закрытие подсказок при клике вне контейнера/input-а
        this.#onOutsideMouseDown = (e) => {
            const target = e.target;
            if (this.#addressInput.contains(target)) return;
            if (this.#suggestionsContainer.contains(target)) return;
            this.#hideSuggestions();
        };
        // Навешиваем обработчик клика на функцию
        document.addEventListener("click", this.#onOutsideMouseDown);

        // Ставим флаг инициализации DaData
        this.#daDataInitialized = true;

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
            this.#addressInput.addEventListener("keydown", (e) => {
                if (e.key !== "Enter") return;

                e.preventDefault(); // отменить submit формы по Enter
                this.#processAddress(this.#addressInput.value);
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

            // Валидируем поля перед выводом
            const workHours = escapeHtml(store.work_hours);
            const address = escapeHtml(store.address);
            const phone = escapeHtml(store.phone);

            // Разбиваем часы работы по переносам строк
            const workHoursHtml = workHours
                ? workHours
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
                        <div class="map__inner-baloon">
                            <p>${address}</p>
                        
                            <p>${workHoursHtml}</p>
                        
                            <a href='tel:${phone}'>${phone}</a>
                        
                            <button 
                                data-pickup-index="${index}"
                                class="btn-reset map__inner-baloon-btn-shell btn-shell"
                                type="button"
                            >
                                <span class="btn map__inner-baloon-btn">
                                    Заберу отсюда
                                </span>
                            </button>
                        </div>
                    `,
                },
                {
                    iconLayout: "default#image",
                    iconImageHref: "/assets/images/ui/custom-map-pin.png",
                    iconImageSize: [50, 50],
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
                        btn.textContent = "✓ Магазин выбран";
                        btn.style.cursor = "default";
                        btn.style.pointerEvents = "none";
                        btn.disabled = true;

                        placemark.options.set({
                            iconImageHref:
                                "/assets/images/ui/custom-map-pin-chosen.png",
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
                                    iconImageHref:
                                        "/assets/images/ui/custom-map-pin.png",
                                });
                            }

                            btn.textContent = "✓ Магазин выбран";
                            btn.style.cursor = "default";
                            btn.style.pointerEvents = "none";
                            btn.disabled = true;

                            placemark.options.set({
                                iconImageHref:
                                    "/assets/images/ui/custom-map-pin-chosen.png",
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
                iconImageHref: "/assets/images/ui/custom-map-pin.png",
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
