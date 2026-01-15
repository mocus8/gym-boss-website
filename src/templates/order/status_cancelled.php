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

<div class="order_back">
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
                <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= number_format($item['price'] * $item['amount'], 2, ',', ' ') ?> ₽
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
                Стоимость доставки: <?= number_format((int)$deliveryCost, 2, ',', ' ') ?> ₽
            </div>
        <?php
        } 
        ?>
    </div>

    <div class="order_row">
        Итоговая стоимость: <?= number_format($orderTotalPrice + $deliveryCost, 2, ',', ' ') ?> ₽
    </div>

    <div class="order_title">
        Заказ отменен
    </div>

    <div class="order_last_actions">
        <a href="/my-orders" class="order_action_button">История заказов</a>
        <a href="/" class="order_action_button">Продолжить покупки</a>
    </div>
</div>