<div
    id="auth-modal"
    class="modal"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-modal-title"
>
    <div class="modal__overlay" data-modal-overlay aria-hidden="true"></div>

    <div class="modal__content shape-cut-corners">
        <div class="modal__header">
            <h2 id="auth-modal-title" class="visually-hidden">
                Вход и регистрация
            </h2>

            <div class="modal__tabs">
                <button class="btn-reset btn-shell" data-auth-tab="login" type="button">
                    <span class="btn shape-cut-corners">
                        Войти
                    </span>
                </button>

                <button class="btn-reset btn-shell is-chosen" data-auth-tab="register" type="button">
                    <span class="btn shape-cut-corners">
                        Зарегистрироваться
                    </span>
                </button>
            </div>

            <button class="btn-reset modal__close-btn btn-shell" data-modal-close type="button" aria-label="Закрыть модальное окно">
                ✕
            </button>
        </div>

        <div>
            <form id="login-form" class="modal__body" action="api/auth/login" method="POST" novalidate>
                <div class="form__field">
                    <label for="login-email">
                        Ваш email:
                    </label>

                    <input
                        id="login-email"
                        class="shape-cut-corners"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        maxlength="254"
                        aria-describedby="login-email-error"
                    >
                </div>

                <div id="login-email-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <div class="form__field">
                    <label for="login-password">
                        Ваш пароль:
                    </label>

                    <input
                        id="login-password"
                        class="shape-cut-corners"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        maxlength="254"
                        aria-describedby="login-password-error"
                    >
                </div>

                <div id="login-password-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <button class="btn-reset form__submit-btn-shell btn-shell" type="submit">
                    <span class="btn form__submit-btn shape-cut-corners">
                        Войти
                    </span>
                </button>
            </form>

            <form id="register-form" class="modal__body" action="api/auth/register" method="POST" hidden novalidate>
                <div class="form__field">
                    <label for="registration-name">
                        Ваши имя и фамилия:
                    </label>

                    <input
                        id="registration-name"
                        class="shape-cut-corners"
                        type="text"
                        name="name"
                        required
                        autocomplete="name"
                        maxlength="100"
                        aria-describedby="registration-name-error"
                    >
                </div>

                <div id="registration-name-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <div class="form__field">
                    <label for="registration-email">
                        Ваш email:
                    </label>

                    <input
                        id="registration-email"
                        class="shape-cut-corners"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        maxlength="254"
                        aria-describedby="registration-email-error"
                    >
                </div>

                <div id="registration-email-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <div class="form__field">
                    <label for="registration-password">
                        Ваш пароль:
                    </label>

                    <input
                        id="registration-password"
                        class="shape-cut-corners"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="64"
                        aria-describedby="registration-password-error"
                    >
                </div>

                <div id="registration-password-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <div class="form__field">
                    <label for="registration-confirm-password">
                        Введите пароль еще раз:
                    </label>

                    <input
                        id="registration-confirm-password"
                        class="shape-cut-corners"
                        type="password"
                        name="confirm_password"
                        required
                        minlength="8"
                        maxlength="64"
                        aria-describedby="registration-confirm-password-error"
                    >
                </div>

                <div id="registration-confirm-password-error" class="form__error is-hidden">
                    <img class="form__error-icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <span data-error-text></span>
                </div>

                <button class="btn-reset form__submit-btn-shell btn-shell" type="submit">
                    <span class="btn form__submit-btn shape-cut-corners">
                        Зарегистрироваться
                    </span>
                </button>
            </form>
        </div>

        <div class="modal__footer">
            <button id="forgot-password-btn" class="btn-reset btn-shell" type="button">
                <span class="btn shape-cut-corners">
                    Забыли пароль?
                </span>
            </button>

            <button class="btn-reset btn-shell" type="button" data-auth-switch-to="register">
                <span class="btn shape-cut-corners">
                    Нет аккаунта? Зарегистрируйтесь
                </span>
            </button>

            <button class="btn-reset btn-shell" type="button" data-auth-switch-to="login" hidden>
                <span class="btn shape-cut-corners">
                    Уже есть аккаунт? Авторизируйтесь
                </span>
            </button>
        </div>
    </div>
</div>
