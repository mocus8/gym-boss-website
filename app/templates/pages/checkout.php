<div class="button_return_position">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/assets/images/ui/arrow_back.png">
        </div>
    </a>
</div>

<div class="cart_in_cart_text">
    Получение:
</div>

<div class="order_types">
    <div class="order_type" id="order-type-courier">
        Доставка
    </div>

    <div class="order_type" id="order-type-pickup">
        Самовывоз
    </div>
</div>

<!-- Прокидываем с php конфига бэка во фронт переменные стоимости доставки и порога, времени доставки/самовывоза -->
<div class="order_container" id="checkout-container"
    data-courier-free-threshold="<?= (int)$deliveryConfig['free_delivery_threshold'] ?>"
    data-courier-price="<?= (int)$deliveryConfig['courier_delivery_price'] ?>"
    data-courier-delivery-from-hours="<?= (int)$deliveryConfig['courier_delivery_from_hours'] ?>"
    data-courier-delivery-to-hours="<?= (int)$deliveryConfig['courier_delivery_to_hours'] ?>"
    data-pickup-ready-from-hours="<?= (int)$deliveryConfig['pickup_ready_from_hours'] ?>"
    data-pickup-ready-to-hours="<?= (int)$deliveryConfig['pickup_ready_to_hours'] ?>"
>
    <div class="order_delivery_type" data-order-type="courier">
        <div class="order_left">
            <div class="map_search_form">
                <input type="text"
                    id="address-search-input"
                    autocomplete="street-address"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    class="map_search_input" 
                    placeholder="Введите адрес доставки"
                    readonly
                    onfocus="this.removeAttribute('readonly')"
                >

                <button type="button" id="address-search-btn" class="map_search_btn">
                    Найти
                </button>

                <div class="suggestions_container" id="suggestions-container" hidden></div>
            </div>

            <div class="map_container">
                <div class="checkout_map_loader" id="courier-map-loader"> 
                    <img class="loader" src="/assets/images/ui/loader.png" alt="Загрузка карты">
                </div>

                <div class="checkout_map" id="courier-map"></div>

                <div class="checkout_map_error hidden" id="courier-map-error">
                    Карта временно недоступна :(
                    <br>
                    Попробуйте обновить страницу
                </div>
            </div>
        </div>
    </div>

    <div class="order_delivery_type hidden" data-order-type="pickup">
        <div class="order_left">
            <div class="pickup_text">
                Выберите магазин для самовывоза:
            </div>

            <div class="map_container">
                <div class="checkout_map_loader" id="pickup-map-loader">
                    <img class="loader" src="/assets/images/ui/loader.png" alt="Загрузка карты">
                </div>

                <div class="checkout_map" id="pickup-map"></div>

                <div class="checkout_map_error hidden" id="pickup-map-error">
                    Карта временно недоступна :(
                    <br>
                    Попробуйте обновить страницу
                </div>
            </div>
        </div>
    </div>

    <div class="order_right">
        <div class="order_info">
            <div class="order_right_order">
                Ваш заказ
            </div>

            <div id="checkout-items-container"></div>

            <div class="order_right_row">
                Количество товаров: <span id="checkout-items-count">загрузка...</span>
            </div>

            <div class="order_right_row">
                Стоимость всех товаров: <span id="checkout-items-price">загрузка...</span> ₽
            </div>
            
            <div class="order_right_row hidden" data-order-type="courier">
                Стоимость доставки: <span id="checkout-delivery-price">загрузка...</span> ₽ <span id="checkout-delivery-note"></span>
            </div>

            <div class="order_right_row">
                Итого: <span id="checkout-total-price">загрузка...</span> ₽
            </div>

            <div class="order_right_row hidden" data-order-type="courier">
                Адрес доставки: <span id="courier-address" data-postal-code="">не указан</span>
            </div>

            <div class="order_right_row hidden" data-order-type="pickup">
                Адрес магазина для самовывоза: <span id="pickup-address" data-store-id="">не указан</span>
            </div>

            <div class="order_right_row hidden" id="courier-date-row">
                Примерная дата доставки: <span id="courier-date-text">загрузка...</span>
            </div>

            <div class="order_right_row hidden" id="pickup-date-row">
                Примерная дата готовности заказа: <span id="pickup-date-text">загрузка...</span>
            </div>
        </div>

        <button class="order_right_pay_button" id="create-order-btn">
            <span>Оформить заказ</span>
        </button>

        <div class="payment_errors">
            <div class="order_rigth_notification">
                <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                Сайт работает в тестовом режиме, не используейте реальные карты. <br>
                Карты для теста: 5555555555554444 (успех), 5555555555554535 (ошибка)
            </div>
        </div>
    </div>
</div>