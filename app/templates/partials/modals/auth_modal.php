<div
    id="auth-modal"
    class="modal hidden"
    data-modal
    role="dialog"
    aria-modal="true"
    aria-labelledby="auth-modal-title"
>
    <div class="modal-overlay" data-modal-overlay></div>

    <div class="modal-content">
        <div class="modal-header">
            <div class="modal__tabs">
                <button type="button" class="btn chosen" data-auth-tab="login">Войти</button>
                <button type="button" class="btn" data-auth-tab="register">Зарегистрироваться</button>
            </div>

            <button type="button" class="modal-close" data-modal-close aria-label="Закрыть модальное окно">×</button>
        </div>

        <div class="modal-body">
            <form action="api/auth/login" method="POST" id="login-form" novalidate>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш email:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        maxlength="254"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш пароль:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        maxlength="254"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_buttons">
                    <button class="btn" type="submit">
                        Войти
                    </button>
                </div>
            </form>

            <form action="api/auth/register" method="POST" id="register-form" class="hidden" novalidate>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваши имя и фамилия:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="text"
                        name="name"
                        autocomplete="name"
                        maxlength="100"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш email:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="email"
                        name="email"
                        autocomplete="email"
                        maxlength="254"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш пароль:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="64"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Введите пароль еще раз:
                    </span>

                    <input
                        required
                        class="registration_modal_input"
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        maxlength="64"
                    >
                </div>

                <div class="form_error form_error_hidden">
                    <img class="error_modal_icon" src="/assets/images/ui/error_modal_icon.png">
                    <div class="error_modal_text"></div>
                </div>

                <div class="registration_modal_buttons">
                    <button class="btn" type="submit">
                        Зарегестрироваться
                    </button>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" id="forgot-password-btn">Забыли пароль?</button>

            <button type="button" class="btn" data-auth-switch-to="register">Нет аккаунта? Зарегестрируйтесь</button>

            <button type="button" class="btn hidden" data-auth-switch-to="login">Уже есть аккаунт? Авторизируйтесь</button>
        </div>
    </div>
</div>
