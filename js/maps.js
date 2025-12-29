// Показ ошибки карты (по параметру)
function showMapError(type = "all") {
    const VALID_TYPES = ["stores", "delivery", "pickup", "all"];

    // Валидация
    if (!VALID_TYPES.includes(type)) {
        console.error(
            `[MapError] Неверный тип: "${type}". Допустимо: ${VALID_TYPES.join(", ")}`
        );
        type = "all"; // fallback (резервный вариант, откат)
    }

    console.error("Ошибка Яндекс.Карт:", type);

    if (type === "all") {
        // Показываем все ошибки
        document.querySelectorAll('[class*="error_"]').forEach((block) => {
            block.classList.add("open");
        });
        return;
    }

    const className = `error_${type}_map`;
    const elements = document.querySelectorAll(`.${className}`);

    if (elements.length === 0) {
        console.warn(
            `[MapError] Элемент .${className} не найден, показываю все ошибки`
        );
        showMapError("all"); // рекурсивный вызов
        return;
    }

    elements.forEach((block) => block.classList.add("open"));
}

// Карта магазинов
function initStoresMap() {
    // защита от повторного вызова
    const container = document.getElementById("stores-map");
    if (!container || container.children.length > 0) return;

    const map = new ymaps.Map("stores-map", {
        center: [55.76, 37.64],
        zoom: 10,
    });
    // Загружаем магазины из БД
    fetch("/src/getStores.php")
        .then((response) => response.json())
        .then((stores) => {
            stores.forEach((store) => {
                if (store.coordinates && store.coordinates.length === 2) {
                    const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address.replace(/<br>/g, ", "))}`;
                    map.geoObjects.add(
                        new ymaps.Placemark(
                            store.coordinates,
                            {
                                balloonContent: `
                        <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
                            <div style="margin-bottom: 15px;">
                                <strong>${store.address}</strong><br>
                                <div style="margin-top: 10px;">${store.work_hours}</div>
                                <a href='tel: ${store.phone}' class="colour_href">
                                    <div style="margin-top: 10px;">${store.phone}</div>
                                </a>
                            </div>
                            <div style="text-align: center;">
                                <a href="${yandexMapsUrl}" 
                                target="_blank" 
                                style="display: inline-block; background: #4B4B4B; color: black; border: 0.15vw solid black; padding: 3% 5%; border-radius: 0.5vw;">
                                    Открыть в Картах
                                </a>
                            </div>
                        </div>
                    `,
                            },
                            {
                                iconLayout: "default#image",
                                iconImageHref: "/img/custom_map_pin.png",
                                iconImageSize: [60, 55],
                                iconImageOffset: [-20, -40],
                            }
                        )
                    );
                }
            });
            map.setBounds(map.geoObjects.getBounds());

            // Закрываем балуны при клике на карту
            map.events.add("click", function () {
                map.balloon.close();
            });
        })
        .catch((error) => {
            console.error("Ошибка загрузки магазинов:", error);
        });

    const loader = document.querySelector(".stores_map_loader");
    if (loader) {
        loader.style.opacity = "0";
        loader.style.visibility = "hidden";
        setTimeout(() => {
            loader.style.display = "none";
        }, 200);
    }
}

// Промис загрузки скриптов DaData (типа флага, но асинхронный, его можно ожидать await)
let dadataPromise = null;

// Функция загрузки скрипта DaData
function loadDaDataScript() {
    // Если уже есть обещание то ничего не делаем
    if (dadataPromise) {
        return dadataPromise;
    }

    // Присваиваем промису значение функции
    dadataPromise = new Promise((resolve, reject) => {
        // Создаем тег <script> в памяти (не реально в документе)
        const script = document.createElement("script");

        // URL для скрипта
        const url = `https://cdn.jsdelivr.net/npm/@dadata/suggestions@25.4.1/dist/suggestions.min.js`;

        // Вставляем в скрипт URL
        script.src = url;

        // Обработчик на загрузку скрипта
        script.onload = () => {
            if (!window.Dadata) {
                reject(
                    new Error(
                        "DaData скрипт загрузился, но window.Dadata не найден"
                    )
                );
                return;
            }
            resolve(); // промис выполнен успешно
        };

        // Обработчик на ошибку скрипта
        script.onerror = () => {
            reject(new Error("Ошибка загрузки скрипта DaData"));
        };

        // Вставляем скрипт в DOM
        document.head.appendChild(script);
    });

    return dadataPromise;
}

// Класс через ES6 для карты доставки, конструктор с параметром (контейнер для создания в нем карты)
class DeliveryMap {
    #map = null; // объект Яндекс.Карты
    #marker = null; // текущий маркер адреса
    #addressInput = null; // input ввода адреса
    #searchBtn = null; // кнопка поиска
    #isInitialized = false; // флаг создания карты
    #daDataInitialized = false; // флаг инициализации DaData

    // Санитизация ввода
    #sanitizeAddress(address) {
        return address
            .replace(/[<>"`\\\/]/g, "") // Удаляем самые опасные
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
        const address = geoObject.getAddressLine();
        const postalCode = geoObject.properties.get(
            "metaDataProperty.GeocoderMetaData.Address.postal_code"
        );

        // если уже есть какой-то маркер то убираем его
        if (this.#marker) {
            this.#map.geoObjects.remove(this.#marker);
        }

        this.#marker = new ymaps.Placemark(
            coords,
            {
                balloonContent: `
                <div style="min-width: 250px; font-size: 120% !important; font-family: 'Jost', Arial !important;">
                    <strong>Адрес доставки:</strong><br>
                    ${this.#sanitizeAddress(address.replace(/^Россия,\s*/, ""))}
                    <div style="margin-top: 5%; text-align: center;">
                        <button id="select-delivery-address" 
                                style="border: 0.15vw solid black; padding: 3% 5%; padding-left: 2%; border-radius: 0.5vw; cursor: pointer; 
                                background: #4B4B4B !important;">
                            ✅ Доставить сюда
                        </button>
                    </div>
                </div>
            `,
            },
            {
                preset: "islands#redDotIcon",
                balloonCloseButton: true,
                balloonAutoPan: true,
            }
        );

        this.#map.geoObjects.add(this.#marker);
        this.#map.setCenter(coords, 16);

        // Обработчик события открытия балуна
        this.#marker.events.add("balloonopen", () => {
            const addressEl = document.getElementById(
                "order-right-delivery-address"
            );
            const currentAddress = addressEl ? addressEl.innerText : "";

            const cleanAddress = this.#sanitizeAddress(
                address.replace(/^Россия,\s*/, "")
            );
            // проверка на то что текущий адресс и выбранный адресс одинаковы
            const isSelected = currentAddress === cleanAddress;

            // Мгновенно обновляем кнопку если адрес выбран
            if (isSelected) {
                setTimeout(() => {
                    const selectBtn = document.getElementById(
                        "select-delivery-address"
                    );
                    if (selectBtn) {
                        selectBtn.textContent = "✅ Адрес выбран";
                        selectBtn.style.cursor = "default";
                        selectBtn.style.pointerEvents = "none";
                        selectBtn.disabled = true;
                    }
                }, 0); // Минимальная задержка
            } else {
                // Обычная логика для невыбранного адреса
                setTimeout(() => {
                    const selectBtn = document.getElementById(
                        "select-delivery-address"
                    );
                    if (
                        selectBtn &&
                        !selectBtn.hasAttribute("data-listener-added")
                    ) {
                        selectBtn.setAttribute("data-listener-added", "true");
                        selectBtn.addEventListener("click", async () => {
                            const cleanAddress = this.#sanitizeAddress(
                                address.replace(/^Россия,\s*/, "")
                            );

                            // проверка на наличие fetchWithRetry
                            if (typeof fetchWithRetry !== "function") {
                                console.error(
                                    "[DeliveryMap] fetchWithRetry не определена"
                                );
                                return;
                            }

                            const data = await fetchWithRetry(
                                "/src/saveDeliveryAddress.php",
                                {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                    },
                                    body: JSON.stringify({
                                        delivery_type: "delivery",
                                        address: cleanAddress,
                                        postalCode: postalCode,
                                    }),
                                }
                            );

                            if (data.success) {
                                selectBtn.textContent = "✅ Адрес выбран";
                                selectBtn.style.cursor = "default";
                                selectBtn.style.pointerEvents = "none";
                                selectBtn.disabled = true;
                                document.getElementById(
                                    "order-right-delivery-address"
                                ).innerText = cleanAddress;
                            }
                        });
                    }
                }, 100);
            }
        });

        this.#marker.balloon.open();
    }

    // Обработка адреса
    #processAddress(address) {
        // Скрываем все ошибки адреса
        document
            .getElementById("modal-error-address-empty")
            ?.classList.remove("open");
        document
            .getElementById("modal-error-address-not-found")
            ?.classList.remove("open");
        document
            .getElementById("modal-error-address-timeout")
            ?.classList.remove("open");

        // показываем ошибку если input адреса пустой
        if (!address.trim()) {
            setTimeout(() => {
                document
                    .getElementById("modal-error-address-empty")
                    ?.classList.add("open");
            }, 10);
            return;
        }

        if (this.#searchBtn) {
            this.#searchBtn.disabled = true;
            this.#searchBtn.textContent = "Поиск...";
        }

        // добавляем таймаут на поиск адреса
        let isGeocodeTimedOut = false;
        const geocodeTimeout = setTimeout(() => {
            console.error("[DeliveryMap] Таймаут геокодирования");
            isGeocodeTimedOut = true; // устанавливаем флаг таймаута
            if (this.#searchBtn) {
                this.#searchBtn.disabled = false;
                this.#searchBtn.textContent = "Найти";
            }
            // Показываем ошибку таймаута
            document
                .getElementById("modal-error-address-timeout")
                ?.classList.add("open");
        }, 10000); // время таймаута - 10 сек

        const normalizedAddress = address
            .replace(/[.,]/g, "")
            .replace(/\s+/g, " ")
            .trim();
        ymaps
            .geocode(normalizedAddress)
            .then((res) => {
                // если таймаут сработал то выходим
                if (isGeocodeTimedOut) {
                    return;
                }

                clearTimeout(geocodeTimeout); // очищаем таймаут при успехе

                // если нет результата то ошибку
                const firstResult = res.geoObjects.get(0);
                if (!firstResult) {
                    setTimeout(() => {
                        document
                            .getElementById("modal-error-address-not-found")
                            ?.classList.add("open");
                    }, 10);
                    if (this.#searchBtn) {
                        this.#searchBtn.disabled = false;
                        this.#searchBtn.textContent = "Найти";
                    }
                    return;
                }

                // показываем на карте
                this.#showAddressOnMap(firstResult);

                if (this.#searchBtn) {
                    this.#searchBtn.disabled = false;
                    this.#searchBtn.textContent = "Найти";
                }
            })
            .catch((error) => {
                // если таймаут сработал то выходим
                if (isGeocodeTimedOut) {
                    return;
                }

                clearTimeout(geocodeTimeout); // очищаем таймаут при ошибки
                console.error("Ошибка геокодирования:", error);
                if (this.#searchBtn) {
                    this.#searchBtn.disabled = false;
                    this.#searchBtn.textContent = "Найти";
                }
                // При других ошибках тоже показываем таймаут
                document
                    .getElementById("modal-error-address-timeout")
                    ?.classList.add("open");
            });
    }

    // Навешиваем обработчики на кнопки и поля
    #setupEvents() {
        // Обработчик кнопки "Найти"
        if (this.#searchBtn) {
            this.#searchBtn.addEventListener("click", () => {
                this.#processAddress(
                    this.#sanitizeAddress(this.#addressInput.value)
                );
            });
        }

        // Обработчик кнопки Enter на input адреса
        if (this.#addressInput) {
            this.#addressInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    this.#processAddress(
                        this.#sanitizeAddress(this.#addressInput.value)
                    );
                }
            });
        }
    }

    // инициализация DaData
    async #initDaData() {
        try {
            if (this.#daDataInitialized) return;

            // Ждем загрузки скриптов DaData
            await loadDaDataScript();

            if (this.#addressInput) {
                const response = await fetch("/src/serviceProxy.php");
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.key && window.Dadata) {
                    // Используем стандартный виджет DaData
                    window.Dadata.createSuggestions(this.#addressInput, {
                        token: data.key,
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
                }
            }
        } catch (error) {
            console.error("Ошибка загрузки ключа DaData:", error);
        }
        // старый код, кастомные подсказки без dadata

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

    // Метод убирания лоадера
    #hideLoader() {
        // Плавно убираем лоадер
        const loader = document.getElementById("delivery-map-loader");
        if (loader) {
            loader.style.opacity = "0";
            loader.style.visibility = "hidden";
            // Через время завершения анимации - полностью убираем
            setTimeout(() => {
                loader.style.display = "none";
            }, 200); // Время должно совпадать с transition (0.4s)
        }
    }

    // инициализация (настройка объекта)
    async #initialize() {
        try {
            // Приватный метод инициализации
            this.#setupEvents();
            await this.#initDaData();
            this.#hideLoader();

            this.#isInitialized = true;
        } catch (error) {
            console.error("[DeliveryMap] Ошибка инициализации:", error);
            showMapError("delivery");
            this.#isInitialized = false;

            // Удаляем карту при ошибке
            if (this.#map) {
                this.#map.destroy();
            }
        }
    }

    // конструктор с параметром, в параметре id контейнера для карты (например 'delivery-map')
    constructor(containerId) {
        try {
            // Проверка API Яндекса
            if (!window.ymaps) {
                throw new Error("Yandex Maps API не загружен");
            }
            if (!ymaps.Map || typeof ymaps.Map !== "function") {
                throw new Error("Yandex Maps API: Map не доступен");
            }

            // защита от повторного вызова
            const container = document.getElementById(containerId);
            if (!container || container.children.length > 0) return;

            this.#addressInput = document.getElementById("delivery-address");
            this.#searchBtn = document.getElementById("delivery-search-btn");
            if (!this.#addressInput || !this.#searchBtn) {
                return;
            }

            this.#map = new ymaps.Map(containerId, {
                center: [55.76, 37.64],
                zoom: 10,
                controls: ["zoomControl"],
            });

            // инициализируем
            this.#initialize().catch(() => {
                // Пустой обработчик - ошибки уже обработаны внутри
            });
        } catch (error) {
            console.error("[DeliveryMap] Ошибка инициализации:", error);
            showMapError("delivery");
            this.#isInitialized = false;
        }
    }

    // очистка карты
    clearDeliveryMap() {
        if (!this.#isInitialized) {
            // тут номрмальное логирование потом
            console.error("[DeliveryMap] Карта не инициализирована");
            return;
        }

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
        if (!this.#isInitialized) {
            // тут номрмальное логирование потом
            console.error("[DeliveryMap] Карта не инициализирована");
            return;
        }

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
}

// объявляем переменную для карты (т.к. она одна на страницу)
let deliveryMap = null;

// Класс через ES6 для карты самовывоза, конструктор с параметром (контейнер для создания в нем карты)
class PickupMap {
    #map = null; // объект Яндекс.Карты
    #selectedStoreMarker = null; // текущий выбранный маркер магазина
    #isInitialized = false; // флаг создания карты

    // Метод убирания лоадера
    #hideLoader() {
        // Плавно убираем лоадер
        const loader = document.getElementById("pickup-map-loader");
        if (loader) {
            loader.style.opacity = "0";
            loader.style.visibility = "hidden";
            // Через время завершения анимации - полностью убираем
            setTimeout(() => {
                loader.style.display = "none";
            }, 200); // Время должно совпадать с transition (0.4s)
        }
    }

    // Метода выбора магазина
    async #selectPickupStore(storeId, address, marker, index) {
        try {
            // проверка на наличие fetchWithRetry
            if (typeof fetchWithRetry !== "function") {
                console.error("[PickupMap] fetchWithRetry не определена");
                return;
            }

            const data = await fetchWithRetry("/src/saveDeliveryAddress.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    delivery_type: "pickup",
                    store_id: storeId,
                }),
            });

            if (data.success) {
                // Сбрасываем предыдущий выбранный маркер
                if (this.#selectedStoreMarker) {
                    this.#selectedStoreMarker.options.set({
                        iconImageHref: "/img/custom_map_pin.png",
                    });
                }

                // Устанавливаем новый выбранный маркер
                marker.options.set({
                    iconImageHref: "/img/custom_map_pin_chosen.png",
                });
                this.#selectedStoreMarker = marker;

                // Обновляем только выбранную кнопку
                const selectBtn = document.getElementById(
                    `select-pickup-store-${index}`
                );
                if (selectBtn) {
                    selectBtn.textContent = "✅ Магазин выбран";
                    selectBtn.style.cursor = "default";
                    selectBtn.style.pointerEvents = "none";
                    selectBtn.disabled = true;
                }

                // Обновляем адрес в правой части
                document.getElementById(
                    "order-right-pickup-address"
                ).innerText = address;

                // Закрываем балун
                marker.balloon.close();
            } else {
                // Устанавливаем дефолтный маркер
                marker.options.set({
                    iconImageHref: "/img/custom_map_pin.png",
                });

                // Обновляем кнопку на дефолтную
                const selectBtn = document.getElementById(
                    `select-pickup-store-${index}`
                );
                if (selectBtn) {
                    selectBtn.textContent = "Заберу отсюда";
                    selectBtn.style.cursor = "pointer";
                    selectBtn.style.pointerEvents = "auto";
                    selectBtn.disabled = false;
                }

                throw new Error(data.message || "Неизвестная ошибка сервера");
            }
        } catch (error) {
            console.error("[PickupMap] Ошибка выбора магазина:", error);
            headerModal.open("Ошибка выбора магазина, попробуйте еще раз");
        }
    }

    // Инициализация
    async #initialize() {
        try {
            // Загружаем магазины из БД
            const response = await fetch("/src/getStores.php");
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const stores = await response.json();

            stores.forEach((store, index) => {
                if (store.coordinates && store.coordinates.length === 2) {
                    const placemark = new ymaps.Placemark(
                        store.coordinates,
                        {
                            balloonContent: `
                            <div style="min-width: 250px; font-family: 'Jost', Arial; font-size: 16px; color: black;">
                                <div style="margin-bottom: 15px;">
                                    <strong>${store.address}</strong><br>
                                    <div style="margin-top: 10px;">${store.work_hours}</div>
                                    <a href='tel: ${store.phone}' class="colour_href">
                                        <div style="margin-top: 10px;">${store.phone}</div>
                                    </a>
                                </div>
                                <div style="text-align: center;">
                                    <button id="select-pickup-store-${index}" 
                                            style="border: 0.15vw solid black; padding: 3% 5%; border-radius: 0.5vw; cursor: pointer; 
                                            background: #4B4B4B !important;">
                                        Заберу отсюда
                                    </button>
                                </div>
                            </div>
                        `,
                        },
                        {
                            iconLayout: "default#image",
                            iconImageHref: "/img/custom_map_pin.png",
                            iconImageSize: [60, 55],
                            iconImageOffset: [-20, -40],
                        }
                    );

                    this.#map.geoObjects.add(placemark);

                    // Обработчик открытия балуна
                    placemark.events.add("balloonopen", () => {
                        const currentAddress = document.getElementById(
                            "order-right-pickup-address"
                        ).innerText;
                        const isSelected = currentAddress === store.address;

                        if (isSelected) {
                            setTimeout(() => {
                                const selectBtn = document.getElementById(
                                    `select-pickup-store-${index}`
                                );
                                if (selectBtn) {
                                    selectBtn.textContent = "✅ Магазин выбран";
                                    selectBtn.style.cursor = "default";
                                    selectBtn.style.pointerEvents = "none";
                                    selectBtn.disabled = true;
                                }
                            }, 0);
                        } else {
                            setTimeout(() => {
                                const selectBtn = document.getElementById(
                                    `select-pickup-store-${index}`
                                );
                                if (
                                    selectBtn &&
                                    !selectBtn.hasAttribute(
                                        "data-listener-added"
                                    )
                                ) {
                                    selectBtn.setAttribute(
                                        "data-listener-added",
                                        "true"
                                    );
                                    selectBtn.addEventListener("click", () => {
                                        this.#selectPickupStore(
                                            store.id,
                                            store.address,
                                            placemark,
                                            index
                                        );
                                    });
                                }
                            }, 100);
                        }
                    });
                }
            });

            // Настройка объекта
            this.#hideLoader();
            this.#isInitialized = true;
        } catch (error) {
            console.error("[PickupMap] Ошибка инициализации:", error);
            showMapError("pickup");
            this.#isInitialized = false;

            // Удаляем карту при ошибке
            if (this.#map) {
                this.#map.destroy();
            }
        }
    }

    // Конструктор с параметром, в параметре id контейнера для карты (например 'delivery-map')
    constructor(containerId) {
        try {
            // Проверка API Яндекса
            if (!window.ymaps) {
                throw new Error("Yandex Maps API не загружен");
            }
            if (!ymaps.Map || typeof ymaps.Map !== "function") {
                throw new Error("Yandex Maps API: Map не доступен");
            }

            // Защита от повторного вызова
            const container = document.getElementById(containerId);
            if (!container || container.children.length > 0) return;

            this.#map = new ymaps.Map(containerId, {
                center: [55.8, 37.64],
                zoom: 8,
                controls: ["zoomControl"],
            });

            // Инициализируем
            this.#initialize().catch(() => {
                // Пустой обработчик - ошибки уже обработаны внутри
            });
        } catch (error) {
            console.error("[PickupMap] Ошибка инициализации:", error);
            showMapError("pickup");
            this.#isInitialized = false;
        }
    }

    // Очистка карты
    clearPickupMap() {
        // Стандартные балуны
        if (this.#selectedStoreMarker) {
            this.#selectedStoreMarker.options.set({
                iconImageHref: "/img/custom_map_pin.png",
            });
            this.#selectedStoreMarker = null;
        }

        // сбрасываем карту к центру
        if (this.#map) {
            this.#map.setCenter([55.76, 37.64], 8);
        }

        // Сбрасываем все кнопки выбора магазина
        document
            .querySelectorAll('[id^="select-pickup-store-"]')
            .forEach((btn) => {
                btn.textContent = "Заберу отсюда";
                btn.style.cursor = "pointer";
                btn.style.pointerEvents = "auto";
                btn.disabled = false;
                btn.replaceWith(btn.cloneNode(true));
            });
    }

    // Удаление карты
    destroy() {
        if (!this.#isInitialized) {
            // тут номрмальное логирование потом
            console.error("[PickupMap] Карта не инициализирована");
            return;
        }

        if (this.#map) {
            this.#map.destroy(); // метод яндекс карт
            this.#map = null;
        }

        this.#isInitialized = false;
    }

    // Геттер для получения состояния карты (инициализирована ли)
    get isInitialized() {
        return this.#isInitialized;
    }
}

// объявляем переменную для карты (т.к. она одна на страницу)
let pickupMap = null;

// Промис загрузки скриптов для карт (типа флага, но асинхронный, его можно ожидать await)
let yandexMapsPromise = null;

function loadYandexMapsScripts() {
    // Если уже есть обещание то ничего не делаем
    if (yandexMapsPromise) {
        return yandexMapsPromise;
    }

    // Присваиваем промису значение функции
    yandexMapsPromise = new Promise((resolve, reject) => {
        // Создаем тег <script> в памяти (не реально в документе)
        const script = document.createElement("script");

        // Получаем ip ключ для карт
        const key = document.body?.dataset?.yandexMapsKey;
        if (!key) {
            reject(new Error("YANDEX_MAPS_KEY не найден в data-атрибуте body"));
            return;
        }

        // Собираем URL для скрипта
        const url = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(key)}&lang=ru_RU&load=package.full`;

        // Вставляем в скрипт URL
        script.src = url;

        // Обработчик на загрузку скрипта
        script.onload = () => {
            if (!window.ymaps) {
                reject(
                    new Error(
                        "Yandex Maps скрипт загрузился, но ymaps не найден"
                    )
                );
                return;
            }
            resolve(); // промис выполнен успешно
        };

        // Обработчик на ошибку скрипта
        script.onerror = () => {
            reject(new Error("Ошибка загрузки скрипта Yandex Maps"));
        };

        // Вставляем скрипт в DOM
        document.head.appendChild(script);
    });

    return yandexMapsPromise;
}

// Функция для инициализации карт
function initAllMaps() {
    if (typeof ymaps === "undefined") {
        showMapError();
    } else {
        ymaps.ready(() => {
            // Карта магазинов (если она видна на странице)
            if (document.getElementById("stores-map")) {
                try {
                    initStoresMap();
                } catch (error) {
                    console.error("Ошибка карты магазинов:", error);
                    showMapError("stores");
                }
            }

            // Карты оформления заказа (проверяем API ключ)
            // Карта доставки (если есть и видна)
            const deliveryModal = document.getElementById(
                "modal-order-type-delivery"
            );
            if (
                document.getElementById("delivery-map") &&
                deliveryModal &&
                !deliveryModal.classList.contains("hidden")
            ) {
                ymaps
                    .geocode("Москва", { results: 1 })
                    .then(() => {
                        // создаем карту если ее нет
                        if (!deliveryMap) {
                            deliveryMap = new DeliveryMap("delivery-map");
                        }
                    })
                    .catch((error) => {
                        // API не работает
                        console.error("Неверный API ключ Яндекс.Карт:", error);
                        showMapError("delivery");
                    });
            }

            // Карта самовывоза будет инициализироваться в обработчике переключения типа
        });
    }
}

// Запускаем асинхронную загрузку скрипта Яндекс.Карт
// Функция loadYandexMapsScripts сразу возвращает Promise (объект ожидания результата)
loadYandexMapsScripts()
    // Эта функция НЕ вызывается сразу, она будет вызвана когда Promise перейдёт в состояние "успех" (resolve)
    .then(() => {
        initAllMaps();
    })
    // Эта функция НЕ вызывается сразу, она будет вызвана если Promise завершится с ошибкой (reject)
    .catch((error) => {
        console.error("[Maps] Не удалось загрузить скрипты карт:", error);
        showMapError();
    });
