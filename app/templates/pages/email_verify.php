<div class="container email-verify">
    <a class="link-shell" href="/">
        <span class="btn return-btn shape-cut-corners">
            На главную
        </span>
    </a>

    <div class="email-verify__content shape-cut-corners">
        <h1 class="email-verify__title">
            <?= htmlspecialchars($pageData['title'], ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p> <?= htmlspecialchars($pageData['message'], ENT_QUOTES, 'UTF-8') ?>  </p>

        <?php if ($isAuthenticated && $pageData['show_resend']) { ?>  
            <button id="resend-button" class="btn-reset email-verify__resend-btn btn-shell" type="button">
                <span class="btn shape-cut-corners">
                    Получить новое письмо
                </span>
            </button>
        <?php } ?>  
    </div>
</div>