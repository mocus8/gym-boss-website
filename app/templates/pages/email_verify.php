<div class="button_return_position">
    <a href="/">
        <div class="button_return">
            <div class="button_return_text">
                На главную
            </div>
            <img class="button_return_img" src="/assets/images/ui/arrow_back.png">
        </div>
    </a>
</div>
<div class="contacts">
    <div class="contact">
        <div class="contact_type">
            <?= htmlspecialchars($pageData['title'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="contact_inf">
            <?= htmlspecialchars($pageData['message'], ENT_QUOTES, 'UTF-8') ?>  
        </div>

        <div class="contact_inf">
            <?php if ($isAuthenticated && $pageData['showResend']) { ?>  
                <button type="button" class="btn" id="resend-button">
                    Получить новое письмо
                </button>
            <?php } ?>  
        </div>
    </div>
</div>