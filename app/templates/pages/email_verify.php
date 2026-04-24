<div class="container flex-stack-lg">
    <a class="link-shell" href="/">
        <span class="btn primary-btn shape-cut-corners--diagonal">
            <span class="primary-btn__sign">←</span>

            <span>На главную</span>
        </span>
    </a>

    <div class="content-card shape-cut-corners--diagonal">
        <h1 class="content-card__title">
            <?= htmlspecialchars($pageData['title'], ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p> <?= htmlspecialchars($pageData['message'], ENT_QUOTES, 'UTF-8') ?>  </p>

        <?php if ($isAuthenticated && $pageData['show_resend']) { ?>  
            <button id="resend-button" class="btn-reset resend-btn btn-shell" type="button">
                <span class="btn shape-cut-corners--diagonal">
                    Получить новое письмо
                </span>
            </button>
        <?php } ?>  
    </div>
</div>