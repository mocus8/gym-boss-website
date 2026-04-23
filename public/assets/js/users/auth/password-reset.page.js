// Импортируем js (подключение этих js в других файлах не требуется)
import { resetPassword } from "./auth.api.js";
import {
    getErrorMessage,
    setButtonLoading,
    setButtonDisable,
} from "../../utils.js";
import { notification } from "../../ui/notification.js";

// Функция инициализации страницы, навешивает обработчики
function initPasswordResetPage(resetPassForm, resetSuccessEl) {
    const passInput = resetPassForm.elements["password"];
    const confirmPassInput = resetPassForm.elements["confirm_password"];
    const passErrorEl = resetPassForm.querySelector("#password-reset-error");
    const passErrorText = passErrorEl.querySelector("[data-error-text]");
    const submitButton = resetPassForm.querySelector('button[type="submit"]');

    if (!passInput || !confirmPassInput || !passErrorEl || !submitButton) {
        return;
    }

    // Функция для показа ошибки пароля
    function showPassError(text) {
        passInput.setAttribute("aria-invalid", "true");
        confirmPassInput.setAttribute("aria-invalid", "true");

        passErrorText.textContent = text;
        passErrorEl.classList.remove("is-hidden");
    }

    // Функция скрытия ошибки пароля
    function hidePassError() {
        passInput.setAttribute("aria-invalid", "false");
        confirmPassInput.setAttribute("aria-invalid", "false");

        passErrorText.textContent = "";
        passErrorEl.classList.add("is-hidden");
    }

    resetPassForm.addEventListener("submit", async (event) => {
        // Отменяем стандартную отправку формы
        event.preventDefault();

        // Скрываем ошибки если они есть
        hidePassError();

        // Получаем токен из скрытого поля
        const token = resetPassForm.elements["token"].value;
        if (!token) {
            notification.open(
                "Ошибка: ссылка недействительна. Перейдите по ссылке из письма заново.",
            );
            setButtonDisable(submitButton, true);
            return;
        }

        // Получаем пароли из полей формы
        const password = resetPassForm.elements["password"].value;
        const confirmPassword =
            resetPassForm.elements["confirm_password"].value;

        // Проверяем пароли
        if (!password || !confirmPassword) {
            showPassError("Заполните оба поля пароля");
            return;
        }

        if (!resetPassForm.checkValidity()) {
            showPassError("Пароль должен быть от 8 до 64 символов");
            return;
        }

        if (password !== confirmPassword) {
            showPassError("Пароли не совпадают");
            return;
        }

        // Пытаемся поменять пароль
        try {
            // Добавляем залипание на кнопку
            setButtonLoading(submitButton, true);

            // Отправляем запрос
            await resetPassword(token, password, confirmPassword);

            // Показываем успех
            resetPassForm.hidden = true;
            resetSuccessEl.hidden = false;
        } catch (e) {
            console.error("[password-reset-page] Не удалось сменить пароль", {
                message: e.message,
                code: e.code,
                status: e.status,
                payload: e.payload,
            });

            const message = getErrorMessage(e.code, e.status);
            notification.open(message);
        } finally {
            // Убираем залипание
            setButtonLoading(submitButton, false);
        }
    });

    // Навешиваем обработчики для скрытия ошибки пароля
    passInput.addEventListener("input", hidePassError);
    confirmPassInput.addEventListener("input", hidePassError);
}

// Определяем основные элементы
const resetPassForm = document.getElementById("reset-pass-form");
const resetSuccessEl = document.getElementById("reset-success");

// Если основные элементы нашлись - навешиваем обработчики
if (!resetPassForm || !resetSuccessEl) {
    console.error(
        "[password-reset-page] Не найдены reset-pass-form или reset-success",
    );
} else {
    initPasswordResetPage(resetPassForm, resetSuccessEl);
}
