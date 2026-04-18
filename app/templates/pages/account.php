<div class="container account">
    <h1 class="page-title">Личный кабинет</h1>

    <form id="change-name-form" class="account__section" action="api/account/profile" method="POST" novalidate>
        <div class="form__field">
            <label for="account-name">
                Ваше имя
            </label>

            <input
                id="account-name"
                class="shape-cut-corners"
                type="text"
                name="name"
                required
                maxlength="100"
                value="<?= htmlspecialchars($currentUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                aria-describedby="account-name-error"
            >
        </div>

        <div id="account-name-error" class="form__error is-hidden">
            <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
            <span data-error-text></span>
        </div>

        <button class="btn-reset form__submit-btn-shell btn-shell" type="submit">
            <span class="btn form__submit-btn shape-cut-corners">
                Сменить имя
            </span>
        </button>
    </form>

    <div class="account__section">
        <?php if (!$currentUser['is_verified']) { ?>
            <p class="account__message">
                Чтобы завершить регистрацию и получить возможность оформлять заказы подтвердите ваш email
            </p>
        <?php } ?>

        <p>
            Ваш email: <?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if (!$currentUser['is_verified']) { ?>
            <button id="resend-verification-email-btn" class="btn-reset form__submit-btn-shell btn-shell" type="button">
                <span class="btn form__submit-btn shape-cut-corners">
                    Получить письмо для подтверждения
                </span>
            </button>
        <?php } ?>
    </div>

    <form action="api/account/password" class="account__section" method="POST" id="change-pass-form" novalidate>
        <div class="form__field">
            <label for="account-current-password">
                Ваш текущий пароль:
            </label>

            <input
                id="account-current-password"
                class="shape-cut-corners"
                type="password"
                name="current_password"
                required
                autocomplete="current-password"
                minlength="8"
                maxlength="64"
                aria-describedby="account-current-password-error"
            >
        </div>

        <div id="account-current-password-error" class="form__error is-hidden">
            <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
            <span data-error-text></span>
        </div>

        <div class="form__field">
            <label for="account-new-password">
                Новый пароль:
            </label>

            <input
                id="account-new-password"
                class="shape-cut-corners"
                type="password"
                name="new_password"
                required
                autocomplete="new-password"
                minlength="8"
                maxlength="64"
                aria-describedby="account-new-password-error"
            >
        </div>

        <div id="account-new-password-error" class="form__error is-hidden">
            <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
            <span data-error-text></span>
        </div>

        <div class="form__field">
            <label for="account-confirm-password">
                Подтверждение пароля:
            </label>

            <input
                id="account-confirm-password"
                class="shape-cut-corners"
                type="password"
                name="confirm_password"
                required
                autocomplete="new-password"
                minlength="8"
                maxlength="64"
                aria-describedby="account-confirm-password-error"
            >
        </div>

        <div id="account-confirm-password-error" class="form__error is-hidden">
            <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
            <span data-error-text></span>
        </div>

        <button class="btn-reset form__submit-btn-shell btn-shell" type="submit">
            <span class="btn form__submit-btn shape-cut-corners">
                Сменить пароль
            </span>
        </button>
    </form>

    <button id="delete-account-btn" class="btn-reset account-delete-btn-shell btn-shell" type="button">
        <span class="btn form__submit-btn shape-cut-corners">
            Удалить аккаунт
        </span>
    </button>
</div>