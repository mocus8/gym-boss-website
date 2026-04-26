import { notification } from "./notification.js";

// Показываем уведомление, если есть сообщение
document.addEventListener("DOMContentLoaded", () => {
    const node = document.getElementById("server-flash");
    if (!node) return;

    let payload;
    try {
        payload = JSON.parse(node.textContent);
    } catch {
        return;
    }

    if (!payload || !payload.message) return;

    notification.open(payload.message);
});
