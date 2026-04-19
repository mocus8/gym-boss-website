<header class="site-header">
    <div class="container">
        <div class="site-header__inner ">
            <a class="link-shell" href="/">
                <div class="site-header__brand shape-cut-corners--diagonal">
                    <div class="site-header__brand-text">
                        <span class="site-header__brand-tagline">
                            Интернет-магазин спортивной атрибутики
                        </span>

                        <span class="site-header__brand-name">
                            “Gym Boss”
                        </span>
                    </div>

                    <img class="site-header__brand-image" src="/assets/images/ui/billy.svg" alt="">
                </div>
            </a>

            <div class="site-header__center">
                <nav>
                    <ul class="site-header__list list-reset flex-center">
                        <li>
                            <a class="link-shell" href="/">
                                <span class="btn shape-cut-corners--diagonal">
                                    <img class="site-header__nav-icon" src="/assets/images/ui/catalog.png" alt="">
                                    
                                    <span>Каталог</span>
                                </span>
                            </a>
                        </li>

                        <li>
                            <a class="link-shell" href="/stores">
                                <span class="btn shape-cut-corners--diagonal">
                                    <img class="site-header__nav-icon" src="/assets/images/ui/map.png" alt="">

                                    <span>Наши магазины</span>
                                </span>
                            </a>
                        </li>

                        <li>
                            <a class="link-shell" href="/contacts">
                                <span class="btn shape-cut-corners--diagonal">
                                    <img class="site-header__nav-icon" src="/assets/images/ui/phone.png" alt="">

                                    <span>Контакты</span>
                                </span>
                            </a>
                        </li>

                        <li>
                            <a class="link-shell" href="/about">
                                <span class="btn shape-cut-corners--diagonal">
                                    <img class="site-header__nav-icon" src="/assets/images/ui/info.png" alt="">

                                    <span>О сайте</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <form
                    id="header-search"
                    class="site-header__search"
                    role="search"
                    action="/api/products/search"
                    method="get"
                >
                    <div class="site-header__search-control shape-cut-corners--diagonal"> 
                        <img class="site-header__nav-icon" src="/assets/images/ui/glass.png" alt="">

                        <input
                            id="header-search-input"
                            class="site-header__search-input"
                            type="search"
                            name="q"
                            maxlength="150"
                            placeholder="Поиск товаров, например: гриф для штанги"
                            autocomplete="off"
                        >
                        
                        <button
                            id="header-search-cancel-button"
                            class="btn-reset site-header__search-clear-btn-shell btn-shell is-hidden"
                            type="button"
                            aria-label="Очистить поиск"
                        >  
                            <span class="btn site-header__search-clear-btn shape-cut-corners--diagonal">✕</span>
                        </button>
                    </div>

                    <div id="search-results-container" class="site-header__search-results-container flex-center is-hidden"></div>
                </form>
            </div>

            <div class="site-header__account-wrapper">
                <div class="site-header__account shape-cut-corners--diagonal">
                    <span class="site-header__account-title">
                        <img class="site-header__nav-icon" src="/assets/images/ui/person.png" alt="">
                        <span>Аккаунт</span>
                    </span>

                    <a class="link-shell" href="/cart">
                        <span class="btn site-header__account-btn shape-cut-corners--diagonal">
                            <img class="site-header__nav-icon" src="/assets/images/ui/cart.png" alt="">

                            <span>
                                Корзина (<span id="header-cart-counter"><?= (int)$cartCount ?></span>)
                            </span>
                        </span>
                    </a>

                    <?php if ($currentUser === null) { ?>
                        <button class="btn-reset btn-shell" data-modal-open="auth-modal" type="button">
                            <span class="btn site-header__account-btn shape-cut-corners--diagonal">
                                Войти
                            </span>
                        </button>
                    <?php } else { ?>
                        <button
                            id="account-menu-trigger"
                            class="btn-reset site-header__account-trigger btn-shell"
                            type="button"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="account-menu"
                        >
                            <span class="btn site-header__account-btn shape-cut-corners--diagonal">
                                <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>

                                <span class="site-header__account-trigger-arrow" aria-hidden="true"></span>
                            </span>
                        </button>
                    <?php } ?>
                </div>

                <div
                    id="account-menu"
                    class="site-header__account-dropdown shape-cut-corners--diagonal is-hidden"
                    aria-labelledby="account-menu-trigger"
                >

                    <a class="link-shell" href="/account">
                        <span class="btn site-header__account-dropdown-btn shape-cut-corners--diagonal">Профиль</span>
                    </a>

                    <a class="link-shell" href="/account/orders">
                        <span class="btn site-header__account-dropdown-btn shape-cut-corners--diagonal">Мои заказы</span>
                    </a>

                    <button class="btn-reset btn-shell" type="button" data-modal-open="logout-modal">
                        <span class="btn site-header__account-dropdown-btn shape-cut-corners--diagonal">Выйти</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>