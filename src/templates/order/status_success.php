<?php require_once __DIR__ . '/../../src/templates/partials/header.php'; ?>

<main class="main">
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

        <div>
            <?php
            if ($orderDetails['delivery_type'] === 'pickup' && $orderDetails['store_name']) { 
            ?>  
                <div class="order_row">
                    Заказ может быть готов к получению с <?= date('H:i', strtotime($paidAt . ' +1 hour')) ?> до <?= date('H:i', strtotime($paidAt . ' +3 hour')) ?>, для уточнения звоните менеджеру 
                    <a href='tel: <?= htmlspecialchars($orderDetails['phone']) ?>' class="colour_href">
                        <?= htmlspecialchars($orderDetails['phone']) ?>
                    </a>
                </div>
            <?php
            } else if ($orderDetails['delivery_type'] === 'delivery' && $orderDetails['delivery_address']) { 
            ?>
                <div class="order_row">
                    Ориентировочная дата доставки: с <?= date('d.m.Y', strtotime($paidAt . ' +1 day')) ?> до <?= date('d.m.Y', strtotime($paidAt . ' +2 days')) ?>
                </div>

                <div class="order_row">
                    Курьер свяжеться с вами по телефону
                </div>
            <?php
            } 
            ?>
        </div>

        <div class="order_row">
            Письмо с чеком будет отправлено на вашу почту
        </div>

        <div class="order_title">
            Заказ успешно оформлен и оплачен!
        </div>

        <div class="order_title">
            Спасибо за ваш заказ!
        </div>

        <div class="order_last_actions">
            <a href="/my-orders" class="order_action_button">История заказов</a>
            <a href="/" class="order_action_button">Продолжить покупки</a>
        </div>

        <div class="order_refund_info">
            Для возврата заказа свяжитесь с менеджером (<a href='tel: +70000000000' class="colour_href">+7 000 000 00 00</a>). Вернуть можно только заказ с момента получения которого прошло менее 14 дней
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../src/templates/partials/footer.php';?>