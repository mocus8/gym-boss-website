// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../../utils.js";

const AUTH_BASE_URL = "/api/auth";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestAuth(path, options = {}) {
    return requestApi(AUTH_BASE_URL, path, options);
}

// Функция для сброса пароля
// Отправляет на бэк оба введеных пароля для дополнительной проверки на совпадение
// Отправляет запрос POST /api/auth/password/reset
export function resetPassword({ token, password, passwordConfirmation }) {
    return requestAuth("/password/reset", {
        method: "POST",
        body: JSON.stringify({
            token: token,
            password: password,
            password_confirmation: passwordConfirmation,
        }),
    });
}
