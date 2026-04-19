<div class="container flex-stack-lg">
    <a class="link-shell" href="/">
        <span class="btn return-btn shape-cut-corners--diagonal">
            <img class="return-btn__img" src="/assets/images/ui/arrow_back.png">

            <span>На главную</span>
        </span>
    </a>

    <form id="reset-pass-form" class="content-card shape-cut-corners--diagonal" action="api/auth/password/reset" method="POST" novalidate>
        <h1 class="content-card__title">Сброс пароля</h1>

        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form__field">
            <label for="password-reset-new">
                Новый пароль:
            </label>

            <input
                id="password-reset-new"
                class="shape-cut-corners--diagonal"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                minlength="8"
                maxlength="64"
                aria-describedby="password-reset-error"
            >
        </div>

        <div class="form__field">
            <label for="password-reset-confirm">
                Подтверждение пароля:
            </label>

            <input
                id="password-reset-confirm"
                class="shape-cut-corners--diagonal"
                type="password"
                name="confirm_password"
                required
                autocomplete="new-password"
                minlength="8"
                maxlength="64"
                aria-describedby="password-reset-error"
            >
        </div>

        <div id="password-reset-error" class="form__error is-hidden">
            <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
            <span data-error-text></span>
        </div>

        <button class="btn-reset form__submit-btn-shell btn-shell" type="submit">
            <span class="btn form__submit-btn shape-cut-corners--diagonal">
                Подтвердить смену пароля
            </span>
        </button>
    </form>

    <div id="reset-success" class="content-card shape-cut-corners--diagonal" hidden>
        <h1 class="content-card__title">Пароль успешно изменен</h1>

        <button class="btn-reset reset-success-btn btn-shell" type="button" data-modal-open="auth-modal">
            <span class="btn shape-cut-corners--diagonal">
                Войти в аккаунт
            </span>
        </button>
    </div>
</div>