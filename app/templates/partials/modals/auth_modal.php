<div
    id="auth-modal"
    class="modal"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-modal-title"
>
    <div class="modal-overlay" data-modal-overlay></div>

    <div class="modal-content">
        <div class="modal-header">
            <h2 id="auth-modal-title" class="visually-hidden">
                Вход и регистрация
            </h2>

            <div class="modal__tabs">
                <button type="button" class="btn is-chosen" data-auth-tab="login">Войти</button>
                <button type="button" class="btn" data-auth-tab="register">Зарегистрироваться</button>
            </div>

            <button type="button" class="modal-close" data-modal-close aria-label="Закрыть модальное окно">✕</button>
        </div>

        <div class="modal-body">
            <form action="api/auth/login" method="POST" id="login-form" novalidate>
                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="login-email">
                        Ваш email:
                    </label>

                    <input
                        required
                        id="login-email"
                        class="registration_modal_input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        maxlength="254"
                        aria-describedby="login-email-error"
                    >
                </div>

                <div id="login-email-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="login-password">
                        Ваш пароль:
                    </label>

                    <input
                        required
                        id="login-password"
                        class="registration_modal_input"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        maxlength="254"
                        aria-describedby="login-password-error"
                    >
                </div>

                <div id="login-password-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_buttons">
                    <button class="btn" type="submit">
                        Войти
                    </button>
                </div>
            </form>

            <form action="api/auth/register" method="POST" id="register-form" hidden novalidate>
                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="registration-name">
                        Ваши имя и фамилия:
                    </label>

                    <input
                        required
                        id="registration-name"
                        class="registration_modal_input"
                        type="text"
                        name="name"
                        autocomplete="name"
                        maxlength="100"
                        aria-describedby="registration-name-error"
                    >
                </div>

                <div id="registration-name-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="registration-email">
                        Ваш email:
                    </label>

                    <input
                        required
                        id="registration-email"
                        class="registration_modal_input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        maxlength="254"
                        aria-describedby="registration-email-error"
                    >
                </div>

                <div id="registration-email-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="registration-password">
                        Ваш пароль:
                    </label>

                    <input
                        required
                        id="registration-password"
                        class="registration_modal_input"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="64"
                        aria-describedby="registration-password-error"
                    >
                </div>

                <div id="registration-password-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <label class="registration_modal_input_text" for="registration-confirm-password">
                        Введите пароль еще раз:
                    </label>

                    <input
                        required
                        id="registration-confirm-password"
                        class="registration_modal_input"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="64"
                        aria-describedby="registration-confirm-password-error"
                    >
                </div>

                <div id="registration-confirm-password-error" class="form_error is-hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png" alt="">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_buttons">
                    <button class="btn" type="submit">
                        Зарегистрироваться
                    </button>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" id="forgot-password-btn">Забыли пароль?</button>

            <button type="button" class="btn" data-auth-switch-to="register">Нет аккаунта? Зарегистрируйтесь</button>

            <button type="button" class="btn" data-auth-switch-to="login" hidden>Уже есть аккаунт? Авторизируйтесь</button>
        </div>
    </div>
</div>
