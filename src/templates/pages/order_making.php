<div class="button_return_position">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/img/arrow_back.png">
        </div>
    </a>
</div>
<div class="cart_in_cart_text">
    Получение:
</div>
<div class="order_types">
    <div class="order_type chosen" id="order-type-delivery">
        Доставка
    </div>
    <div class="order_type" id="order-type-pickup">
        Самовывоз
    </div>
</div>
<div class="modal_order_type" id="modal-order-type-delivery">
    <div class="order_left">
        <div class="map_search_form">
        <input type="text"
            id="delivery-address"
            name="delivery_address"
            autocomplete="street-address"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            class="map_search_input" 
            placeholder="Введите адрес доставки"
            readonly
            onfocus="this.removeAttribute('readonly')">
            <button type="button" id="delivery-search-btn" class="map_search_btn">
                Найти
            </button>
        </div>
        <div class="map_container">
            <div class="order_map_loader" id="delivery-map-loader"> 
                <img class="loader" src="/img/loader.png" alt="Загрузка карты">
            </div>
            <div id="delivery-map"></div>
        </div>
        <div class="error_delivery_map">
            Карта временно недоступна :(
        </div>
        <div class="error_address_not_found" id="modal-error-address-not-found">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            Адрес не найден
        </div>
        <div class="error_address_not_found" id="modal-error-address-timeout">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            Проблемы с соединением. Попробуйте еще раз
        </div>
        <div class="error_address_not_found" id="modal-error-address-empty">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            Введите адрес
        </div>
    </div>
    <div class="order_right">
        <div class="order_info">
            <div class="order_right_order">Ваш заказ</div>
            <?php foreach ($cartItems as $item) {  ?>   
                <div class="order_right_products_row">
                    <?= htmlspecialchars($item['name']) ?> (<?= $item['amount'] ?> шт.) - <?= number_format($item['price'] * $item['amount'], 2, ',', ' ') ?> ₽
                </div>
            <?php } ?>
            <div class="order_right_row">Количество товаров: <?= $cartCount ?></div>
            <div class="order_right_row">Стоимость всех товаров: <?= number_format($cartTotalPrice, 2, ',', ' ') ?> ₽</div>
            <?php if ($cartTotalPrice < 5000) { ?> 
                <div class="order_right_row">Стоимость доставки: 750,00 ₽ (бесплатно при заказе от 5000 ₽)</div>
                <div class="order_right_row">Итого: <?= number_format($cartTotalPrice, 2, ',', ' ') ?> ₽</div>
            <?php } else { ?>
                <div class="order_right_row">Стоимость доставки: 0 ₽ (бесплатно при заказе от 5000 ₽)</div>
                <div class="order_right_row">Итого: <?= number_format($cartTotalPrice, 2, ',', ' ') ?> ₽</div>
            <?php } ?>
            <div class="order_right_row">Адрес доставки: <span id="order-right-delivery-address">не указан</span></div>
        </div>
        <button class="order_right_pay_button" data-order-id="<?= $cartOrderId ?>">
            Оплатить
        </button>
        <div class="payment_errors">
            <div class="order_rigth_notification">
                <img class="error_modal_icon" src="/img/error_modal_icon.png">
                Сайт работает в тестовом режиме, не используейте реальные карты. <br>
                Карты для теста: 5555555555554444 (успех), 5555555555554535 (ошибка)
            </div>
            <div class="error_pay hidden" id="error-pay-delivery">
                <img class="error_modal_icon" src="/img/error_modal_icon.png">
                <div id="error-pay-delivery-text"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal_order_type hidden" id="modal-order-type-pickup">
    <div class="order_left">
        <div class="pickup_text">
            Выберите магазин для самовывоза:
        </div>
        <div class="map_container">
            <div class="order_map_loader" id="pickup-map-loader">
                <img class="loader" src="/img/loader.png" alt="Загрузка карты">
            </div>
            <div id="pickup-map"></div>
        </div>
        <div class="error_pickup_map">
            Карта временно недоступна :(
        </div>
    </div>
    <div class="order_right">
        <div class="order_info">
            <div class="order_right_order">Ваш заказ</div>
            <?php foreach ($cartItems as $item) { ?>   
                <div class="order_right_products_row">
                    <?= htmlspecialchars($item['name']) ?> (<?= $item['amount'] ?> шт.) - <?= number_format($item['price'] * $item['amount'], 2, ',', ' ') ?> ₽
                </div>
            <?php } ?>
            <div class="order_right_row">Количество товаров: <?= $cartCount ?></div>
            <div class="order_right_row">Стоимость всех товаров: <?= number_format($cartTotalPrice, 2, ',', ' ') ?> ₽</div>
            <div class="order_right_row">Итого: <?= number_format($cartTotalPrice, 2, ',', ' ') ?> ₽</div>
            <div class="order_right_row">Адрес магазина для самовывоза:<br><span id="order-right-pickup-address">не указан</span></div>
        </div>
        <button class="order_right_pay_button" data-order-id="<?= $cartOrderId ?>">
            Оплатить
        </button>
        <div class="payment_errors">
            <div class="order_rigth_notification">
                <img class="error_modal_icon" src="/img/error_modal_icon.png">
                Сайт работает в тестовом режиме, не используейте реальные карты. <br>
                Карты для теста: 5555555555554444 (успех), 5555555555554535 (ошибка)
            </div>
            <div class="error_pay hidden" id="error-pay-pickup">
                <img class="error_modal_icon" src="/img/error_modal_icon.png">
                <div id="error-pay-pickup-text"></div>
            </div>
        </div>
    </div>
</div>