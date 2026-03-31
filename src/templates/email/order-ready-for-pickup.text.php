Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.

Ваш заказ №<?= $orderId ?> готов к получению.

Состав заказа:
<?php foreach ($items as $item) { ?>
    <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['quantity'] ?> шт.)
<?php } ?>

Вы можете получить заказ в выбранном вами магазине: <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>.

Время работы магазина:
<?= htmlspecialchars($storeWorkHours, ENT_QUOTES, 'UTF-8') ?>

Ссылка на страницу заказа: <?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>

По вопросам получения заказа напишите нам: <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>

С уважением,
Разработчик GymBoss