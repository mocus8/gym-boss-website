<?php require_once __DIR__ . '/../../header.php'; ?>

<main class="main">
    <div class="button_return_position">
        <a href="index.php">
            <div class="button_return">
                <div class="button_return_text">
                    На главную
                </div>
                <img class="button_return_img" src="img/arrow_back.png">
            </div>
        </a>
    </div>

    <div class="order_back">
        <div class="order_title">
            При обработке заказа произошла ошибка.
        </div>

        <div class="order_row">
            <?php
            $errors = [
                'ORDER_NOT_FOUND' => 'Заказ не найден. Вернитесь к истории заказов.',
                'DATABASE_ERROR' => 'Техническая ошибка. Попробуйте обновить страницу.',
                'PAYMENT_SYSTEM_ERROR' => 'Ошибка оплаты. Попробуйте позже.',
                'PAYMENT_NOT_CREATED' => 'Платеж не создан, оформите заказ заново.'
            ];

            echo $errors[$error_message] ?? 'Неизвестная ошибка, Попробуйте позже.';
            ?>
        </div>
        
        <div class="order_row">
            Если проблема повторяется, свяжитесь с поддержкой: <a href="tel:+79000000000" class="colour_href">+7 900 000 00 00</a>
        </div>

        <div class="order_last_actions">
            <a href="my_orders.php" class="order_action_button">История заказов</a>
            <a href="index.php" class="order_action_button">Продолжить покупки</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../footer.php';?>