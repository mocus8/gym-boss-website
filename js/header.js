// Файл для общей инициализации хедера
// Инициализируется поисковик и обработчик кнопки "выйти" для залогиненого пользователя

import { debounce, getErrorMessage } from "./utils.js";
import { searchProducts } from "./products/products.api.js";
import { logout } from "./users/auth/auth.api";
import { ConfirmationModal } from "./ui/confirmation-modal.js";
import { notification } from "./ui/notification.js";

// TODO: доделать это файл:
// сюда перенести все по поиску
// сделать обработчик кнопки выхода с модалкой подтверждения
// проверить старый modals.js

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
        const searchMessageEl = document.createElement("div");
        searchMessageEl.classList.add("search_empty");
        searchMessageEl.textContent = "Поиск...";

        // Вставляем его в контейнер с результатами
        searchResultsContainer.appendChild(searchMessageEl);

        // Показываем контейнер с результатами и кнопку закрытия поисковика
        searchCancelBtn.classList.remove("hidden");
        searchResultsContainer.classList.remove("hidden");

        // Вызываем функцию поиска и рендера результатов
        // Функция с debounce оберткой для ограничения запросов на уровне фронта
        debouncedSearchAndRenderProducts(query, searchMessageEl);
    });

    // Обработчик клика на input
    // Показываем результаты, если поле не пустое
    searchInput.addEventListener("click", function () {
        if (this.value.trim() === "") return;

        searchResultsContainer.classList.remove("hidden");
    });

    // Обработчик клика на крестик
    searchCancelBtn.addEventListener("click", clearSearchBlock);

    // Обработчик клика вне поисковика
    window.addEventListener("click", function (e) {
        // Если клик был внутри поисковика - выходим
        if (searchBlock.contains(e.target)) return;

        searchResultsContainer.classList.add("hidden");
    });

    // Функция для очистки всего блока поиска
    function clearSearchBlock() {
        debouncedSearchAndRenderProducts.cancel(); // отменяем отложенный вызов если он есть
        searchInput.value = "";
        searchCancelBtn.classList.add("hidden");
        searchResultsContainer.classList.add("hidden");
        searchResultsContainer.innerHTML = "";
    }

    // Функция поиска и рендера результатов с debounced оберткой (переменная, которая хранит функцию)
    const debouncedSearchAndRenderProducts = debounce(
        async (query, searchMessageEl) => {
            try {
                const foundProducts = await searchProducts(query);

                if (foundProducts.length === 0) {
                    searchMessageEl.textContent = "Ничего не найдено";
                    return;
                }

                // Очищаем содержимое контейнера с результатами и заполняем его товарами
                searchResultsContainer.innerHTML = "";

                foundProducts.forEach((product) => {
                    const productCard = createProductCard(product);
                    searchResultsContainer.appendChild(productCard);
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
                searchMessageEl.textContent = message;
            }
        },
        300,
    );

    // Функция для создания карточки товара
    function createProductCard(product) {
        // Создаем ссылку-обертку вокруг карточки
        const linkWrapper = document.createElement("a");
        linkWrapper.href = `/product/${product.slug}`;

        const productWrapper = document.createElement("div");
        productWrapper.classList.add("product");
        linkWrapper.appendChild(productWrapper);

        const productClickEl = document.createElement("div");
        productClickEl.classList.add("product_click");
        productWrapper.appendChild(productClickEl);

        const productImg = document.createElement("img");
        productImg.classList.add("product_img_1");
        productImg.src = product.image_path;
        productClickEl.appendChild(productImg);

        const productNameEl = document.createElement("div");
        productNameEl.classList.add("product_name_1");
        productNameEl.textContent = product.name;
        productClickEl.appendChild(productNameEl);

        const productPriceEl = document.createElement("div");
        productPriceEl.classList.add("product_price_1");
        productPriceEl.textContent = `${product.price} ₽`;
        productClickEl.appendChild(productPriceEl);

        return linkWrapper;
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
            initHeaderSearch();
            initLogoutBtn();
        },
        {
            once: true,
        },
    );
} else {
    initHeaderSearch();
    initLogoutBtn();
}
