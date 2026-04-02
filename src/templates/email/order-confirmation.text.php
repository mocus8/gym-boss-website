Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= $orderId ?> оплачен и принят в обработку.

Состав заказа:
<?php foreach ($items as $item) { ?>
    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['quantity'] ?> шт.) - <?= $item['price'] ?> ₽
<?php } ?>

Стоимость товаров: <?= $itemsPrice ?>
<?php if ($deliveryTypeCode === 'courier') { ?>
    Стоимость доставки: <?= $deliveryCost ?> ₽
    Итоговая стоимость заказа: <?= $totalPrice ?> ₽

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок доставки: с <?= $deliveryFrom ?> до <?= $deliveryTo ?>
<?php } elseif ($deliveryTypeCode === 'pickup') { ?>
    Итоговая стоимость заказа: <?= $totalPrice ?> ₽

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Выбранный для самовывоза магазин: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок готовности заказа: с <?= $deliveryFrom ?> до <?= $deliveryTo ?>
<?php } ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

Спасибо за покупку в GymBoss!

С уважением,
Разработчик GymBoss