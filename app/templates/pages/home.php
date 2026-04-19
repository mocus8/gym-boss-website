<!-- Контент главной страницы -->

<div class="container flex-stack-lg">
    <h1 class="page-title">Каталог товаров</h1>

    <?php foreach ($catalog as $categoryData) { ?>
        <section class="catalog-section">
            <h2 class="catalog-section__title shape-cut-corners--diagonal">
                <?= htmlspecialchars($categoryData['category']['name'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            
            <ul class="list-reset catalog-section__list">
                <?php foreach ($categoryData['products'] as $productData) { ?>
                    <li class="catalog-section__item">
                        <a
                            class="link-shell full-size"
                            href="/products/<?= htmlspecialchars($productData['slug'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="product-card shape-cut-corners--diagonal">
                                <img
                                    class="shape-cut-corners--diagonal"
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