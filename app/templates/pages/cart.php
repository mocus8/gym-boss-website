<div class="container cart">
    <div class="cart__items">
        <h1 class="page-title">Товары в корзине:</h1>

        <div id="product-container" class="cart__items-container">
            <div class="cart__items-loader flex-center" role="status">
                <img class="cart__items-loader-spinner" src="/assets/images/ui/loader.png" alt="">

                <span>Загрузка...</span>
            </div>
        </div>
    </div>

    <div class="cart__summary">
        <div class="cart__summary-info shape-cut-corners--diagonal">
            <div class="cart__summary-title">
                <img class="cart__summary-icon" src="/assets/images/ui/cart.png" alt="">

                <h2 class="cart__summary-row">
                    Сумма заказа
                </h2>
            </div>

            <p class="cart__summary-row">
                Количество товаров: <span id="items-total-qty">загрузка...</span> шт.
            </p>

            <p class="cart__summary-row">
                Стоимость всех товаров: <span data-items-total-price>загрузка...</span> ₽
            </p>

            <p class="cart__summary-row">
                Итого: <span data-items-total-price>загрузка...</span> ₽
            </p>
        </div>
        
        <?php if (!$isAuthenticated) { ?>
            <a id="start-order-btn" class="link-shell" data-modal-open="auth-modal" hidden>
                <span class="btn primary-btn shape-cut-corners--diagonal">
                    Перейти к оформлению
                </span>
            </a>
        <?php } else { ?>
            <a id="start-order-btn" class="link-shell" href="/checkout" hidden>
                <span class="btn primary-btn shape-cut-corners--diagonal">
                    Перейти к оформлению
                </span>
            </a>
        <?php } ?>
    </div>
</div>