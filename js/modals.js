/* global grecaptcha */
/* global Inputmask */
import { searchProducts } from "./product/product.api.js";
import { getErrorMessage } from "./utils.js";

// универсальная функция для распознавания ответа
async function parseResponse(response) {
    const contentType = response.headers.get("content-type");
    const text = await response.text();

    // Если это JSON - парсим как JSON
    if (contentType && contentType.includes("application/json")) {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("JSON parse error:", e);
            throw new Error(
                "Ошибка, некорректный JSON от сервера, попробуйте позже",
            );
        }
    }

    // Если не JSON - обрабатываем как текст

    // дебаг
    console.log("Content-Type:", contentType); // заголовок
    console.log("Raw response:", text); // Что пришло на самом деле

    throw new Error(
        "Ошибка, сервер вернул некорректный ответ, попробуйте позже",
    );
}

// повторные отправки запросов на сервак
async function fetchWithRetry(url, options, retries = 2) {
    for (let attempt = 0; attempt <= retries; attempt++) {
        try {
            const response = await fetch(url, options);
            if (response.ok) {
                const result = await parseResponse(response);
                return result;
            } else {
                throw new Error(`HTTP ${response.status}`);
            }
        } catch (error) {
            if (attempt === retries) throw error;
            await new Promise((resolve) =>
                setTimeout(resolve, 1000 * (attempt + 1)),
            );
        }
    }
}

//открытие модалки
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add("open");
}

//закрытие модалки
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove("open");
}

function setupModal(modalId, openBtnId, closeBtnId) {
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);
    const closeBtn = document.getElementById(closeBtnId);
    if (!openBtn || !modal || !closeBtn) return;

    openBtn.addEventListener("click", () => openModal(modalId));
    closeBtn.addEventListener("click", () => closeModal(modalId));

    modal.addEventListener("click", function (e) {
        if (e.target === this) {
            closeModal(modalId);
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal.classList.contains("open")) {
            closeModal(modalId);
        }
    });
}

setupModal(
    "authorization-modal",
    "open-authorization-modal",
    "close-authorization-modal",
);
setupModal(
    "authorization-modal",
    "open-my-orders-for-guest",
    "close-authorization-modal",
);
setupModal(
    "registration-modal",
    "open-registration-modal",
    "close-registration-modal",
);
setupModal(
    "registration-modal",
    "open-registration-modal-from-cart",
    "close-registration-modal",
);
setupModal(
    "account-edit-modal",
    "open-account-editior-modal",
    "close-account-edit-modal",
);
setupModal(
    "account-delete-modal",
    "open-account-edit-modal",
    "close-account-delete-modal",
);
setupModal(
    "account-exit-modal",
    "open-account-exit-modal",
    "close-account-exit-modal",
);

// Отркытие по дата-атрибуту (все кнопки с ним открывают), делигирование событий. Потом переделать все модалки, сделав все не через
// setup modal, а через такое делегирование и data-атрибуты вместо id. Также все открытие модалок делать через добавление к кнопке
// дата атрибута
document.addEventListener("click", (e) => {
    const trigger = e.target.closest("[data-open-modal]");
    if (!trigger) return;

    const name = trigger.dataset.openModal;
    const modal = document.getElementById(`${name}-modal`);
    if (modal) modal.classList.add("open");
});

// функция очистки номера телефона
function validatePhoneNumber(phone) {
    let cleaned = phone.replace(/[^\d+]/g, "");

    const regex = /^\+79\d{9}$/;

    return {
        isValid: regex.test(cleaned),
        formatted: cleaned,
    };
}

//получение токена капчи
async function getRecaptchaToken(form) {
    const siteKey = form.dataset.recaptchaSiteKey;
    await grecaptcha.ready;
    return await grecaptcha.execute(siteKey, { action: "submit" });
}

// крутой класс через ES6 для универсальной модалки в хедере с текстом
// Singleton паттерн - гарантируем один экземпляр (будет создан всего один объект класса)
class HeaderModal {
    // static поля - общие для ВСЕХ объектов класса
    static #instance = null;

    #closeTimer = null;
    #modal = null;
    #text = null;
    #progress = null;
    #closeBtn = null;

    constructor() {
        // если уже есть экземпляр класса то просто возвращаем его
        if (HeaderModal.#instance) {
            return HeaderModal.#instance;
        }

        // Автоинициализация при загрузке
        if (document.readyState === "loading") {
            // стрелочная функция берет переменные (и this) и работает с ними для ЭТОГО созданного объекта
            // this берет переменные из ЭТОГО созданного объекта
            document.addEventListener("DOMContentLoaded", () => this.#init());
        } else {
            // тут # т.к. init - приватный метод
            this.#init();
        }

        // сохраняем единственный instance (экземпляр)
        HeaderModal.#instance = this;
    }

    #init() {
        this.#modal = document.getElementById("header-modal");
        this.#text = document.getElementById("header-modal-text");
        this.#progress = document.getElementById("header-modal-progress-fill");
        this.#closeBtn = document.getElementById("header-modal-close");

        if (!this.#modal || !this.#text || !this.#progress || !this.#closeBtn) {
            // тут потом правильное логирование
            console.error("Modal elements not found");
            return;
        }

        this.#closeBtn.addEventListener("click", () => this.close());
    }

    open(innerText) {
        if (!this.#modal) return;

        this.close();

        this.#text.textContent = innerText;
        this.#modal.classList.remove("hidden");

        // Сброс и запуск анимации прогресса
        this.#progress.classList.remove("shrinking");
        void this.#progress.offsetWidth; // Принудительный reflow, гарантируем что анимация перезапуститься
        this.#progress.classList.add("shrinking");

        // Таймер автоскрытия
        this.#closeTimer = setTimeout(() => this.close(), 5000);
    }

    close() {
        if (!this.#modal) return;

        this.#modal.classList.add("hidden");
        this.#progress.classList.remove("shrinking");

        if (this.#text) this.#text.textContent = "";
        if (this.#closeTimer) clearTimeout(this.#closeTimer);

        this.#closeTimer = null;
    }
}
// создаем объект класса
const headerModal = new HeaderModal();

// крутой класс через ES6 для управления SMS таймерами
// Singleton паттерн - гарантируем один экземпляр (будет создан всего один объект класса)
class SmsTimerManager {
    // static поля - общие для ВСЕХ объектов класса
    static #instance = null;

    #resendTimer = null;
    #resendTimeLeft = 0;
    #smsFirstCodeButton = null;
    #smsRetryCodeButton = null;
    #timerSpans = null;

    constructor() {
        // если уже есть экземпляр класса то просто возвращаем его
        if (SmsTimerManager.#instance) {
            return SmsTimerManager.#instance;
        }

        // Автоинициализация при загрузке
        if (document.readyState === "loading") {
            // стрелочная функция берет переменные (и this) и работает с ними для ЭТОГО созданного объекта
            // this берет переменные из ЭТОГО созданного объекта
            document.addEventListener("DOMContentLoaded", () => this.#init());
        } else {
            // тут # т.к. init - приватный метод
            this.#init();
        }

        // сохраняем единственный instance (экземпляр)
        SmsTimerManager.#instance = this;
    }

    #init() {
        this.#smsFirstCodeButton = document.getElementById("first-sms-code");
        this.#smsRetryCodeButton = document.getElementById("retry-sms-code");
        this.#timerSpans = document.querySelectorAll(
            `[data-action="retry-sms-code-timer"]`,
        );

        if (
            !this.#smsFirstCodeButton ||
            !this.#smsRetryCodeButton ||
            this.#timerSpans.length === 0
        ) {
            // тут потом правильное логирование
            console.error("Modal elements not found");
            return;
        }
    }

    //запуск таймера повторной отправки
    startResendTimer(seconds = 60) {
        this.#resendTimeLeft = seconds;

        // Блокируем кнопки сразу
        this.#smsFirstCodeButton.disabled = true;
        this.#smsRetryCodeButton.disabled = true;

        this.#resendTimer = setInterval(() => {
            this.#resendTimeLeft--;

            if (this.#resendTimeLeft <= 0) {
                clearInterval(this.#resendTimer);
                this.#resendTimer = null;
                this.#smsFirstCodeButton.disabled = false;
                this.#smsRetryCodeButton.disabled = false;
                this.#timerSpans.forEach((span) => {
                    span.textContent = ``;
                });
            } else {
                this.#timerSpans.forEach((span) => {
                    span.textContent = `(${this.#resendTimeLeft}с)`;
                });
            }
        }, 1000);
    }

    //остановка таймера повторной отправки
    clearResendTimer() {
        if (this.#resendTimer) {
            clearInterval(this.#resendTimer);
            this.#resendTimer = null;
            this.#resendTimeLeft = 0;

            this.#timerSpans.forEach((span) => {
                span.textContent = ``;
            });
        }
    }

    //запуск таймера блокировки
    startUnlockTimer(blockedUntilTimestamp) {
        this.clearResendTimer();

        const interval = setInterval(() => {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = blockedUntilTimestamp - now;

            if (timeLeft <= 0) {
                clearInterval(interval);
            }
        }, 1000);
    }
}

const smsTimerManager = new SmsTimerManager();

// версия объекта для управления SMS таймерами через IIFE
// const SmsTimerManager = (function() {
//     let resendTimer = null;
//     let resendTimeLeft = 0;
//     let smsFirstCodeButton, smsRetryCodeButton, timerSpans;

//     function init() {
//         smsFirstCodeButton = document.getElementById('first-sms-code');
//         smsRetryCodeButton = document.getElementById('retry-sms-code');
//         timerSpans = document.querySelectorAll(`[data-action="retry-sms-code-timer"]`);

//         if (!smsFirstCodeButton || !smsRetryCodeButton || timerSpans.length === 0) {
//             console.error('Modal elements not found');
//             return;
//         }
//     }

//     //запуск таймера повторной отправки
//     function startResendTimer(seconds = 60) {
//         resendTimeLeft = seconds;

//         // Блокируем кнопки сразу
//         smsFirstCodeButton.disabled = true;
//         smsRetryCodeButton.disabled = true;

//         resendTimer = setInterval(() => {
//             resendTimeLeft--;

//             if (resendTimeLeft <= 0) {
//                 clearInterval(resendTimer);
//                 smsFirstCodeButton.disabled = false;
//                 smsRetryCodeButton.disabled = false;
//                 timerSpans.forEach(span => {
//                     span.textContent = ``;
//                 });
//             } else {
//                 timerSpans.forEach(span => {
//                     span.textContent = `(${resendTimeLeft}с)`;
//                 });
//             }
//         }, 1000);
//     }

//     //остановка таймера повторной отправки
//     function clearResendTimer() {
//         if (resendTimer) {
//             clearInterval(resendTimer);
//             resendTimer = null;
//             resendTimeLeft = 0;

//             timerSpans.forEach(span => {
//                 span.textContent = ``;
//             });
//         }
//     }

//     //запуск таймера блокировки
//     function startUnlockTimer(blockedUntilTimestamp) {
//         clearResendTimer();

//         const interval = setInterval(() => {
//             const now = Math.floor(Date.now() / 1000);
//             const timeLeft = blockedUntilTimestamp - now;

//             if (timeLeft <= 0) {
//                 clearInterval(interval);
//             }
//         }, 1000);
//     }

//     // Автоинициализация при загрузке
//     if (document.readyState === 'loading') {
//         document.addEventListener('DOMContentLoaded', init);
//     } else {
//         init();
//     }

//     // возвращаем функции для открытия и закрытия
//     return { startResendTimer, clearResendTimer,  startUnlockTimer};
// })(); // () на конце выполняет сразу (для всех функций), и это исользуется все последующее разы

// проверка на блок по попыткам
async function isAttemptsBlocked() {
    const incorrectSmsCodeModal = document.getElementById(
        "incorrect-sms-code-modal",
    );

    try {
        const response = await fetch("/src/isAttemptsBlocked.php");
        const result = await parseResponse(response);

        if (result.success) {
            return false;
        }

        if (result.error === "blocked") {
            smsTimerManager.clearResendTimer();
            smsTimerManager.startUnlockTimer(result.blocked_until);

            incorrectSmsCodeModal.querySelector(
                ".error_modal_text",
            ).textContent =
                `Система заблокирована до ${new Date(result.blocked_until * 1000).toLocaleTimeString()}`;
            incorrectSmsCodeModal.classList.add("open");

            document.querySelector('input[name="sms_code"]').value = "";

            return true;
        }

        return false;
    } catch (error) {
        incorrectSmsCodeModal.querySelector(".error_modal_text").textContent =
            `Ошибка проверки блокировки: ${error instanceof Error ? error.message : String(error)}`;
        return false;
    }
}

// проверка наличия пользователя
async function isUserAlreadyExist() {
    const userAlreadyExistsModal = document.getElementById(
        "user-already-exists-modal",
    );
    const incorrectSmsCodeModal = document.getElementById(
        "incorrect-sms-code-modal",
    );

    const phoneNumberInput = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="login"]');
    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

    try {
        const response = await fetch("/src/isUserAlreadyExist.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                login: phoneValidation.formatted,
            }),
        });

        const result = await parseResponse(response);

        if (result.success) {
            userAlreadyExistsModal.classList.add("open");
            return true;
        } else {
            return false;
        }
    } catch (error) {
        incorrectSmsCodeModal.querySelector(".error_modal_text").textContent =
            `Ошибка проверки блокировки: ${error instanceof Error ? error.message : String(error)}`;
        return false;
    }
}

// переключение состояния формы
function toggleSmsCodeState() {
    const smsFirstCodeButton = document.getElementById("first-sms-code");
    const smsRetryCodeButton = document.getElementById("retry-sms-code");
    const phoneChangeButton = document.getElementById("phone-change");
    const SmsCodeSection = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="sms_code"]')
        .closest(".registration_modal_input_back");
    const phoneNumberSection = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="login"]')
        .closest(".registration_modal_input_back");

    document.querySelector('input[name="sms_code"]').value = "";

    smsFirstCodeButton.classList.toggle("hidden");
    smsRetryCodeButton.classList.toggle("hidden");
    phoneChangeButton.classList.toggle("hidden");
    SmsCodeSection.classList.toggle("hidden");
    phoneNumberSection.classList.toggle("hidden");
}

// отправка кода
async function sendSmsCode() {
    if (await isAttemptsBlocked()) {
        return;
    }

    if (await isUserAlreadyExist()) {
        return;
    }

    const phoneNumberInput = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="login"]');
    const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

    const incorrectPhoneModal = document.getElementById(
        "incorrect-phone-number-modal",
    );
    const incorrectSmsCodeModal = document.getElementById(
        "incorrect-sms-code-modal",
    );

    const smsFirstCodeButton = document.getElementById("first-sms-code");
    const smsRetryCodeButton = document.getElementById("retry-sms-code");

    const smsFirstCodeButtonText = smsFirstCodeButton.querySelector(
        ".first_sms_code_btn_text",
    );
    const smsRetryCodeButtonText = smsRetryCodeButton.querySelector(
        ".retry_sms_code_btn_text",
    );

    const smsFirstCodeButtonTextOrgnl = smsFirstCodeButtonText.textContent;
    const smsRetryCodeButtonTextOrgnl = smsRetryCodeButtonText.textContent;

    if (!smsFirstCodeButton.classList.contains("hidden")) {
        smsFirstCodeButton.disabled = true;
        smsFirstCodeButtonText.textContent = "Обработка...";
    } else {
        smsRetryCodeButton.disabled = true;
        smsRetryCodeButtonText.textContent = "Обработка...";
    }

    try {
        if (!phoneValidation.isValid) {
            incorrectPhoneModal.classList.add("open");
            smsFirstCodeButtonText.textContent = smsFirstCodeButtonTextOrgnl;
            smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
            smsFirstCodeButton.disabled = false;
            smsRetryCodeButton.disabled = false;
            return;
        }

        // передаем телефон В POST для отправки смс
        const response = await fetch("/src/smscSend.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                phone: phoneValidation.formatted,
            }),
        });

        const result = await parseResponse(response);

        if (!result.success) {
            throw new Error(
                result.error ||
                    result.message ||
                    `Ошибка ${response.status}! Попробуйте еще раз`,
            );
        }

        if (!smsFirstCodeButton.classList.contains("hidden")) {
            toggleSmsCodeState();
        }

        document.querySelector('input[name="sms_code"]').value = "";

        //это для теста без реальных sms, потом убрать!!!
        alert(
            `смски дорогие, пока так (но функционал для реальных смс уже есть) Код подтверждения: ${result.debug_code}, был бы отправлен на номер ${result.debug_phone}`,
        );

        headerModal.open("SMS-код был отправлен на указанный номер");

        smsTimerManager.startResendTimer(30);
    } catch (error) {
        if (smsFirstCodeButton.classList.contains("hidden")) {
            toggleSmsCodeState();
        }

        incorrectSmsCodeModal.querySelector(".error_modal_text").textContent =
            error.message;
        incorrectSmsCodeModal.classList.add("open");

        smsFirstCodeButton.disabled = false;
        smsRetryCodeButton.disabled = false;
    } finally {
        smsFirstCodeButtonText.textContent = smsFirstCodeButtonTextOrgnl;
        smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
    }
}

// подтверждение кода
async function confirmSmsCode() {
    if (await isAttemptsBlocked()) {
        return;
    }

    const smsCodeInput = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="sms_code"]');
    const incorrectSmsCodeModal = document.getElementById(
        "incorrect-sms-code-modal",
    );

    const smsFirstCodeButton = document.getElementById("first-sms-code");

    const smsRetryCodeButton = document.getElementById("retry-sms-code");
    const smsRetryCodeButtonText = smsRetryCodeButton.querySelector(
        ".retry_sms_code_btn_text",
    );
    const smsRetryCodeButtonTextOrgnl = smsRetryCodeButtonText.textContent;

    const phoneNumberInput = document
        .querySelector(".registration_modal_form")
        .querySelector('input[name="login"]');

    smsFirstCodeButton.disabled = true;
    smsRetryCodeButton.disabled = true;
    smsRetryCodeButtonText.textContent = "Обработка...";

    try {
        const response = await fetch("/src/smscVerify.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                code: smsCodeInput.value,
            }),
        });

        const result = await parseResponse(response);

        if (result.error === "blocked") {
            smsTimerManager.startUnlockTimer(result.blocked_until);
            smsFirstCodeButton.disabled = false;
            smsRetryCodeButton.disabled = false;
            throw new Error(
                `Система заблокирована до ${new Date(result.blocked_until * 1000).toLocaleTimeString()}`,
            );
        }

        if (!result.success) {
            throw new Error(
                result.error ||
                    result.message ||
                    `Ошибка ${response.status}! Попробуйте еще раз`,
            );
        }

        smsTimerManager.clearResendTimer();

        phoneNumberInput.value = validatePhoneNumber(
            phoneNumberInput.value,
        ).formatted;
        phoneNumberInput.readOnly = true;

        toggleSmsCodeState();

        document.querySelector('input[name="sms_code"]').value = "";

        smsFirstCodeButton.textContent = "Успешно";
    } catch (error) {
        incorrectSmsCodeModal.querySelector(".error_modal_text").textContent =
            error.message;
        incorrectSmsCodeModal.classList.add("open");
    } finally {
        smsRetryCodeButtonText.textContent = smsRetryCodeButtonTextOrgnl;
    }
}

// первичная отправка кода
document
    .getElementById("first-sms-code")
    .addEventListener("click", sendSmsCode);

// отправить код снова
document
    .getElementById("retry-sms-code")
    .addEventListener("click", sendSmsCode);

// изменить телефон
document
    .getElementById("phone-change")
    .addEventListener("click", async function () {
        toggleSmsCodeState();
    });

// Обработчик нажатия enter
document
    .querySelector('input[name="sms_code"]')
    .addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            confirmSmsCode();
        }
    });

// Обработчик ввода кода (по 5 символам)
document
    .querySelector('input[name="sms_code"]')
    .addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "");
        if (this.value.length === 5) {
            confirmSmsCode();
        }
    });

// маска для номера телефона
document.addEventListener("DOMContentLoaded", function () {
    Inputmask({
        mask: "+7 (999) 999-99-99",
        placeholder: "_",
        clearIncomplete: true,
        showMaskOnHover: false,
    }).mask('input[name="login"][type="tel"]');
});

// Обработчик ввода имени
document.querySelectorAll('input[name="name"]').forEach((input) => {
    input.addEventListener("input", function () {
        this.value = this.value.replace(/[^а-яёА-ЯЁ\s-]/g, "");
    });
});

// Обработчик поиска товаров
document.getElementById("header-search-input").addEventListener(
    "input",
    (function () {
        let searchTimeout;

        return async function () {
            let query = this.value.trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                const headerSearchCancelButton = document.getElementById(
                    "header-search-cancel-button",
                );
                const queryProductsContainer = document.getElementById(
                    "query-products-container",
                );

                queryProductsContainer.classList.toggle("hidden", !query);
                headerSearchCancelButton.classList.toggle("hidden", !query);

                if (!query) return;

                const loaderTimer = setTimeout(() => {
                    queryProductsContainer.innerHTML = `<div class="search_empty">Поиск...</div>`;
                }, 200);

                try {
                    const queryProducts = await searchProducts(query);

                    clearTimeout(loaderTimer);

                    if (queryProducts.length == 0) {
                        queryProductsContainer.innerHTML = `<div class="search_empty">Ничего не найдено</div>`;
                    } else {
                        queryProductsContainer.innerHTML = queryProducts
                            .map(
                                (queryProduct) => `
                        <a href="/product/${queryProduct.slug}">
                                <div class="product">
                                    <div class="product_click">
                                        <img class="product_img_1" src="${queryProduct.image_path}">
                                        <div class="product_name_1">
                                            ${queryProduct.name}
                                        </div>
                                        <div class="product_price_1">
                                            ${queryProduct.price} ₽
                                        </div>
                                    </div>
                                </div>
                            </a>
                    `,
                            )
                            .join("");
                    }
                } catch (e) {
                    clearTimeout(loaderTimer);

                    // Логирование в консоль с полным контекстом
                    console.error("[product-search] Ошибка при поиске товара", {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload, // тот самый data
                        query,
                    });

                    // Отображение ошибки в поле поисковика
                    const message = getErrorMessage(e.code, e.status);
                    queryProductsContainer.innerHTML = `<div class="search_empty">${message}</div>`;
                }
            }, 300);
        };
    })(),
);

// Клик на поле ввода
document
    .getElementById("header-search-input")
    .addEventListener("focus", function () {
        const queryProductsContainer = document.getElementById(
            "query-products-container",
        );
        if (!queryProductsContainer) return;

        const query = this.value.trim();
        if (!query) return;

        queryProductsContainer.classList.remove("hidden");
    });

// Нажатие на крестик (закртие поля воода и очистка инпута)
document
    .getElementById("header-search-cancel-button")
    .addEventListener("click", function () {
        const queryProductsContainer = document.getElementById(
            "query-products-container",
        );
        queryProductsContainer.classList.add("hidden");

        const headerSearchInput = document.getElementById(
            "header-search-input",
        );
        headerSearchInput.value = "";

        this.classList.add("hidden");

        headerSearchInput.focus();
    });

// Клик не по поисковому блоку закрывает его
window.addEventListener("click", function (e) {
    const headerSearchBlock = document.getElementById("header-search");
    if (!headerSearchBlock) return;

    // Если клик был внутри headerSearchBlock - ничего не делаем
    if (headerSearchBlock.contains(e.target)) return;

    const queryProductsContainer = document.getElementById(
        "query-products-container",
    );
    if (!queryProductsContainer) return;

    queryProductsContainer.classList.add("hidden");
});

// потдтверждение формы регистрации
document
    .querySelector(".registration_modal_form")
    .addEventListener("submit", async function (e) {
        e.preventDefault();

        if (await isAttemptsBlocked()) {
            return;
        }

        const token = await getRecaptchaToken(this);

        const phoneNumberInput = this.querySelector('input[name="login"]');
        const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

        const password = this.querySelector('input[name="password"]').value;
        const confirmPassword = this.querySelector(
            'input[name="confirm-password"]',
        ).value;

        const submitRegistrtionButton = document.getElementById(
            "submit-registration",
        );

        const userAlreadyExistsModal = document.getElementById(
            "user-already-exists-modal",
        );
        const mismatchModal = document.getElementById(
            "password-mismatch-modal",
        );
        const incorrectPhoneModal = document.getElementById(
            "incorrect-phone-number-modal",
        );
        const incorrectSmsCodeModal = document.getElementById(
            "incorrect-sms-code-modal",
        );

        try {
            submitRegistrtionButton.textContent = "Регистрация...";
            submitRegistrtionButton.disabled = true;

            if (password !== confirmPassword) {
                throw new Error("password_mismatch");
            }

            if (!phoneValidation.isValid) {
                throw new Error("incorrect_phone");
            }

            const formData = new FormData(this);
            formData.set("login", phoneValidation.formatted);
            formData.append("recaptcha_response", token);

            const result = await fetchWithRetry(this.action, {
                method: "POST",
                body: formData,
            });

            if (!result.success) {
                throw new Error(result.message);
            }

            window.location.reload();
        } catch (error) {
            const errorMessages = {
                user_already_exists: "Пользователь уже зарегистрирован",
                phone_not_verified: "Телефон не подтвержден",
                phone_changed: "Телефон был изменен после подтверждения по sms",
                code_expired: "Код подтверждения устарел",
                recaptcha_false: "Не удалось пройти проверку на ботов",
                error: "Ошибка",
            };

            if (error.message === "user_already_exists") {
                userAlreadyExistsModal.classList.add("open");
            } else if (error.message === "password_mismatch") {
                mismatchModal.classList.add("open");
            } else if (error.message === "incorrect_phone") {
                incorrectPhoneModal.classList.add("open");
            } else if (errorMessages[error.message]) {
                incorrectSmsCodeModal.querySelector(
                    ".error_modal_text",
                ).textContent = errorMessages[error.message];
                incorrectSmsCodeModal.classList.add("open");
            } else if (error.message) {
                incorrectSmsCodeModal.querySelector(
                    ".error_modal_text",
                ).textContent = error.message;
                incorrectSmsCodeModal.classList.add("open");
            } else {
                incorrectSmsCodeModal.querySelector(
                    ".error_modal_text",
                ).textContent = "Произошла ошибка";
                incorrectSmsCodeModal.classList.add("open");
            }
        } finally {
            submitRegistrtionButton.textContent = "Зарегистрироваться";
            submitRegistrtionButton.disabled = false;
        }
    });

// подтверждение формы авторизации
document
    .querySelector(".authorization_modal_form")
    .addEventListener("submit", function (e) {
        e.preventDefault();

        const phoneNumberInput = this.querySelector('input[name="login"]');
        const phoneValidation = validatePhoneNumber(phoneNumberInput.value);

        if (!phoneValidation.isValid) {
            headerModal.open("Неправильный формат номера");
            return;
        }

        const formData = new FormData(this);
        formData.set("login", phoneValidation.formatted);

        fetchWithRetry(this.action, {
            method: "POST",
            body: formData,
        })
            .then((data) => {
                if (data.success) {
                    window.location.reload();
                } else if (data.message === "wrong_password") {
                    document
                        .getElementById("wrong-password-modal")
                        .classList.add("open");
                } else if (data.message === "user_not_found") {
                    document
                        .getElementById("uknown-user-modal")
                        .classList.add("open");
                }
            })
            .catch((error) => {
                console.error("Ошибка:", error);
            });
    });

//открытие ошибки о несовпадении старого пароля
document
    .querySelector(".account_edit_modal .registration_modal_form")
    .addEventListener("submit", function (e) {
        //предотвращаем стандартную отправку формы
        e.preventDefault();

        const formData = new FormData(this);

        fetchWithRetry(this.action, {
            method: "POST",
            body: formData,
        })
            .then((data) => {
                if (data.success) {
                    window.location.reload();
                } else if (data.message === "old_password_missmatch") {
                    document
                        .getElementById("old-password-missmatch-modal")
                        .classList.add("open");
                }
            })
            .catch((error) => {
                console.error("Ошибка:", error);
            });
    });

function closeErrorModal(ErrorModalId) {
    const modal = document.getElementById(ErrorModalId);

    if (modal && modal.classList.contains("open")) {
        modal.classList.remove("open");
    }
}

document.addEventListener("click", function (e) {
    // Закрываем модалки только если клик НЕ на кнопки (мб добавить еще элементы если все равно сразу закрывается)
    if (!e.target.closest("button")) {
        closeErrorModal("incorrect-phone-number-modal");
        closeErrorModal("incorrect-sms-code-modal");
        closeErrorModal("password-mismatch-modal");
        closeErrorModal("uknown-user-modal");
        closeErrorModal("wrong-password-modal");
        closeErrorModal("user-already-exists-modal");
        closeErrorModal("old-password-missmatch-modal");
        closeErrorModal("modal-error-address-not-found");
        closeErrorModal("modal-error-address-empty");
        closeErrorModal("modal-error-address-timeout");
        closeErrorModal("flash-payment-error");
    }
});

function accountPasswordSwitch(nmbOfInpt) {
    const passwordInput = document.getElementById(
        "password-input-" + nmbOfInpt,
    );
    const passwordButton = document.getElementById(
        "account-edit-modal-password-button-" + nmbOfInpt,
    );
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        passwordButton.textContent = "Скрыть";
    } else {
        passwordInput.type = "password";
        passwordButton.textContent = "Показать";
    }
}

// Вешаем обработчик на кнопку
document
    .getElementById("account-edit-modal-password-button-1")
    .addEventListener("click", function () {
        accountPasswordSwitch(1);
    });
document
    .getElementById("account-edit-modal-password-button-2")
    .addEventListener("click", function () {
        accountPasswordSwitch(2);
    });

document.addEventListener("click", function (e) {
    // Добавляем проверку на существование parentElement
    if (
        e.target.parentElement?.classList?.contains(
            "product_minor_images_button",
        )
    ) {
        document.querySelector(".product_main_img").src = e.target.src;
    }
});
