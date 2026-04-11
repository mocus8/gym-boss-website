<!-- Контент главной страницы -->

<div class="catalog">
    <?php foreach ($catalog as $categoryData) { ?>
        <section class="category_row">
            <h2 class="category_name">
                <?= htmlspecialchars($categoryData['category']['name'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            
            <ul class="products_main">
                <?php foreach ($categoryData['products'] as $productData) { ?>
                    <li>
                        <article>
                            <a class="product" href="/products/<?= htmlspecialchars($productData['slug'], ENT_QUOTES, 'UTF-8') ?>">
                                <img class="product_img_1"
                                    src="<?= htmlspecialchars($productData['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <h3 class="product_name_1">
                                    <?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                
                                <p class="product_price_1">
                                    <?= htmlspecialchars(formatPrice((float)$productData['price']), ENT_QUOTES, 'UTF-8') ?> ₽
                                </p>
                            </a>
                        </article>
                    </li>
                <?php } ?>
            </ul>
        </section>
    <?php } ?>
</div>
