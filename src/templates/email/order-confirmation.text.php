Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= $orderId ?> оплачен и принят в обработку.

Состав заказа:
<?php foreach ($orderItems as $item) { ?>
    <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['amount'] ?> шт.) - <?= $item['price'] ?>
<?php } ?>

Стоимость товаров: <?= $itemsPrice ?>
<?php if ($deliveryTypeCode === 'courier') { ?>
    Стоимость доставки: <?= $deliveryCost ?>
    Итоговая стоимость заказа: <?= $totalPrice ?>

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок доставки: с <?= $courierDeliveryFrom ?> до <?= $courierDeliveryTo ?>
<?php } elseif ($deliveryTypeCode === 'pickup') { ?>
    Итоговая стоимость заказа: <?= $totalPrice ?>

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Выбранный для самовывоза магазин: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок готовности заказа: с <?= $readyForPickupFrom ?> до <?= $readyForPickupTo ?>
<?php } ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

Спасибо за покупку в GymBoss!

С уважением,
Разработчик GymBoss