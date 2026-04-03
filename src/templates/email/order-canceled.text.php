Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= (int)$orderId ?> был отменён.

Состав заказа:
<?php foreach ($items as $item) { ?>
    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$item['quantity'] ?> шт.) - <?= (float)$item['price'] ?> ₽
<?php } ?>

Сумма заказа: <?= (float)$itemsPrice ?> ₽

<?php if ($canceledBy === 'user') { ?>
    Заказ был отменён по вашему запросу
<?php } elseif ($canceledBy === 'provider') { ?>
    Ваш заказ был отменён со стороны магазина. Приносим извинения за неудобства.
<?php } ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

С уважением,
Разработчик GymBoss