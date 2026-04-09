/* global grecaptcha */ // глобальная переменная из скрипта, подключаемого в общем лэйауте

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
        error.retryAfter = response.headers.get("Retry-After"); // хедер с временем кулдауна
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

// Функция для получения токена от капчи
export async function getRecaptchaToken(action) {
    if (!action) {
        throw new Error("reCAPTCHA action is required");
    }

    const siteKey = document.body?.dataset?.recaptchaSiteKey;
    if (!siteKey) {
        throw new Error("reCAPTCHA site key is missing on <body>");
    }

    if (typeof grecaptcha === "undefined") {
        throw new Error("reCAPTCHA script is not loaded");
    }

    // Ждём, пока библиотека будет готова
    await new Promise((resolve) => {
        grecaptcha.ready(resolve);
    });

    // Получаем токен для конкретного действия
    const token = await grecaptcha.execute(siteKey, { action });

    if (!token) {
        throw new Error("Failed to get reCAPTCHA token");
    }

    return token;
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
    RECAPTCHA_FAILED: "Подозрительная активность, попробуйте позже",
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
        "Для этого действия необходимо подтвердить почту, для этого перейдите в личный кабинет",
    EMAIL_ALREADY_VERIFIED:
        "Почта уже подтверждена, обновите страницу или попробуйте позже",
    EMAIL_RATE_LIMIT:
        "Повторная отправка письма будет доступна через время, попробуйте позже",
    INVALID_CREDENTIALS: "Неверный email или пароль",
    LOGIN_ATTEMPTS_EXCEEDED: "Слишком много попыток, попробуйте позже",
    PASSWORD_REQUIRED: "Требуется пароль",
    PASSWORD_TOO_SHORT: "Слишком короткий пароль",
    PASSWORD_TOO_LONG: "Слишком длинный пароль",
    PASSWORD_INVALID_CHARS: "Пароль содержит недопустимые символы",
    PASSWORD_MISMATCH: "Пароли не совпадают, проверьте пароль",
    WRONG_PASSWORD: "Неверный пароль, попробуйте еще раз",
    SAME_PASSWORD:
        "Новый пароль должен отличаться от предыдущего, введите новый пароль",
    NAME_REQUIRED: "Требуется имя",
    NAME_TOO_LONG: "Слишком длинное имя",
    NAME_INVALID_CHARS: "Имя содержит недопустимые символы",
    TOKEN_INVALID:
        "Ссылка недействительна. Перейдите по ссылке из письма ещё раз или запросите новое письмо",
    TOKEN_EXPIRED: "Ссылка устарела, запросите новое письмо",
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

// Функция для отключение кликабельности кнопки (disabled + disabled-класс)
export function setButtonDisable(btn, isDisabled) {
    // Отключаем кликабельность
    btn.disabled = isDisabled;
    // Визуал через css-класс
    btn.classList.toggle("is-disabled", isDisabled);
    // Aria-атрибут для доступности
    btn.setAttribute("aria-disabled", String(isDisabled));
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
