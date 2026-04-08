// Класс для управлением универсальной модалкой подтверждения
export class ConfirmationModal {
    constructor(id = "confirmation-modal") {
        const el = document.getElementById(id);
        if (!el) throw new Error(`[ConfirmationModal] Not found: ${id}`);

        // При присвоении в конструкторе поле сразу создается для всего класса
        this.el = el;
        this.overlay = el.querySelector("[data-modal-overlay]");
        this.closeEls = el.querySelectorAll("[data-modal-close]");
        this.titleEl = el.querySelector("[data-modal-title]");
        this.messageEl = el.querySelector("[data-modal-message]");
        this.warningEl = el.querySelector("[data-modal-warning]");
        this.cancelBtn = el.querySelector("[data-modal-cancel]");
        this.confirmBtn = el.querySelector("[data-modal-confirm]");

        if (
            !this.confirmBtn ||
            !this.cancelBtn ||
            !this.titleEl ||
            !this.messageEl
        ) {
            throw new Error(
                "[ConfirmationModal] Required elements are missing in markup",
            );
        }

        // Флаги
        this._isOpen = false;
        this._onConfirm = null; // действие поле подтверждения (внешняя функция)
        this._previousActiveEl = null; // элемент, который был в фокусе, нужен чтобы вернуть фокус назад при закрытии модалки

        // Привязываем this к текущему экземпляру класса через функцию-обёртку (через .bind(this))
        // Это нужно чтобы при навешивании обработчиков с этой функцией контекст this не терялся
        this._handleKeydown = this._handleKeydown.bind(this);
        this._handleConfirmClick = this._handleConfirmClick.bind(this);

        // Закрытие по крестику/кнопкам с data-modal-close
        this.closeEls.forEach((btn) =>
            btn.addEventListener("click", () => this.close()),
        );

        // Закрытие по клику на overlay (если он есть)
        if (this.overlay) {
            this.overlay.addEventListener("click", () => this.close());
        }

        // Подтверждение
        this.confirmBtn.addEventListener("click", this._handleConfirmClick);
    }

    // Метод октрытия модалки, на входе параметры для отображения модалки
    open({
        title = "Подтверждение действия",
        message = "Вы уверены, что хотите выполнить это действие?",
        warning = "", // дополнительная строчка под тайтлом модалки
        confirmText = "Подтвердить",
        cancelText = "Отмена",
        onConfirm, // внешняя функция, которая будет выполнятся при подтверждении модалки (без аргументов, может быть async)
    }) {
        // Определяем активный до появления модалки элемент
        this._previousActiveEl =
            document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;

        this.titleEl.textContent = String(title);
        this.messageEl.textContent = String(message);

        // Добавляем доп. строчку предупреждения если она есть, иначе скрываем
        const warningText = String(warning ?? "").trim();
        if (this.warningEl) {
            this.warningEl.textContent = warningText;
            this.warningEl.classList.toggle("hidden", warningText === "");
        }

        this.confirmBtn.textContent = String(confirmText);
        this.cancelBtn.textContent = String(cancelText);

        this._onConfirm = typeof onConfirm === "function" ? onConfirm : null;

        // Показываем модалку и ставим open флаг
        this.el.classList.remove("hidden");
        this._isOpen = true;

        // Ставим фокус на cancel модалки
        this.cancelBtn.focus();

        // Вешаем обработчик всех клавиш на функцию
        document.addEventListener("keydown", this._handleKeydown);
    }

    // Метод закрытия модалки
    close() {
        if (!this._isOpen) return;

        this.el.classList.add("hidden");
        this._isOpen = false;
        this._onConfirm = null;

        document.removeEventListener("keydown", this._handleKeydown);

        // Возвращаем фокус на тот элемент, на котором он был до окрытия модалки
        if (
            this._previousActiveEl &&
            document.contains(this._previousActiveEl)
        ) {
            this._previousActiveEl.focus();
        }
        this._previousActiveEl = null;
    }

    // Метод для обработки нажатия
    async _handleConfirmClick() {
        if (!this._onConfirm) {
            this.close();
            return;
        }

        // Минимальный “loading” без оверкилла: блокируем кнопки
        this.confirmBtn.disabled = true;
        this.cancelBtn.disabled = true;

        try {
            await this._onConfirm();
            this.close();
        } catch (e) {
            // При ошибке: разблокируем и оставляем модалку открытой
            this.confirmBtn.disabled = false;
            this.cancelBtn.disabled = false;
            throw e;
        }
    }

    // Функция для нахождения интерактивных элементов модалки
    // "_" - условное обозначение для внутреннего метода
    _getFocusableElements() {
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
        return Array.from(this.el.querySelectorAll(selectors)) // подходит под массив selectors
            .filter((node) => node instanceof HTMLElement) // является html элементом
            .filter((node) => !node.classList.contains("hidden")) // не имеет hidden класса
            .filter((node) => node.offsetParent !== null); // не display:none
    }

    // Функция для обработки нажати клавиш Escape и Tab
    _handleKeydown(e) {
        if (!this._isOpen) return;

        if (e.key === "Escape") {
            e.preventDefault();
            this.close();
            return;
        }

        if (e.key !== "Tab") return;

        // Focus trap: Tab/Shift+Tab будут переключаться только внутри модалки
        // Это нужно чтобы при Tab фокус не перешел на элемент под/за модалкой
        const focusables = this._getFocusableElements();
        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        const active = document.activeElement;

        // Ограничиваем переключение фокуса между first и last элементами модалки
        if (e.shiftKey) {
            if (active === first || !this.el.contains(active)) {
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
}
