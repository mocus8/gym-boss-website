import { getErrorMessage, setButtonLoading } from "../../utils.js";
import { updateProfile, updatePassword, deleteAccount } from "./account.api.js";
import { resendVerificationEmail } from "../auth/auth.api.js";
import { ConfirmationModal } from "../../ui/confirmation-modal.js";
import { notification } from "../../ui/notification.js";

// Функция для инициализации всего js для страницы личного кабинета
function initAccountPage() {
    const changeNameForm = document.getElementById("change-name-form");
    const nameInput = changeNameForm.elements["name"];
    const changeNameFormSubmitBtn = changeNameForm.querySelector(
        'button[type="submit"]',
    );

    const resendVerificationEmailBtn = document.getElementById(
        "resend-verification-email-btn",
    );

    const changePassForm = document.getElementById("change-pass-form");
    const currentPassInput = changePassForm.elements["current_password"];
    const newPassInput = changePassForm.elements["new_password"];
    const confirmPassInput = changePassForm.elements["confirm_password"];
    const changePassFormSubmitBtn = changePassForm.querySelector(
        'button[type="submit"]',
    );

    const deleteAccountBtn = document.getElementById("delete-account-btn");

    if (
        !changeNameForm ||
        !nameInput ||
        !changeNameFormSubmitBtn ||
        !changePassForm ||
        !currentPassInput ||
        !newPassInput ||
        !confirmPassInput ||
        !changePassFormSubmitBtn ||
        !deleteAccountBtn
    ) {
        console.error(
            "[account.page.js] Не найдены необходимые элементы для инициализации поиска по товарам",
        );

        return;
    }

    // Обработчик подтверждения формы смены имени
    changeNameForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        hideErrors(changeNameForm);

        if (!nameInput.checkValidity()) {
            showInputError(nameInput, "Имя содержит недопустимые символы");
            return;
        }

        // Получаем введеное в input имя
        const name = nameInput.value.trim();

        // Отправляем запрос на смену данных профиля (имени)
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(changeNameFormSubmitBtn, true);

            await updateProfile(name);

            window.location.reload();
        } catch (e) {
            console.error("[account.page.js] Не удалось сменить имя", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            });

            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Убираем залипание
            setButtonLoading(changeNameFormSubmitBtn, false);
        }
    });

    // Если кнопка есть на странице, вешаем на нее бработчик для получения письма для подтверждения почты
    if (resendVerificationEmailBtn) {
        resendVerificationEmailBtn.addEventListener("click", async () => {
            // Отправляем запрос отправку письма
            try {
                // Добавляем залипание на кнопку
                setButtonLoading(resendVerificationEmailBtn, true);

                await resendVerificationEmail();

                notification.open(
                    "Вам на электронную почту будет отправлен email для подтверждения",
                );
            } catch (e) {
                console.error(
                    "[account.page.js] Не удалось отправить email для подтверждения",
                    {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload,
                    },
                );

                // Если ограничение по кол-ву писем
                if (e.code === "EMAIL_RATE_LIMIT") {
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
                        `Повторная отправка письма будет доступна через ${text}`,
                    );
                    return;
                }

                const message = getErrorMessage(e.code, e.status);
                notification.open(message);
            } finally {
                // Убираем залипание
                setButtonLoading(resendVerificationEmailBtn, false);
            }
        });
    }

    // Обработчик подтверждения формы смены пароля
    changePassForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        hideErrors(changePassForm);

        if (!changePassForm.checkValidity()) {
            if (!currentPassInput.checkValidity()) {
                showInputError(
                    currentPassInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            if (!newPassInput.checkValidity()) {
                showInputError(
                    newPassInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            if (!confirmPassInput.checkValidity()) {
                showInputError(
                    confirmPassInput,
                    "Пароль должен быть от 8 до 64 символов",
                );
            }

            return;
        }

        // Получаем пароли из инпутов
        const currentPass = currentPassInput.value;
        const newPassword = newPassInput.value;
        const confirmPassword = confirmPassInput.value;

        // Проверяем совпадение паролей
        if (newPassword !== confirmPassword) {
            showInputError(
                confirmPassInput,
                "Пароли не совпадают, проверьте пароль",
            );
            return;
        }

        // Отправляем запрос на смену пароля
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(changePassFormSubmitBtn, true);

            await updatePassword(currentPass, newPassword);

            changePassForm.reset();

            notification.open("Пароль успешно изменен");
        } catch (e) {
            console.error("[auth-modal] Не удалось сменить пароль", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            });

            const message = getErrorMessage(e.code, e.status);

            switch (e.code) {
                case "PASSWORD_REQUIRED":
                case "WRONG_PASSWORD":
                    showInputError(currentPassInput, message);
                    break;

                case "PASSWORD_INVALID_CHARS":
                case "SAME_PASSWORD":
                    showInputError(newPassInput, message);
                    break;

                default:
                    notification.open(message);
            }
        } finally {
            // Убираем залипание
            setButtonLoading(changePassFormSubmitBtn, false);
        }
    });

    // Создаем модалку подтверждения
    const confirmModal = new ConfirmationModal("confirmation-modal");

    // Обработчик кнопки удаления аккаунта
    deleteAccountBtn.addEventListener("click", async () => {
        confirmModal.open({
            title: "Удаление аккаунта",
            message: "Вы уверены что хотите удалить аккаунт?",
            warning: "Это действие необратимо.",
            confirmText: "Удалить",
            cancelText: "Отмена",
            onConfirm: async () => {
                try {
                    await deleteAccount();
                    window.location.href = "/";
                } catch (e) {
                    // Логирование в консоль с полным контекстом
                    console.error("[logout-btn] Не удалось удалить аккаунт", {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload,
                    });

                    const message = getErrorMessage(e.code, e.status);
                    notification.open(message);

                    // Пробрасываем ошибку, при этом модалка не закрывается
                    throw e;
                }
            },
        });
    });

    // Функция для скрытия всех ошибок для конкретной формы
    function hideErrors(formEl) {
        const authErrors = formEl.querySelectorAll(".form_error");

        authErrors.forEach((errorEl) => {
            errorEl.querySelector(".error_modal_text").textContent = "";
            errorEl.classList.add("form_error_hidden");
        });
    }

    // Функция для показа ошибки под конкретным input-ом
    function showInputError(inputEl, errorMessage) {
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
}

// Автоинициализация при загрузке
if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        () => {
            initAccountPage();
        },
        {
            once: true,
        },
    );
} else {
    initAccountPage();
}
