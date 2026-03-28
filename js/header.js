// Файл для общей инициализации хедера
// Инициализируется поисковик и обработчик кнопки "выйти" для залогиненого пользователя

// TODO: доделать это файл: сюда перенести все по поиску и сделать обработчик кнопки выхода с модалкой подтверждения

function initHeader() {}

// Автоинициализация при загрузке
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeader(), {
        once: true,
    });
} else {
    initHeader();
}
