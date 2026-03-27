<header class="header">
    <a href="/" class="icon_href">
        <div class="internet_shop">
            Интернет-магазин спортивной атрибутики
        </div>

        <div class="gym_boss">
            “Gym Boss”
        </div>

        <img class="billy" src="/img/billy.svg" alt="Мужик, качёк, крутой парень, Билли Харрингтон">
<!--                                                                        Добавлять этот атрибут для CEO-->
    </a>

    <div class="header_buttons">
        <a href="/">
            <div class="header_button">
                <img class="header_button_icon" src="/img/catalog.png">
                
                <div class="header_button_text">
                    Каталог
                </div>
            </div>
        </a>

        <a href="/stores">
            <div class="header_button">
                <img class="header_button_icon" src="/img/map.png">

                <div class="header_button_text">
                    Наши магазины
                </div>
            </div>
        </a>

        <a href="/contacts">
            <div class="header_button">
                <img class="header_button_icon" src="/img/phone.png">

                <div class="header_button_text">
                    Контакты
                </div>
            </div>
        </a>

        <a href="/about">
            <div class="header_button">
                <img class="header_button_icon" src="/img/info.png">

                <div class="header_button_text">
                    О сайте
                </div>
            </div>
        </a>
     </div>

    <div class="header_search" id="header-search">
        <div class="header_search_click">
            <img class="header_search_icon" src="/img/glass.png">

            <label for="header-search-input" class="header_search_text">
                Поиск товаров:
            </label>

            <input type="search" id="header-search-input" name="q" placeholder="гриф для штанги ..." class="header_search_input" autocomplete="off" maxlength="150">
            
            <button class="header_search_cancel_button hidden" id="header-search-cancel-button">✕</button>

            <div class="query_products_container hidden" id="query-products-container"></div>
        </div>
    </div>

    <div class="header_account">
        <div class="header_account_up">
            <img class="header_account_icon" src="/img/person.png">

            <a href="/cart">
                <div class="header_button account">
                    <img class="header_button_icon" src="/img/cart.png">

                    <div class="header_button_text">
                        Корзина (<span id="header-cart-counter"><?= $cartCount ?></span>)
                    </div>
                </div>
            </a>
        </div>

        <?php if ($currentUser === null) { ?>
            <button class="header_account_button_guest"  data-modal-open="auth-modal">
                Войти
            </button>
        <?php } else { ?>
            <div class="header_account_data">
                <button class="header_account_trigger" type="button">
                    <div class="header_account_inf">
                        <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </button>

                <div class="header_account_dropdown">
                    <a href="/account">Профиль</a>

                    <a href="/account/orders">Мои заказы</a>

                    <button data-modal-open="logout-modal">Выйти</button>
                </div>
            </div>
        <?php } ?>
    </div>
</header>