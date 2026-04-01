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

<div class="cart_in_cart_text">
    Личный кабинет
</div>

<div class="account_wrapper">
    <form action="api/account/profile" method="POST" class="account_form" id="change-name-form" novalidate>
        <div class="registration_modal_input_text">
            Ваше имя
        </div>

        <div class="registration_modal_input_back">
            <input
                required
                class="registration_modal_input"
                type="text"
                name="name"
                maxlength="100"
                value="<?= htmlspecialchars($currentUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form_error form_error_hidden">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            <div class="error_modal_text"></div>
        </div>

        <div class="registration_modal_buttons">
            <button class="registration_modal_button" type="submit">
                Сменить имя
            </button>
        </div>
    </form>

    <div class="account_form">
        <?php if (!$currentUser['is_verified']) { ?>
            <div class="registration_modal_input_text">
                Чтобы завершить регистрацию и получить возможность оформлять заказы подтвердите ваш email
            </div>
        <?php } ?>

        <div class="registration_modal_input_text">
            Ваш email: <?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <?php if (!$currentUser['is_verified']) { ?>
            <div class="registration_modal_buttons">
                <button class="registration_modal_button" id="resend-verification-email-btn">
                    Получить письмо для подтверждения
                </button>
            </div>
        <?php } ?>
    </div>

    <form action="api/account/password" method="POST" class="account_form" id="change-pass-form" novalidate>
        <div class="registration_modal_input_text">
            Смена пароля
        </div>

        <div class="registration_modal_input_back">
            <span class="registration_modal_input_text">
                Ваш текущий пароль:
            </span>
            <input
                required
                class="registration_modal_input"
                type="password"
                name="current_password"
                autocomplete="current-password"
                minlength="8"
                maxlength="64"
            >
        </div>

        <div class="form_error form_error_hidden">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            <div class="error_modal_text"></div>
        </div>

        <div class="registration_modal_input_back">
            <span class="registration_modal_input_text">
                Новый пароль:
            </span>
            <input
                required
                class="registration_modal_input"
                type="password"
                name="new_password"
                autocomplete="new-password"
                minlength="8"
                maxlength="64"
            >
        </div>

        <div class="form_error form_error_hidden">
            <img class="error_modal_icon" src="/img/error_modal_icon.png">
            <div class="error_modal_text"></div>
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

    <div class="account_form">
        <div class="registration_modal_buttons">
            <button class="registration_modal_button" id="delete-account-btn">
                Удалить аккаунт
            </button>
        </div>
    </div>
</div>