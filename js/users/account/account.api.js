// Файл для взаимодействия с api-контроллером: отправкой запросов и получения ответов.

// Импортируем общую функция для взаимодвействия с api
import { requestApi } from "../../utils.js";

const ACCOUNT_BASE_URL = "/api/account";

// Оборачиваем общую функцию (добавляем базовый путь)
async function requestAccount(path, options = {}) {
    return requestApi(ACCOUNT_BASE_URL, path, options);
}

// Функция для смены инфы профиля (имени)
// Отправляет запрос POST /api/account/profile
export function updateProfile(newName) {
    return requestAccount("/profile", {
        method: "POST",
        body: JSON.stringify({
            name: newName,
        }),
    });
}

// Функция для смены пароля на основании текущего
// Отправляет запрос POST /api/account/password
export function updatePassword(currentPassword, newPassword) {
    return requestAccount("/password", {
        method: "POST",
        body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword,
        }),
    });
}

// Функция для удаления аккаунта
// Отправляет запрос DELETE /api/account/delete
export function deleteAccount() {
    return requestAccount("", {
        method: "DELETE",
    });
}
