Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= $orderId ?> передан в доставку.

Состав заказа:
<?php foreach ($orderItems as $item) { ?>
    <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['amount'] ?> шт.)
<?php } ?>

Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

Примерный срок доставки: с <?= $courierDeliveryFrom ?> до <?= $courierDeliveryTo ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

По вопросам доставки напишите нам: <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>

С уважением,
Разработчик GymBoss