<div class="container product">
    <div class="product__left">
        <div class="product__images">
            <div class="product__minor-images shape-cut-corners--diagonal">
                <?php foreach ($images as $image) { ?>
                    <button class="btn-reset btn-shell" data-product-image-btn type="button">
                        <img
                            class="product__minor-image shape-cut-corners--diagonal"
                            src="<?= htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                        >
                    </button>
                <?php } ?>
            </div>

            <div class="product__main-image-shell">
                <img
                    class="product__main-image shape-cut-corners--all"
                    src="<?= htmlspecialchars($images[0], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >
            </div>
        </div>

        <div class="product__description text-narrow shape-cut-corners--diagonal">
            <h2>О товаре:</h2>

            <div class="product__description-text"><?= $productDescriptionHtml ?></div>
        </div>
    </div>

    <div class="product__right shape-cut-corners--diagonal">
        <div class="product__info">
            <h1><?= htmlspecialchars($productName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

            <p><?= number_format($product['price'], 2, ',', ' ') ?> ₽</p>

            <p>В наличии</p>
        </div>

        <button 
            id="button-add"
            class="btn-reset btn-shell"
            data-product-add-cart
            data-product-id="<?= (int)$product['id'] ?>"
            type="button"
        >
            <span class="btn product__add-to-cart-btn shape-cut-corners--diagonal">
                Добавить в корзину
            </span>
        </button>

        <div id="button-change-qty" class="product__change-qty shape-cut-corners--diagonal" hidden>
            <button
                class="btn-reset product__change-qty-btn-shell btn-shell"
                type="button"
                data-product-subtract-cart
                data-product-id="<?= (int)$product['id'] ?>"
            >
                <span class="product__change-qty-sign">–</span>
            </button>

            <span id="product-cart-counter"></span>

            <button
                class="btn-reset product__change-qty-btn-shell btn-shell"
                type="button"
                data-product-add-cart
                data-product-id="<?= (int)$product['id'] ?>"
            >
                <span class="product__change-qty-sign">+</span>
            </button>
        </div>
    </div>
</div>