<!-- Контент главной страницы -->

<div class="catalog">
<?php foreach ($categoriesWithProducts as $categoryData) { ?>
    <div class="category_row">
        <div class="category_name">
            <?= htmlspecialchars($categoryData['category']['name'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="products_main">
            <?php foreach ($categoryData['products'] as $productData) { ?>
                <a href="/product/<?= htmlspecialchars($productData['slug'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="product">
                        <div class="product_click">
                            <img class="product_img_1"
                                 src="<?= htmlspecialchars($productData['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="product_name_1">
                                <?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="product_price_1">
                                <?= htmlspecialchars($productData['price'], ENT_QUOTES, 'UTF-8') ?> ₽
                            </div>
                        </div>
                    </div>
                </a>
            <?php } ?>
        </div>
    </div>
<?php } ?>
</div>
