<div class="container flex-stack-lg">
    <a class="link-shell" href="/">
        <span class="btn return-btn shape-cut-corners">
            <img class="return-btn__img" src="/assets/images/ui/arrow_back.png">

            <span>На главную</span>
        </span>
    </a>

    <div class="email-verify shape-cut-corners">
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