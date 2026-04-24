<div class="container flex-stack-base">
    <div class="checkout__delivery-tabs">
        <p>Получение:</p>

        <button 
            id="order-type-courier"
            class="btn-reset btn-shell"
            type="button"
        >
            <span class="btn checkout__delivery-tab shape-cut-corners--diagonal">
                Доставка
            </span>
        </button>

        <button 
            id="order-type-pickup"
            class="btn-reset btn-shell"
            type="button"
        >
            <span class="btn checkout__delivery-tab shape-cut-corners--diagonal">
                Самовывоз
            </span>
        </button>
    </div>

    <!-- Прокидываем с php конфига бэка во фронт переменные стоимости доставки и порога, времени доставки/самовывоза -->
    <div
        id="checkout-container"
        class="checkout__container"
        data-courier-free-threshold="<?= (int)$deliveryConfig['free_delivery_threshold'] ?>"
        data-courier-price="<?= (int)$deliveryConfig['courier_delivery_price'] ?>"
        data-courier-delivery-from-hours="<?= (int)$deliveryConfig['courier_delivery_from_hours'] ?>"
        data-courier-delivery-to-hours="<?= (int)$deliveryConfig['courier_delivery_to_hours'] ?>"
        data-pickup-ready-from-hours="<?= (int)$deliveryConfig['pickup_ready_from_hours'] ?>"
        data-pickup-ready-to-hours="<?= (int)$deliveryConfig['pickup_ready_to_hours'] ?>"
    >
        <div class="checkout__delivery-type" data-order-type="courier">
            <div class="checkout__address-search">
                <input
                    id="address-search-input"
                    class="shape-cut-corners--diagonal" 
                    placeholder="Введите адрес доставки"
                    type="text"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                >

                <button 
                    id="address-search-btn"
                    class="btn-reset btn-shell"
                    type="button"
                >
                    <span class="btn shape-cut-corners--diagonal">
                        Найти
                    </span>
                </button>

                <ul id="suggestions-container" class="list-reset checkout__address-suggestions-container" hidden></ul>
            </div>

            <div class="map__container shape-cut-corners--diagonal">
                <div id="courier-map-loader" class="map__overlay flex-center" role="status"> 
                    <img class="map__loader-spinner" src="/assets/images/ui/spinner.png" alt="">

                    <span class="visually-hidden">Карта загружается</span>
                </div>

                <div id="courier-map" class="map__inner"></div>

                <div id="courier-map-error" class="map__overlay flex-center" hidden>
                    <p>Карта временно недоступна :(</p>

                    <p>Попробуйте обновить страницу</p>
                </div>
            </div>
        </div>

        <div class="checkout__delivery-type" data-order-type="pickup" hidden>
            <p>Выберите магазин для самовывоза:</p>

            <div class="map__container shape-cut-corners--diagonal">
                <div id="pickup-map-loader" class="map__overlay flex-center" role="status"> 
                    <img class="map__loader-spinner" src="/assets/images/ui/spinner.png" alt="">

                    <span class="visually-hidden">Карта загружается</span>
                </div>

                <div id="pickup-map" class="map__inner"></div>

                <div id="pickup-map-error" class="map__overlay flex-center" hidden>
                    <p>Карта временно недоступна :(</p>

                    <p>Попробуйте обновить страницу</p>
                </div>
            </div>
        </div>

        <div class="checkout__summary">
            <div class="checkout__summary-info shape-cut-corners--diagonal">
                <p class="checkout__summary-title">
                    Ваш заказ
                </p>

                <ul id="checkout-items-container" class="checkout__items-list"></ul>

                <p>
                    Количество товаров: <span id="checkout-items-count">загрузка...</span>
                </p>

                <p>
                    Стоимость всех товаров: <span id="checkout-items-price">загрузка...</span> ₽
                </p>

                <p data-order-type="courier" hidden>
                    Стоимость доставки: <span id="checkout-delivery-price">загрузка...</span> ₽ <span id="checkout-delivery-note"></span>
                </p>

                <p>
                    Итого: <span id="checkout-total-price">загрузка...</span> ₽
                </p>

                <p data-order-type="courier" hidden>
                    Адрес доставки: <span id="courier-address" data-postal-code="">не указан</span>
                </p>

                <p data-order-type="pickup" hidden>
                    Адрес магазина для самовывоза: <span id="pickup-address" data-store-id="">не указан</span>
                </p>

                <p id="courier-date-row" hidden>
                    Примерная дата доставки: <span id="courier-date-text">загрузка...</span>
                </p>

                <p id="pickup-date-row" hidden>
                    Примерная дата готовности заказа: <span id="pickup-date-text">загрузка...</span>
                </p>
            </div>

            <button 
                id="create-order-btn"
                class="btn-reset checkout__create-order-btn btn-shell"
                type="button"
            >
                <span class="btn primary-btn shape-cut-corners--diagonal">
                    Оформить заказ
                </span>
            </button>
        </div>
    </div>
</div>