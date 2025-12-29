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
<div class="cart_products">
    <?php if ($cartCount == 0) { ?>
        <div class="cart_empty">
            Корзина пуста
        </div>
    <?php } else {
        foreach ($cartItems as $item) { ?>
        <div class="cart_product" data-product-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
            <div class="product_click">
                <a href="product/<?= $item['slug'] ?>">
                    <img class="product_img_1" src="<?= $item['image_path'] ?>">
                    <div class="cart_product_name">
                        <?= htmlspecialchars($item['name']) ?>
                    </div>
                </a>
                <div class="cart_product_price">
                    <?= $item['price'] * $item['amount'] ?> ₽
                </div>
                <div class="product_interaction">
                    <div class="product_interaction_count">
                        <button class="product_sign_button">
                            <img class="product_interaction_sign" src="/img/minus.png" data-subtract-cart data-product-id="<?= $item['id'] ?>">
                        </button>
                        <div class="product_interaction_amount" id="cart-counter-<?= $item['id'] ?>">
                            <?= $item['amount'] ?>
                        </div>
                        <button class="product_sign_button">
                            <img class="product_interaction_sign" src="/img/plus.png" data-add-cart data-product-id="<?= $item['id'] ?>">
                        </button>
                    </div>
                    <button class="product_sign_button">
                        <img class="product_interaction_delete" src="/img/trash.png" data-remove-cart data-product-id="<?= $item['id'] ?>">
                    </button>
                </div>
            </div>
        </div>
    <?php }
    } ?>
</div>
<div class="cart_right">
    <img class="order_icon" src="/img/cart.png">
    <div class="order_inf">
        <div class="order_inf_price_text">
            Сумма заказа
        </div>
        <div class="order_inf_amount">
            Количество товаров: <span id="order-cart-count"><?= $cartCount ?></span> шт.
        </div>
        <div class="order_inf_price_1" >
            Стоимость всех товаров: <span data-total-counter><?= $cartTotalPrice ?></span> ₽
        </div>
        <div class="order_inf_price_2">
            Итого: <span data-total-counter><?= $cartTotalPrice ?></span> ₽
        </div>
    </div>
    <?php if ($cartCount != 0) {
        if ($userId) { ?>
        <a href="/order-making" class="order-button-link">
            <div class="order_start">
                <div class="order_start_text">
                    Оформить заказ
                </div>
            </div>
        </a>
    <?php } else { ?>
        <a class="order-button-link" id="open-registration-modal-from-cart">
            <div class="order_start">
                <div class="order_start_text">
                    Оформить заказ
                </div>
            </div>
        </a>
    <?php }
    } ?>
</div>