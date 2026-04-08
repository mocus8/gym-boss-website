Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= (int)$orderId ?> оплачен и принят в обработку.

Состав заказа:
<?php foreach ($items as $item) { ?>
    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$item['quantity'] ?> шт.) - <?= (float)$item['price'] ?> ₽
<?php } ?>

Стоимость товаров: <?= (float)$itemsPrice ?>
<?php if ($deliveryTypeCode === 'courier') { ?>
    Стоимость доставки: <?= (float)$deliveryCost ?> ₽
    Итоговая стоимость заказа: <?= (float)$totalPrice ?> ₽

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок доставки: с <?= htmlspecialchars($deliveryFrom, ENT_QUOTES, 'UTF-8') ?> до <?= htmlspecialchars($deliveryTo, ENT_QUOTES, 'UTF-8') ?>
<?php } elseif ($deliveryTypeCode === 'pickup') { ?>
    Итоговая стоимость заказа: <?= (float)$totalPrice ?> ₽

    Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
    Выбранный для самовывоза магазин: <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>

    Примерный срок готовности заказа: с <?= htmlspecialchars($deliveryFrom, ENT_QUOTES, 'UTF-8') ?> до <?= htmlspecialchars($deliveryTo, ENT_QUOTES, 'UTF-8') ?>
<?php } ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

Спасибо за покупку в GymBoss!

С уважением,
Разработчик GymBoss