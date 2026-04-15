// крутой класс через ES6 для управленя уведомлением с текстом
class Notification {
    // Константа для времени закрытия уведомления
    // static поля - общие для ВСЕХ объектов класса
    static #CLOSE_TIME = 5000;

    #notification = null;
    #closeTimer = null;
    #text = null;
    #progress = null;
    #closeBtn = null;

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
    }

    #init() {
        this.#notification = document.getElementById("notification");
        this.#text = document.getElementById("notification-text");
        this.#progress = document.getElementById("notification-progress-fill");
        this.#closeBtn = document.getElementById("notification-close-btn");

        if (
            !this.#notification ||
            !this.#text ||
            !this.#progress ||
            !this.#closeBtn
        ) {
            console.error("[Notification] Необходимые элементы не найдены");
            return;
        }

        this.#closeBtn.addEventListener("click", () => this.close());
    }

    open(innerText) {
        if (!this.#notification) return;

        this.close();

        this.#text.textContent = innerText;
        this.#notification.classList.add("is-open");

        // Сброс и запуск анимации прогресса
        this.#progress.classList.remove("shrinking");
        void this.#progress.offsetWidth; // Принудительный reflow, гарантируем что анимация перезапуститься
        this.#progress.classList.add("shrinking");

        // Таймер автоскрытия
        this.#closeTimer = setTimeout(
            () => this.close(),
            Notification.#CLOSE_TIME,
        );
    }

    close() {
        if (!this.#notification) return;

        this.#notification.classList.remove("is-open");
        this.#progress.classList.remove("shrinking");

        if (this.#text) this.#text.textContent = "";

        if (this.#closeTimer !== null) clearTimeout(this.#closeTimer);
        this.#closeTimer = null;
    }
}

export const notification = new Notification();

// Похожая модалка, но через IIFE
// const HeaderModal = (function() {
//     let closeTimer = null;
//     let modal, text, progress, closeBtn;

//     function init() {
//         modal = document.getElementById('header-modal');
//         text = document.getElementById('header-modal-text');
//         progress = document.getElementById('header-modal-progress-fill');
//         closeBtn = document.getElementById('header-modal-close');

//         if (!modal || !text || !progress || !closeBtn) {
//             console.error('Modal elements not found');
//             return;
//         }

//         closeBtn.addEventListener('click', close);
//     }

//     function open(innerText) {
//         if (!modal) return;

//         close();

//         text.textContent = innerText;
//         modal.classList.remove("hidden");

//         progress.classList.remove("shrinking");
//         // Принудительный reflow, гарантируем что анимация перезапуститься
//         void progress.offsetWidth;
//         progress.classList.add("shrinking");

//        closeTimer = setTimeout(close, 5000);
//     }

//     function close() {
//         if (!modal) return;

//         modal.classList.add("hidden");
//         progress.classList.remove("shrinking");

//         if(text) text.textContent = '';

//         if (closeTimer) clearTimeout(closeTimer);

//         closeTimer = null;
//     }

//     // Автоинициализация при загрузке
//     if (document.readyState === 'loading') {
//         document.addEventListener('DOMContentLoaded', init);
//     } else {
//         init();
//     }

//     // возвращаем функции для открытия и закрытия
//     return { open, close };
// })(); // () на конце выполняет сразу (для всех функций), и это исользуется все последующее разы
