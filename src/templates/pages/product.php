<div class="product_left">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/img/arrow_back.png">
        </div>
    </a>
    <div class="product_minor_images">
        <?php for ($i = 0; $i < count($productImages); $i++) { ?>
            <button class="product_minor_images_button">
                <img class="product_img_2" src="<?= htmlspecialchars($productImages[$i]) ?>" alt="<?= htmlspecialchars($productName) ?>">
            </button>
        <?php } ?>
    </div>
    <img class="product_main_img" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($productName) ?>">
    <div class="product_description_head">
        О товаре:
    </div>
    <div class="product_description_text">
        <?= $productDescription ?>
    </div>
</div>
<div class="product_right">
    <div class="product_inf">
        <div class="product_name_2">
            <?= htmlspecialchars($productName) ?>
        </div>
        <div class="product_price_2">
            <?= number_format($productPrice, 0, '', ' ') ?> ₽
        </div>
        <div class="product_availability">
            В наличии
        </div>
    </div>
    <button class="product_button_add_not_in_cart open" type="button" id="product-button-add-not-in-cart" data-product-add-cart data-product-id="<?= $productId ?>">
        Добавить в корзину
    </button>
    <div class="product_button_add_in_cart" type="button" id="product-button-add-in-cart">
        <button class="product_sign_button">
            <img class="product_interaction_sign" src="/img/minus.png" data-product-subtract-cart data-product-id="<?= $productId ?>">
        </button>
            <span id="product-cart-counter"><?= $cartAmount ?></span>
        <button class="product_sign_button">
            <img class="product_interaction_sign" src="/img/plus.png" data-product-add-cart data-product-id="<?= $productId ?>"> 
        </button>
    </div>
</div>