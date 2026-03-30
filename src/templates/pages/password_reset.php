<div class="button_return_position">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/img/arrow_back.png">
        </div>
    </a>
</div>

<form action="api/auth/password/reset" method="POST" class="password_reset_form" id="reset-pass-form">
    <div class="registration_modal_input_text">
        Сброс пароля
    </div>

    <input type="hidden" name="token" value="<?= $token ?>">

    <div class="registration_modal_input_back">
        <span class="registration_modal_input_text">
            Новый пароль:
        </span>
        <input
            required
            class="registration_modal_input"
            type="password"
            name="password"
            autocomplete="new-password"
            minlength="8"
            maxlength="64"
            novalidate
        >
    </div>

    <div class="registration_modal_input_back">
        <span class="registration_modal_input_text">
            Подтверждение пароля:
        </span>
        <input
            required
            class="registration_modal_input"
            type="password"
            name="confirm_password"
            autocomplete="new-password"
            minlength="8"
            maxlength="64"
            novalidate
        >
    </div>

    <div class="form_error form_error_hidden">
        <img class="error_modal_icon" src="/img/error_modal_icon.png">
        <div class="error_modal_text"></div>
    </div>

    <div class="registration_modal_buttons">
        <button class="registration_modal_button" type="submit">
            Подтвердить смену пароля
        </button>
    </div>
</form>

<div class="password_reset_form hidden" id="reset-success-modal">
    <div class="registration_modal_input_text">
        Пароль успешно изменен
    </div>

    <a class="registration_modal_button" data-modal-open="auth-modal">
        Войти в аккаунт
    </a>
</div>