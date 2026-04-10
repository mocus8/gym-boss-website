<header class="header">
    <a class="icon_href" href="/">
        <div class="internet_shop">
            Интернет-магазин спортивной атрибутики
        </div>

        <div class="gym_boss">
            “Gym Boss”
        </div>

        <img class="billy" src="/assets/images/ui/billy.svg" alt="">
    </a>

    <nav class="header_buttons">
        <ul>
            <li>
                <a class="header_button" href="/">
                    <img class="header_button_icon" src="/assets/images/ui/catalog.png" alt="">
                    
                    <span class="header_button_text">
                        Каталог
                    </span>
                </a>
            </li>

            <li>
                <a class="header_button" href="/stores">
                    <img class="header_button_icon" src="/assets/images/ui/map.png" alt="">

                    <span class="header_button_text">
                        Наши магазины
                    </span>
                </a>
            </li>

            <li>
                <a class="header_button" href="/contacts">
                    <img class="header_button_icon" src="/assets/images/ui/phone.png" alt="">

                    <span class="header_button_text">
                        Контакты
                    </span>
                </a>
            </li>

            <li>
                <a class="header_button" href="/about">
                    <img class="header_button_icon" src="/assets/images/ui/info.png" alt="">

                    <span class="header_button_text">
                        О сайте
                    </span>
                </a>
            </li>
        </ul>
    </nav>

    <form
        id="header-search"
        class="header_search"
        role="search"
        action="/api/products/search"
        method="get"
    >
        <img class="header_search_icon" src="/assets/images/ui/glass.png" alt="">

        <label for="header-search-input" class="header_search_text">
            Поиск товаров:
        </label>

        <input
            id="header-search-input"
            class="header_search_input"
            type="search"
            name="q"
            maxlength="150"
            placeholder="гриф для штанги ..."
            autocomplete="off"
        >
        
        <button
            id="header-search-cancel-button"
            class="header_search_cancel_button hidden"
            type="button"
            aria-label="Очистить поиск"
        >
            ✕
        </button>

        <div id="search-results-container" class="query_products_container hidden"></div>
    </form>

    <div class="header_account">
        <div class="header_account_up">
            <img class="header_account_icon" src="/assets/images/ui/person.png" alt="">

            <a href="/cart">
                <img class="header_button_icon" src="/assets/images/ui/cart.png" alt="">

                <span class="header_button_text">
                    Корзина (<span id="header-cart-counter"><?= (int)$cartCount ?></span>)
                </span>
            </a>
        </div>

        <?php if ($currentUser === null) { ?>
            <button class="header_account_button_guest" data-modal-open="auth-modal" type="button">
                Войти
            </button>
        <?php } else { ?>
            <div class="header_account_data">
                <button
                    id="account-menu-trigger"
                    class="header_account_trigger"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="account-menu"
                >
                    <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>

                    <span class="header_account_trigger_arrow" aria-hidden="true"></span>
                </button>

                <div id="account-menu" class="header_account_dropdown hidden" aria-labelledby="account-menu-trigger">
                    <a href="/account">Профиль</a>

                    <a href="/account/orders">Мои заказы</a>

                    <button type="button" data-modal-open="logout-modal">Выйти</button>
                </div>
            </div>
        <?php } ?>
    </div>
</header>