// Функция запроса к API: добавляет baseUrl, парсит JSON, проверяет HTTP статус и флаг success, кидает осмысленную ошибку
// Options - по умолчанию пустой объект {}, можеть быть таким: { method: 'POST', body: '...' }
export async function requestApi(baseUrl, path, options = {}) {
    const response = await fetch(baseUrl + path, {
        credentials: "same-origin", // настройка для fetch, которая говорит: "отправлять данные только если запрос на тот же домен"
        ...options, // ...options раскрывает содержимое объекта и вставляет его (убирает {} вокруг содержимого)
        // Всегда по умолчанию JSON.
        headers: {
            "Content-Type": "application/json",
            ...(options.headers || {}),
        },
    });

    // Пытаемся распарсить response как json
    let data = null;
    try {
        data = await response.json();
    } catch {
        data = null;
    }

    // Если HTTP статус не 2xx или в data API вернул success: false то считаем это ошибкой.
    if (!response.ok || (data && data.success === false)) {
        const apiError = data && data.error;
        const message =
            apiError && apiError.message
                ? apiError.message
                : `API error (${response.status})`;

        const error = new Error(message); // понятное сообщение
        error.status = response.status; // HTTP-код
        error.code = apiError && apiError.code; // бизнес-код с бэка
        error.payload = data; // весь ответ
        throw error;
    }

    // Если есть поле data то возвращаем его
    if (data && Object.prototype.hasOwnProperty.call(data, "data")) {
        return data.data;
    }

    // Если поле data нет, то возвращаем объект целиком
    return data;
}

// Debounce функция: делает задержку перед вызовом функции, и отменяет предыдущие вызовы при появлении новых
// Через замыкание функция получает и выполняет переданную функцию с актуальными аргументами
// Сначала будет вызываться главная debounce с аргументами в виде функции и времени, а потом уже обертку debounced
export function debounce(fn, wait = 300) {
    if (typeof fn != "function") {
        throw new TypeError("[debounce] fn должна быть функцией");
    }

    let timerId = null; // таймер
    let lastArgs = null; // актуальный (последний) аргумент
    let lastThis = null; // актуальный (последний) контекст вызова функции (this)

    // Внутренняя функция-обертка, вызывается каждый раз при действии, требующей debounce-а
    // В нее передаются актуальные аргументы, и уже с ними при достижении времени вызывается исходная функция, переданная в debounce
    function debounced(...args) {
        // Переопределяем аргументы и контекст в актуальные
        lastArgs = args;
        lastThis = this;

        // Если таймер debounce-а уже запущен - обнуляем
        if (timerId !== null) clearTimeout(timerId);

        // Ставим задержку по нужному времени на выполние функции с актуальными аргументами
        // Также обнуляем таймер, аргументы и контекст
        timerId = setTimeout(() => {
            timerId = null;
            fn.apply(lastThis, lastArgs);
            lastArgs = null;
            lastThis = null;
        }, wait);
    }

    // Метод функции для отмены последнего вызова через debounced, если он ещё не успел выполниться.
    debounced.cancel = () => {
        // Если таймер debounce-а уже запущен - обнуляем
        if (timerId !== null) clearTimeout(timerId);

        // Обнуляем таймер, аргументы и контекст
        timerId = null;
        lastArgs = null;
        lastThis = null;
    };

    // Функция для немедленного выполнения последнего вызова через debounced, даже если он ещё не успел выполниться.
    debounced.flush = () => {
        // Если иаймера нет - выходим
        if (timerId === null) return;
        // Очищаем таймер
        clearTimeout(timerId);

        // Сразу выполняем функцию с актуальными аргументами и обнуляем таймер, аргументы и контекст
        timerId = null;
        fn.apply(lastThis, lastArgs);
        lastArgs = null;
        lastThis = null;
    };

    // Возвращаем вложеную функцию (сохраняется таймер, аргументы и контекст вызова через замыкание)
    // Главная функция debounce - фабрика функций, т.к. она возвращяет функции
    // Также она принимает настройки (fn, wait) и возвращает новую функцию (debounced), “собранную” под эти настройки.
    return debounced;
}

// Константа со всеми кодами ошибок
const ERROR_CODE_MESSAGES = {
    // Маппинг кодов
    INTERNAL_SERVER_ERROR: "Внутренняя ошибка сервера. Попробуйте позже",
    INVALID_REQUEST: "Ошибка в запросе. Обновите страницу и попробуйте снова",
    VALIDATION_ERROR: "Ошибка в данных. Проверьте введённую информацию",
    UNAUTHENTICATED:
        "Войдите в аккаунт и повторите попытку либо свяжитесь с поддержкой",
    ALREADY_AUTHENTICATED: "Вход в аккаунт уже выполнен, обновите страницу",
    EMAIL_REQUIRED: "Требуется электронная почта",
    EMAIL_INVALID: "Проверьте формат введеной почты",
    EMAIL_TOO_LONG: "Слишком длинный адрес электронной почты",
    EMAIL_TAKEN:
        "Аккаунт с этим email уже зарегестрирован, войдите или восстановите пароль",
    EMAIL_SEND_FAILURE:
        "Не удалось отправить email, запросите повторную отправку письма или обратитесь в поддержку",
    EMAIL_UNVERIFIED:
        "Для этого действия необходимо подтвердить почту через личный кабинет",
    EMAIL_ALREADY_VERIFIED:
        "Почта уже подтверждена, обновите страницу или попробуйте позже",
    RESEND_TOO_SOON: "Повторная отпрака письма будет доступна через ",
    PASSWORD_REQUIRED: "Требуется пароль",
    PASSWORD_TOO_SHORT: "Слишком короткий пароль",
    PASSWORD_TOO_LONG: "Слишком длинный пароль",
    PASSWORD_INVALID_CHARS: "Пароль содержит недопустимые символы",
    NAME_REQUIRED: "Требуется имя",
    NAME_TOO_LONG: "Слишком длинное имя",
    NAME_INVALID_CHARS: "Имя содержит недопустимые символы",
    CART_NOT_FOUND: "Корзина не найдена. Обновите страницу",
    INVALID_ORDER_ID: "Заказ не найден. Проверьте номер или обновите страницу",
    ORDER_NOT_FOUND: "Заказ не найден. Обновите страницу",
    ORDER_CANCEL_ERROR:
        "Не удалось отменить заказ. Попробуйте позже или обратитесь в поддержку",
    PRODUCT_NOT_FOUND:
        "Товар не найден. Обновите страницу или попробуйте позже",
    PAYMENT_CREATION_ERROR: "Ошибка при создании платежа, попробуйте позже",
    PAYMENT_STATUS_SYNC_ERROR:
        "Ошибка при синхронизации статуса платежа, обновите страницу или попробуйте позже",
};

// Константа со всеми статусами ошибок
const STATUS_MESSAGES = {
    // Маппинг статусов
    400: "Неверный запрос. Обновите страницу",
    401: "Требуется авторизация. Войдите в аккаунт",
    403: "Доступ запрещен. Недостаточно прав",
    404: "Данные не найдены. Обновите страницу",
    405: "Метод не поддерживается. Обновите страницу",
    409: "Конфликт при обработке заказа, попробуйте позже. Обновите страницу и попробуйте снова",
    410: "Ресурс удален. Обновите страницу",
    422: "Ошибка в данных. Проверьте введённую информацию",
    429: "Слишком много запросов. Попробуйте через минуту",
    500: "Внутренняя ошибка сервера. Попробуйте позже",
    502: "Сервис временно недоступен. Попробуйте через несколько минут",
    503: "Сервер перегружен. Попробуйте позже",
};

// Функция для получения понятного сообщения об ошибке
export function getErrorMessage(errorCode, status) {
    if (errorCode && ERROR_CODE_MESSAGES[errorCode]) {
        return ERROR_CODE_MESSAGES[errorCode];
    }

    if (status && STATUS_MESSAGES[status]) {
        return STATUS_MESSAGES[status];
    }

    return "Произошла ошибка. Попробуйте позже";
}

// крутой класс через ES6 для универсальной модалки ошибки оплаты с текстом
export class ErrorModal {
    static #allInstances = new Set(); // все созданные объекты класса

    #modal = null;
    #modalText = null;
    #openButtons = [];
    #handleOutsideClick = null; // функция закрытия

    // Метод инициализации (#приватный)
    #init(modalElId, modalTextElId) {
        // Проверка что оба аргумента переданы и это строки
        if (
            typeof modalElId !== "string" ||
            typeof modalTextElId !== "string"
        ) {
            throw new Error("[ErrorModal] Arguments must be string");
        }

        // Проверка что аргументы не пустые
        if (!modalElId.trim() || !modalTextElId.trim()) {
            throw new Error("[ErrorModal] Arguments empty");
        }

        this.#modal = document.getElementById(modalElId);
        this.#modalText = document.getElementById(modalTextElId);

        // Проверяем что на странице есть такие элементы
        if (!this.#modal || !this.#modalText) {
            console.error("Modal elements not found");
            return;
        }

        // Определяем функцию правильного закрытия
        this.#handleOutsideClick = (e) => {
            // закрывается при нажатии НЕ на кнопку и НЕ на модалку, чтобы не закрывалась сразу
            let clickedOnOpenButton = false;

            for (const button of this.#openButtons) {
                if (!button) continue; // если кнопка null

                // Кликнули прямо на кнопку
                if (e.target === button) {
                    clickedOnOpenButton = true;
                    break;
                }

                // Или кликнули внутри кнопки
                if (button.contains(e.target)) {
                    clickedOnOpenButton = true;
                    break;
                }
            }

            if (clickedOnOpenButton) {
                return;
            }

            if (!this.#modal.contains(e.target)) {
                this.close();
            }
        };
    }

    // Конструктор
    constructor(modalElId, modalTextElId) {
        // Проверяем DOM
        if (document.readyState === "loading") {
            throw new Error("Create modal after DOM is loaded");
        }

        this.#init(modalElId, modalTextElId);

        ErrorModal.#allInstances.add(this);
    }

    // Метод для установки кнопки открытия (чтобы модалка сразу не закрывалась)
    addOpenButton(selectorOrElement) {
        let button = null;

        if (typeof selectorOrElement === "string") {
            // Пробуем как ID, потом как селектор
            button =
                document.getElementById(selectorOrElement) ||
                document.querySelector(selectorOrElement);
        } else if (selectorOrElement instanceof HTMLElement) {
            button = selectorOrElement;
        }

        if (button && !this.#openButtons.includes(button)) {
            this.#openButtons.push(button);
        }

        return this; // паттерн Method Chaining (чейнинг методов), который позволяет вызывать методы цепочкой
    }

    open(innerText) {
        if (!this.#modal) return;

        if (typeof innerText !== "string") return;

        this.close();

        // Когда модалка открывается вешаем на документ обработчик правильного закрытия
        document.addEventListener("click", this.#handleOutsideClick);

        this.#modalText.textContent = innerText;
        this.#modal.classList.remove("hidden");

        return this; // паттерн Method Chaining (чейнинг методов), который позволяет вызывать методы цепочкой
    }

    close() {
        if (this.#modal) this.#modal.classList.add("hidden");
        if (this.#modalText) this.#modalText.textContent = "";
        if (this.#handleOutsideClick)
            document.removeEventListener("click", this.#handleOutsideClick);
    }

    // Статический метод для скрытия всех модалок
    static closeAll() {
        for (const instance of ErrorModal.#allInstances) {
            instance.close();
        }
    }

    // Метод для удаления
    destroy() {
        this.close();
        this.#modal = null;
        this.#modalText = null;
        this.#openButtons = [];
        this.#handleOutsideClick = null;
        ErrorModal.#allInstances.delete(this);
    }

    // Статический (для всего класса в целом) метод удаления (очищает все созданные объекты)
    static destroyAll() {
        // Вызываем destroy() для каждого экземпляра
        for (const instance of ErrorModal.#allInstances) {
            instance.destroy();
        }

        // Очищаем Set всех созданных объектов
        ErrorModal.#allInstances.clear();
    }
}

// Функция для обновления счетчика в header
export async function updateHeaderCounter(qty) {
    const headerCounter = document.getElementById("header-cart-counter");
    if (!headerCounter) return;

    headerCounter.textContent = Number(qty);
}

// Функция для навешивания лоадера на кнопку (disabled + loader-класс)
export function setButtonLoading(btn, isLoading) {
    // Отключаем кликабельность
    btn.disabled = isLoading;
    // Визуальный лоадер через css-класс
    btn.classList.toggle("is-loading", isLoading);
    // Aria-атрибут для доступности
    btn.setAttribute("aria-disabled", String(isLoading));
}

// Функция для экранирования html перед выводом
export function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

// Создаем объект для форматирования цены (два знака после запятой)
const priceFormatter = new Intl.NumberFormat("ru-RU", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

// Функция для форматирования цены
export function formatPrice(value) {
    const number = Number(value) || 0;
    return `${priceFormatter.format(number)}`;
}

// Функция для форматирования даты в формате 31.01 12:00
export function formatDate(dateInput) {
    if (!dateInput) {
        console.error("[utils] formatDate: dateInput null или undefined");
        return "";
    }

    // Преобразуем в Date объект (если пришла строка)
    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);

    // Проверка на валидность даты
    if (isNaN(date.getTime())) {
        console.error("[utils] formatDate: неправильная data:", dateInput);
        return "";
    }

    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${day}.${month} ${hours}:${minutes}`;
}
