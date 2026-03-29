// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../../utils.js";

const AUTH_BASE_URL = "/api/auth";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestAuth(path, options = {}) {
    return requestApi(AUTH_BASE_URL, path, options);
}

// Функция для входа в аккаунт
// Отправляет запрос POST /api/auth/login
export function login(email, password) {
    return requestAuth("/login", {
        method: "POST",
        body: JSON.stringify({
            email: email,
            password: password,
        }),
    });
}

// Функция для выхода из аккаунта
// Отправляет запрос POST /api/auth/login
export function logout() {
    return requestAuth("/logout", {
        method: "POST",
    });
}

// Функция для регистрации
// Отправляет запрос POST /api/auth/register
export function register(email, password, name) {
    return requestAuth("/register", {
        method: "POST",
        body: JSON.stringify({
            email: email,
            password: password,
            name: name,
        }),
    });
}

// Функция для старта сброса пароля
// Отправляет запрос POST /api/auth/password/forgot
export function forgotPassword(email) {
    return requestAuth("/password/forgot", {
        method: "POST",
        body: JSON.stringify({
            email: email,
        }),
    });
}

// Функция для сброса пароля
// Отправляет на бэк оба введеных пароля для дополнительной проверки на совпадение
// Отправляет запрос POST /api/auth/password/reset
export function resetPassword(token, password, passwordConfirmation) {
    return requestAuth("/password/reset", {
        method: "POST",
        body: JSON.stringify({
            token: token,
            password: password,
            password_confirmation: passwordConfirmation,
        }),
    });
}
