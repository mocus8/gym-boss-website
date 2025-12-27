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
    Ваши заказы:
</div>
<div class="orders_list">
    <?php if (empty($ordersInfo)) { ?>
        <div class="cart_empty">
            У вас еще нет заказов
        </div>
    <?php } else {
        foreach ($ordersInfo as $order) { ?>
            <div class="order" data-order-id="<?= $order['order_id'] ?>">
                <div class="order_number">
                    Заказ <?= '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?> (оформлен <?= date('d.m.Y', strtotime($order['created_at'])) ?>)
                </div>

                <div class="order_data">
                    Стоимость: <?= $order['total_price'] ?> ₽
                </div>

                <div class="order_data">
                    Способ получения: <br>
                    <?= $order['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
                </div>
                
                <?php if ($order['delivery_type'] === 'pickup' && $order['store_address']) { ?>  
                    <div class="order_data_address">
                        Пункт выдачи: <?= $order['store_address'] ?>
                    </div>
                <?php } else if ($order['delivery_type'] === 'delivery' && $order['delivery_address']) { ?>
                    <div class="order_data_address">
                        Адрес доставки: <?= $order['delivery_address'] ?>
                    </div>
                <?php } ?>

                <div class="order_data" data-field="status">
                    Статус заказа: <br>
                    <?= match($order['status']) {
                        'cart' => 'корзина',
                        'paid' => 'оплачен', 
                        'pending_payment' => 'ожидает оплаты',
                        'cancelled' => 'отменён',
                        'refund' => 'возврат',
                        default => $order['status']
                    } ?> 
                </div>

                <a href="/order/<?= htmlspecialchars($order['order_id']) ?>">
                    <div class="order_button">
                        Перейти к заказу
                    </div>
                </a>
            </div>
        <?php } ?>
    <?php } ?>
</div>