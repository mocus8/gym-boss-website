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
    <?php foreach ($images as $image) { ?>
        <button class="product_minor_images_button">
            <img class="product_img_2" src="<?= htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        </button>
    <?php } ?>
    </div>

    <img class="product_main_img" src="<?= htmlspecialchars($images[0], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($productName) ?>">

    <div class="product_description_head">
        О товаре:
    </div>

    <div class="product_description_text">
        <?= $productDescriptionHtml ?>
    </div>
</div>

<div class="product_right">
    <div class="product_inf">
        <div class="product_name_2">
            <?= htmlspecialchars($productName) ?>
        </div>

        <div class="product_price_2">
            <?= number_format($product['price'], 2, ',', ' ') ?> ₽
        </div>

        <div class="product_availability">
            В наличии
        </div>
    </div>

    <button class="product_button_add_not_in_cart" type="button" id="button-add" data-product-add-cart data-product-id="<?= (int)$product['product_id'] ?>">
        Добавить в корзину
    </button>

    <div class="product_button_add_in_cart hidden" type="button" id="button-change-qty">
        <button class="product_sign_button" type="button" data-product-subtract-cart data-product-id="<?= (int)$product['product_id'] ?>">
            <img class="product_interaction_sign" src="/img/minus.png">
        </button>

        <span id="product-cart-counter"></span>

        <button class="product_sign_button" type="button" data-product-add-cart data-product-id="<?= (int)$product['product_id'] ?>">
            <img class="product_interaction_sign" src="/img/plus.png"> 
        </button>
    </div>
</div>