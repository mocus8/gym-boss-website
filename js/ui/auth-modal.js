// Импортируем js (подключение этих js в других файлах не требуется)
import { login, register, forgotPassword } from "../users/auth/auth.api.js";
import { getErrorMessage, setButtonLoading } from "../utils.js";
import { notification } from "./notification.js";

// Класс для управления модалкой аутонтификации (вход/регистрация)
class AuthModal {
    #authModal = null;
    #overlay = null;
    #loginTab = null;
    #registerTab = null;
    #closeEl = null;

    #loginForm = null;
    #loginEmailInput = null;
    #loginPassInput = null;
    #loginSubmitBtn = null;

    #registerForm = null;
    #registerNameInput = null;
    #registerEmailInput = null;
    #registerPassInput = null;
    #registerConfirmPassInput = null;
    #registerSubmitBtn = null;

    #forgotPassBtn = null;
    #footerSwitchToLoginBtn = null;
    #footerSwitchToRegisterBtn = null;

    #isOpen = false;
    #previousActiveEl = null; // элемент, который был в фокусе, нужен чтобы вернуть фокус назад при закрытии модалки

    constructor() {
        // Автоинициализация при загрузке
        if (document.readyState === "loading") {
            // стрелочная функция берет переменные (и this) и работает с ними для ЭТОГО созданного объекта
            // this берет переменные из ЭТОГО созданного объекта
            document.addEventListener("DOMContentLoaded", () => this.#init(), {
                once: true,
            });
        } else {
            this.#init();
        }

        // Вешаем обработчик всех клавиш на функцию
        document.addEventListener("keydown", (e) => this.#handleKeydown(e));

        // Вешаем обработчики для открытия модалки (делигирование событий)
        document.addEventListener("click", (e) => {
            const target = e.target;

            if (target.closest('[data-modal-open="auth-modal"]')) {
                this.#setAuthMode("login");
                this.open();
            }
        });
    }

    // Метод открытия модалки
    open() {
        // Определяем активный до появления модалки элемент
        this.#previousActiveEl =
            document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;

        // Показываем модалку и ставим open флаг
        this.#authModal.classList.remove("hidden");
        this.#isOpen = true;

        // Ставим фокус на cancel модалки
        this.#closeEl.focus();
    }

    // Метод закрытия модалки
    close() {
        if (!this.#isOpen) return;

        this.#authModal.classList.add("hidden");
        this.#isOpen = false;

        // Возвращаем фокус на тот элемент, на котором он был до окрытия модалки
        if (
            this.#previousActiveEl &&
            document.contains(this.#previousActiveEl)
        ) {
            this.#previousActiveEl.focus();
        }
        this.#previousActiveEl = null;
    }

    #init() {
        this.#authModal = document.getElementById("auth-modal");
        this.#overlay = this.#authModal.querySelector("[data-modal-overlay]");
        this.#loginTab = this.#authModal.querySelector(
            '[data-auth-tab="login"]',
        );
        this.#registerTab = this.#authModal.querySelector(
            '[data-auth-tab="register"]',
        );

        this.#closeEl = this.#authModal.querySelector("[data-modal-close]");

        this.#loginForm = this.#authModal.querySelector("#login-form");
        this.#loginEmailInput = this.#loginForm.elements["email"];
        this.#loginPassInput = this.#loginForm.elements["password"];
        this.#loginSubmitBtn = this.#loginForm.querySelector(
            'button[type="submit"]',
        );

        this.#registerForm = this.#authModal.querySelector("#register-form");
        this.#registerNameInput = this.#registerForm.elements["name"];
        this.#registerEmailInput = this.#registerForm.elements["email"];
        this.#registerPassInput = this.#registerForm.elements["password"];
        this.#registerConfirmPassInput =
            this.#registerForm.elements["confirm_password"];
        this.#registerSubmitBtn = this.#registerForm.querySelector(
            'button[type="submit"]',
        );

        this.#forgotPassBtn = this.#authModal.querySelector(
            "#forgot-password-btn",
        );
        this.#footerSwitchToLoginBtn = this.#authModal.querySelector(
            '[data-auth-switch-to="login"]',
        );
        this.#footerSwitchToRegisterBtn = this.#authModal.querySelector(
            '[data-auth-switch-to="register"]',
        );

        if (
            !this.#authModal ||
            !this.#overlay ||
            !this.#loginTab ||
            !this.#registerTab ||
            !this.#closeEl ||
            !this.#loginForm ||
            !this.#loginEmailInput ||
            !this.#loginPassInput ||
            !this.#loginSubmitBtn ||
            !this.#registerForm ||
            !this.#registerNameInput ||
            !this.#registerEmailInput ||
            !this.#registerPassInput ||
            !this.#registerConfirmPassInput ||
            !this.#registerSubmitBtn ||
            !this.#forgotPassBtn ||
            !this.#footerSwitchToLoginBtn ||
            !this.#footerSwitchToRegisterBtn
        ) {
            console.error("[AuthModal] Необходимые элементы не найдены");
            return;
        }

        // Скрываем ошибки если они есть
        this.#hideErrors();

        // Переход к логину
        this.#loginTab.addEventListener("click", () =>
            this.#setAuthMode("login"),
        );
        this.#footerSwitchToLoginBtn.addEventListener("click", () =>
            this.#setAuthMode("login"),
        );

        // Переход к регистрации
        this.#registerTab.addEventListener("click", () =>
            this.#setAuthMode("register"),
        );
        this.#footerSwitchToRegisterBtn.addEventListener("click", () =>
            this.#setAuthMode("register"),
        );

        // Закрытие по крестику
        this.#closeEl.addEventListener("click", () => this.close());

        // Закрытие по клику на overlay (если он есть)
        if (this.#overlay) {
            this.#overlay.addEventListener("click", () => this.close());
        }

        // Подтверждение входа
        this.#loginSubmitBtn.addEventListener("click", async (e) =>
            this.#login(e),
        );

        // Подтверждение регистарции
        this.#registerSubmitBtn.addEventListener("click", async (e) =>
            this.#register(e),
        );

        // Клик по "забыли пароль"
        this.#forgotPassBtn.addEventListener("click", async () =>
            this.#forgotPass(),
        );
    }

    // Функция для обработки нажати клавиш Escape и Tab
    #handleKeydown(e) {
        if (!this.#isOpen) return;

        if (e.key === "Escape") {
            e.preventDefault();
            this.close();
            return;
        }

        if (e.key !== "Tab") return;

        // Focus trap: Tab/Shift+Tab будут переключаться только внутри модалки
        // Это нужно чтобы при Tab фокус не перешел на элемент под/за модалкой
        const focusables = this.#getFocusableElements();
        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        const active = document.activeElement;

        // Ограничиваем переключение фокуса между first и last элементами модалки
        if (e.shiftKey) {
            if (active === first || !this.#authModal.contains(active)) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (active === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    // Функция для нахождения интерактивных элементов модалки
    #getFocusableElements() {
        // Минимальный набор селекторов для tabbable элементов
        const selectors = [
            "a[href]",
            "button:not([disabled])",
            'input:not([disabled]):not([type="hidden"])',
            "select:not([disabled])",
            "textarea:not([disabled])",
            '[tabindex]:not([tabindex="-1"])',
        ].join(",");

        // Находим интерактивные в данный млмент элементы модалки
        return Array.from(this.#authModal.querySelectorAll(selectors)) // подходит под массив selectors
            .filter((node) => node instanceof HTMLElement) // является html элементом
            .filter((node) => !node.classList.contains("hidden")) // не имеет hidden класса
            .filter((node) => node.offsetParent !== null); // не display:none
    }

    // Переключение на 'login' / 'register'
    #setAuthMode(mode) {
        const isLogin = mode === "login";

        // Переключаем выделенные вкладки
        this.#loginTab.classList.toggle("chosen", isLogin);
        this.#registerTab.classList.toggle("chosen", !isLogin);

        // Очищаем ошибки и значения в формах
        this.#loginForm.reset();
        this.#registerForm.reset();
        this.#hideErrors();

        // Переключаем видимость форм
        this.#loginForm.classList.toggle("hidden", !isLogin);
        this.#registerForm.classList.toggle("hidden", isLogin);

        // Переключаем видимость опций в футере модалки
        this.#forgotPassBtn.classList.toggle("hidden", !isLogin);
        this.#footerSwitchToLoginBtn.classList.toggle("hidden", isLogin);
        this.#footerSwitchToRegisterBtn.classList.toggle("hidden", !isLogin);
    }

    // Подтверждение формы входа
    async #login(e) {
        e.preventDefault();

        // Скрываем ошибки если они есть
        this.#hideErrors();

        const form = this.#loginForm;
        const emailInput = form.elements["email"];
        const passInput = form.elements["password"];

        // Проверяем валидность формы и ее инпутов
        if (!form.checkValidity()) {
            if (!emailInput.checkValidity()) {
                this.#showInputError(emailInput, "Введите корректный email");
            }

            if (!passInput.checkValidity()) {
                this.#showInputError(
                    passInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            return;
        }

        // Получаем данные из инпутов
        const email = emailInput.value.trim();
        const password = passInput.value;

        // Отправляем запрос на вход
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(this.#loginSubmitBtn, true);

            await login(email, password);

            window.location.href = "/";
        } catch (e) {
            console.error("[auth-modal] Не удалось выполнить вход", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            });

            const message = getErrorMessage(e.code, e.status);

            // Маппим ошибки для показа под конкретными полями
            switch (e.code) {
                case "EMAIL_REQUIRED":
                case "EMAIL_INVALID":
                case "EMAIL_TOO_LONG":
                    this.#showInputError(emailInput, message);
                    break;

                case "INVALID_CREDENTIALS":
                case "PASSWORD_REQUIRED":
                case "PASSWORD_TOO_SHORT":
                case "PASSWORD_TOO_LONG":
                case "PASSWORD_INVALID_CHARS":
                    this.#showInputError(passInput, message);
                    break;

                case "LOGIN_ATTEMPTS_EXCEEDED": {
                    // Для этого случая вытаскиваем поле retryAfter из ошибки
                    const sec = parseInt(e.retryAfter, 10);
                    let text = "некоторое время";

                    // Если sec - нормально число, то берем его, иначе дефолт
                    // Если больше 60 секунд - переводим в минуты
                    if (!Number.isNaN(sec) && sec > 0) {
                        text =
                            sec >= 60
                                ? `${Math.ceil(sec / 60)} мин.`
                                : `${sec} сек.`;
                    }

                    notification.open(
                        `Слишком много попыток, попробуйте через ${text}`,
                    );
                    break;
                }

                default:
                    notification.open(message);
            }
        } finally {
            // Убираем залипание
            setButtonLoading(this.#loginSubmitBtn, false);
        }
    }

    // Подтверждение формы регистрации
    async #register(e) {
        e.preventDefault();

        // Скрываем ошибки если они есть
        this.#hideErrors();

        const form = this.#registerForm;
        const nameInput = form.elements["name"];
        const emailInput = form.elements["email"];
        const passInput = form.elements["password"];
        const confirmPassInput = form.elements["confirm_password"];

        // Проверяем валидность формы и ее инпутов
        if (!form.checkValidity()) {
            if (!nameInput.checkValidity()) {
                this.#showInputError(
                    nameInput,
                    "Длина имени не должна превышать 100 символов",
                );
            }

            if (!emailInput.checkValidity()) {
                this.#showInputError(emailInput, "Введите корректный email");
            }

            if (!passInput.checkValidity()) {
                this.#showInputError(
                    passInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            if (!confirmPassInput.checkValidity()) {
                this.#showInputError(
                    confirmPassInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            return;
        }

        // Получаем данные из инпутов
        const name = nameInput.value;
        const email = emailInput.value.trim();
        const password = passInput.value;
        const confirmPassword = confirmPassInput.value;

        // Проверяем совпаденгие паролей
        if (password !== confirmPassword) {
            this.#showInputError(
                confirmPassInput,
                "Пароли не совпадают, проверьте пароль",
            );
            return;
        }

        // Отправляем запрос на регистрацию
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(this.#registerSubmitBtn, true);

            await register(email, password, name);

            window.location.href = "/account";

            this.close();
        } catch (e) {
            console.error("[auth-modal] Не удалось выполнить регистрацию", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            });

            const message = getErrorMessage(e.code, e.status);

            switch (e.code) {
                case "NAME_REQUIRED":
                case "NAME_TOO_LONG":
                case "NAME_INVALID_CHARS":
                    this.#showInputError(nameInput, message);
                    break;

                case "EMAIL_REQUIRED":
                case "EMAIL_INVALID":
                case "EMAIL_TOO_LONG":
                case "EMAIL_TAKEN":
                    this.#showInputError(emailInput, message);
                    break;

                case "PASSWORD_REQUIRED":
                case "PASSWORD_TOO_SHORT":
                case "PASSWORD_TOO_LONG":
                case "PASSWORD_INVALID_CHARS":
                    this.#showInputError(passInput, message);
                    break;

                case "PASSWORD_MISMATCH":
                    this.#showInputError(confirmPassInput, message);
                    break;

                default:
                    notification.open(message);
            }
        } finally {
            // Убираем залипание
            setButtonLoading(this.#registerSubmitBtn, false);
        }
    }

    // Забыли пароль
    async #forgotPass() {
        // Скрываем ошибки если они есть
        this.#hideErrors();

        const emailInput = this.#loginForm.elements["email"];
        const email = emailInput.value.trim();

        if (!email) {
            this.#showInputError(
                emailInput,
                "Введите email для восстановления пароля",
            );
            return;
        }

        if (!emailInput.checkValidity()) {
            this.#showInputError(
                emailInput,
                "Введите корректный email для восстановления пароля",
            );
            return;
        }

        // Отправляем запрос на сброс пароля
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(this.#forgotPassBtn, true);

            await forgotPassword(email);

            notification.open(
                "Вам на почту отправлено письмо для сброса пароля, перейдите по ссылке в письме, чтобы создать новый пароль",
            );
        } catch (e) {
            console.error(
                "[auth-modal] Не удалось отправить письмо для сброса пароля",
                {
                    message: e.message,
                    code: e.code,
                    status: e.status,
                    payload: e.payload,
                },
            );

            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Убираем залипание
            setButtonLoading(this.#forgotPassBtn, false);
        }
    }

    // Показ ошибки под конкретным input-ом
    #showInputError(inputEl, errorMessage) {
        const inputElWrapper = inputEl.closest(
            ".registration_modal_input_back",
        );
        if (!inputElWrapper) return;

        const inputErrorEl = inputElWrapper.nextElementSibling;
        if (!inputErrorEl) return;

        const inputErrorTextEl =
            inputErrorEl.querySelector(".error_modal_text");
        if (!inputErrorTextEl) return;

        inputErrorTextEl.textContent = errorMessage;
        inputErrorEl.classList.remove("form_error_hidden");

        // Навешиваем обработчик для закрытия ошибки при исправлении в input-е
        inputEl.addEventListener(
            "input",
            () => {
                inputErrorEl.classList.add("form_error_hidden");
                inputErrorTextEl.textContent = "";
            },
            { once: true },
        );
    }

    // Скрытие всех ошибок на формах
    #hideErrors() {
        const authErrors = this.#authModal.querySelectorAll(".form_error");

        authErrors.forEach((errorEl) => {
            errorEl.querySelector(".error_modal_text").textContent = "";
            errorEl.classList.add("form_error_hidden");
        });
    }
}

export const authModal = new AuthModal();
