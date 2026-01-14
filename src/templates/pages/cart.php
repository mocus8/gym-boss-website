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
    Товары в корзине:
</div>

<div class="cart_products" id="product-container">
    <div class="cart_products_loader">
        Загрузка... <img class="loader" src="/img/loader.png" alt="Загрузка">
    </div>
</div>

<div class="cart_right">
    <img class="order_icon" src="/img/cart.png">
    <div class="order_inf">
        <div class="order_inf_price_text">
            Сумма заказа
        </div>

        <div class="order_inf_amount">
            Количество товаров: <span id="items-total-qty">0</span> шт.
        </div>

        <div class="order_inf_price_1" >
            Стоимость всех товаров: <span data-items-total-price>0</span> ₽
        </div>

        <div class="order_inf_price_2">
            Итого: <span data-items-total-price>0</span> ₽
        </div>
    </div>
    
    <?php if ($userId) { ?>
        <a class="order_start hidden" id="start-order-btn" href="/order-making">
            <div class="order_start_text">
                Оформить заказ
            </div>
        </a>
    <?php } else { ?>
        <a class="order_start hidden" id="start-order-btn" data-open-modal="registration">
            <div class="order_start_text">
                Оформить заказ
            </div>
        </a>
    <?php } ?>
</div>