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

<div class="order_back" id="order-container" data-order-id="<?= $orderId ?>">
    <div>
        <div class="order_row">
            Номер заказа: <?= '#' . str_pad($orderId, 6, '0', STR_PAD_LEFT) ?>
        </div>
        
        <div class="order_row">
            Дата оформления: <?= date('d.m.Y', strtotime($orderDetails['created_at'])) ?>
        </div>
    </div>

    <div>
        <?php
        foreach ($orderItems as $item) { 
        ?>   
            <div class="order_right_products_row">
                <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= $item['price'] * $item['amount'] ?> ₽
            </div>
        <?php
            }
        ?>
    </div>
    
    <div>
        <div class="order_row">
            Способ доставки: <?= $orderDetails['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
        </div>

        <?php
        if ($orderDetails['delivery_type'] === 'pickup' && $orderDetails['store_name']) { 
        ?>  
            <div class="order_row">
                Пункт выдачи: <?= htmlspecialchars($orderDetails['store_name']) ?>, <?= htmlspecialchars($orderDetails['store_address']) ?>
            </div>
        <?php
        } else if ($orderDetails['delivery_type'] === 'delivery' && $orderDetails['delivery_address']) { 
        ?>
            <div class="order_row">
                Адрес доставки: <?= htmlspecialchars($orderDetails['delivery_address']) ?>
            </div>

            <div class="order_row">
                Стоимость доставки: <?= (int)$deliveryCost ?> ₽
            </div>
        <?php
        } 
        ?>
    </div>

    <div class="order_row">
        Итоговая стоимость: <?= $orderTotalPrice + $deliveryCost?> ₽
    </div>

    <div class="order_title" id="order-pending-title">
        Заказ успешно оформлен и ожидает оплаты.
    </div>

    <div class="order_pending_actions" id="order-pending-actions">
        <button class="order_action_button" id="order-pay-btn">Оплатить</button>
        <button class="order_action_button" id="order-cancel-btn">Отменить</button>
    </div>

    <div class="order_last_actions">
        <a href="/my-orders" class="order_action_button">История заказов</a>
        <a href="/" class="order_action_button">Продолжить покупки</a>
    </div>
</div>

<div class="order_error hidden" id="error-modal-<?= $orderId ?>">
    <img class="error_modal_icon" src="/img/error_modal_icon.png">
    <div id="error-modal-text-<?= $orderId ?>"></div>
</div> 

<div class="registration_modal_blur" id="order-cancel-modal">
    <div class="account_delete_modal">
        <div class="account_delete_modal_entry_text">Вы уверены что хотите отменить заказ <?= $orderId ?>?</div>
        <div class="registration_modal_form">
            <div class="registration_modal_buttons">
                <button class="registration_modal_button" id="order-cancel-modal-exit">
                    Вернуться
                </button>

                <button class="registration_modal_button" id="order-cancel-modal-submit">
                    Отменить заказ
                </button>
            </div>
        </div>
    </div>
</div>