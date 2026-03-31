Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= $orderId ?> передан в доставку.

Состав заказа:
<?php foreach ($items as $item) { ?>
    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['quantity'] ?> шт.)
<?php } ?>

Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>

Примерный срок доставки: с <?= $deliveryFrom ?> до <?= $deliveryTo ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

По вопросам доставки напишите нам: <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>

С уважением,
Разработчик GymBoss