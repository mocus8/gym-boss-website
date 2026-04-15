<!-- Контент главной страницы -->

<div class="container catalog">
    <?php foreach ($catalog as $categoryData) { ?>
        <section class="catalog__section">
            <h2 class="catalog__section-title shape-cut-corners">
                <?= htmlspecialchars($categoryData['category']['name'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            
            <ul class="list-reset catalog__section-list">
                <?php foreach ($categoryData['products'] as $productData) { ?>
                    <li class="catalog__section-item">
                        <a
                            class="link-shell full-size"
                            href="/products/<?= htmlspecialchars($productData['slug'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="product-card shape-cut-corners">
                                <img
                                    class="img-full shape-cut-corners"
                                    src="<?= htmlspecialchars($productData['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <h3><?= htmlspecialchars($productData['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                
                                <p class="product-card__price">
                                    <?= htmlspecialchars(formatPrice((float)$productData['price']), ENT_QUOTES, 'UTF-8') ?> ₽
                                </p>
                            </div>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </section>
    <?php } ?>
</div>