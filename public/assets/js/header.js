// Файл для общей инициализации хедера
// Инициализируется поисковик и обработчик кнопки "выйти" для залогиненого пользователя

import { debounce, getErrorMessage } from "./utils.js";
import { searchProducts } from "./products/products.api.js";
import { logout } from "./users/auth/auth.api.js";
import { ConfirmationModal } from "./ui/confirmation-modal.js";
import { notification } from "./ui/notification.js";

// Инициализация дропдауна аккаунта
function initAccountDropdown() {
    const accountBlock = document.querySelector(
        ".site-header__account-wrapper",
    );
    const accountMenuTrigger = document.getElementById("account-menu-trigger");
    const accountMenu = document.getElementById("account-menu");
    if (!accountBlock || !accountMenuTrigger || !accountMenu) return;

    // Обработчик клика на триггер
    accountMenuTrigger.addEventListener("click", () => {
        const isOpened =
            accountMenuTrigger.getAttribute("aria-expanded") === "true";

        // Переключаем состояние меню и aria атрибута
        accountMenu.classList.toggle("is-hidden", isOpened);
        accountMenuTrigger.setAttribute("aria-expanded", String(!isOpened));
    });

    // Обработчик закрытия по клику escape
    document.addEventListener("keydown", (e) => {
        const isOpened =
            accountMenuTrigger.getAttribute("aria-expanded") === "true";

        if (!isOpened || e.key !== "Escape") return;

        hideMenu();

        // Возвращаем фокус на триггер
        accountMenuTrigger.focus();
    });

    // Обработчик закрытия при клике на фон
    document.addEventListener("click", (e) => {
        const isOpened =
            accountMenuTrigger.getAttribute("aria-expanded") === "true";

        if (accountBlock.contains(e.target) || !isOpened) return;

        hideMenu();
    });

    // Функция для скрытия меню
    function hideMenu() {
        // Скрываем меню и добавляем aria атрибут о том что меню закрыто
        accountMenu.classList.add("is-hidden");
        accountMenuTrigger.setAttribute("aria-expanded", "false");
    }
}

// Инициализация поиска
function initHeaderSearch() {
    const searchBlock = document.getElementById("header-search");
    const searchInput = document.getElementById("header-search-input");
    const searchCancelBtn = document.getElementById(
        "header-search-cancel-button",
    );
    const searchResultsContainer = document.getElementById(
        "search-results-container",
    );

    if (
        !searchBlock ||
        !searchInput ||
        !searchCancelBtn ||
        !searchResultsContainer
    ) {
        console.error(
            "[header-search] Не найдены необходимые элементы для инициализации поиска по товарам",
        );

        return;
    }

    // Отменяем стандартную отправку формы
    searchBlock.addEventListener("submit", (event) => {
        event.preventDefault();
    });

    // Обработчик ввода в input-е
    // Если что то есть - даем результаты, если пусто - скрываем их
    searchInput.addEventListener("input", function () {
        // Получаем введеный в input текст
        const query = this.value.trim();

        // Если ввод пустой - очищаем все и выходим
        if (!query) {
            clearSearchBlock();
            return;
        }

        // Очищаем результат, на случай если там что то осталось
        searchResultsContainer.innerHTML = "";

        // Создаем элемент сообщения для результатов
        const searchStatusEl = document.createElement("p");
        searchStatusEl.classList.add("site-header__search-status");
        searchStatusEl.textContent = "Поиск...";

        // Вставляем его в контейнер с результатами
        searchResultsContainer.appendChild(searchStatusEl);

        // Показываем контейнер с результатами и кнопку закрытия поисковика
        searchCancelBtn.classList.remove("is-hidden");
        searchResultsContainer.classList.remove("is-hidden");

        // Вызываем функцию поиска и рендера результатов
        // Функция с debounce оберткой для ограничения запросов на уровне фронта
        debouncedSearchAndRenderProducts(query, searchStatusEl);
    });

    // Обработчик клика на input
    // Показываем результаты, если поле не пустое
    searchInput.addEventListener("click", function () {
        if (this.value.trim() === "") return;

        searchResultsContainer.classList.remove("is-hidden");
    });

    // Обработчик клика на крестик
    searchCancelBtn.addEventListener("click", clearSearchBlock);

    // Обработчик клика вне поисковика
    window.addEventListener("click", function (e) {
        // Если клик был внутри поисковика - выходим
        if (searchBlock.contains(e.target)) return;

        searchResultsContainer.classList.add("is-hidden");
    });

    // Функция для очистки всего блока поиска
    function clearSearchBlock() {
        debouncedSearchAndRenderProducts.cancel(); // отменяем отложенный вызов если он есть
        searchInput.value = "";
        searchCancelBtn.classList.add("is-hidden");
        searchResultsContainer.classList.add("is-hidden");
        searchResultsContainer.innerHTML = "";
    }

    // Функция поиска и рендера результатов с debounced оберткой (переменная, которая хранит функцию)
    const debouncedSearchAndRenderProducts = debounce(
        async (query, searchStatusEl) => {
            try {
                const foundProducts = await searchProducts(query);

                if (foundProducts.length === 0) {
                    searchStatusEl.textContent = "Ничего не найдено";
                    return;
                }

                // Очищаем содержимое контейнера с результатами
                searchResultsContainer.innerHTML = "";

                // Создаем список-обертку для результатов
                const resultList = document.createElement("ul");
                resultList.classList.add("list-reset");
                resultList.classList.add("site-header__search-results-list");
                searchResultsContainer.appendChild(resultList);

                foundProducts.forEach((product) => {
                    const resultItem = createResultItem(product);
                    resultList.appendChild(resultItem);
                });
            } catch (e) {
                // Логирование в консоль с полным контекстом
                console.error("[product-search] Ошибка при поиске товара", {
                    message: e.message,
                    code: e.code,
                    status: e.status,
                    payload: e.payload,
                    query,
                });

                // Отображение ошибки в поле поисковика
                const message = getErrorMessage(e.code, e.status);
                searchStatusEl.textContent = message;
            }
        },
        300,
    );

    // Функция для создания элементов списка результата поиска
    function createResultItem(product) {
        const itemWrapper = document.createElement("li");
        itemWrapper.classList.add("site-header__search-results-item");

        const linkWrapper = document.createElement("a");
        linkWrapper.classList.add("link-shell");
        linkWrapper.href = `/products/${encodeURIComponent(product.slug)}`;
        itemWrapper.appendChild(linkWrapper);

        const productCard = document.createElement("div");
        productCard.classList.add("product-card");
        productCard.classList.add("shape-cut-corners");
        linkWrapper.appendChild(productCard);

        const productImg = document.createElement("img");
        productImg.classList.add("img-full");
        productImg.classList.add("shape-cut-corners");
        productImg.src = product.image_path;
        productImg.alt = product.name;
        productCard.appendChild(productImg);

        const productName = document.createElement("h3");
        productName.textContent = product.name;
        productCard.appendChild(productName);

        const productPrice = document.createElement("p");
        productPrice.classList.add("product-card__price");
        productPrice.textContent = `${product.price} ₽`;
        productCard.appendChild(productPrice);

        return itemWrapper;
    }
}

// Функция для инициализации бработчика кнопки логаута
function initLogoutBtn() {
    const logoutBtn = document.querySelector(
        '[data-modal-open="logout-modal"]',
    );
    if (!logoutBtn) return;

    // Создаем модалку подтверждения
    const confirmModal = new ConfirmationModal("confirmation-modal");

    // Настраиваем открытие модалки подтверждения при клике на кнопку выхода из аккаунта
    logoutBtn.addEventListener("click", () => {
        confirmModal.open({
            title: "Выход из аккаунта",
            message: "Вы уверены что хотите выйти из аккаунта?",
            confirmText: "Выйти",
            cancelText: "Остаться",
            onConfirm: async () => {
                try {
                    await logout();
                    window.location.reload();
                } catch (e) {
                    // Логирование в консоль с полным контекстом
                    console.error("[logout-btn] Не удалось выйти из аккаунта", {
                        message: e.message,
                        code: e.code,
                        status: e.status,
                        payload: e.payload, // data
                    });

                    // Показ ошибки пользователю
                    const message = getErrorMessage(e.code, e.status);
                    notification.open(message);

                    // Пробрасываем ошибку, при этом модалка не закрывается
                    throw e;
                }
            },
        });
    });
}

// Автоинициализация при загрузке
if (document.readyState === "loading") {
    document.addEventListener(
        "DOMContentLoaded",
        () => {
            initAccountDropdown();
            initHeaderSearch();
            initLogoutBtn();
        },
        {
            once: true,
        },
    );
} else {
    initAccountDropdown();
    initHeaderSearch();
    initLogoutBtn();
}
