<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Отмена заказа</title>
	</head>

	<body>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center" style="padding: 40px 16px;">
                    <table width="560" cellpadding="0" cellspacing="0" border="0" style="
                        background-color: #bd1b1b;
                        border-radius:8px;
                        overflow:hidden;
                    ">
                        <tr>
                            <td style="padding: 40px;">
                                <p style="
                                    margin:0;
                                    font-family: Arial, sans-serif;
                                    font-size: 22px;
                                    font-weight: 700;
                                ">
                                    Заказ №<?= $orderId ?>, GymBoss
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:24px 40px; font-family: Arial, sans-serif; font-size: 14px;">
                                <p style="
                                    margin:0 0 16px;
                                    font-size: 16px;
                                ">
                                    Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-size: 16px;
                                ">
                                    Ваш заказ №<?= $orderId ?> был отменён.
                                </p>

                                <p style="margin:0 0 16px;">
                                    Состав заказа:
                                </p>

                                <?php foreach ($items as $item) { ?>
                                    <p style="margin:0 0 8px;">
                                        <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['quantity'] ?> шт.) - <?= $item['price'] ?> ₽
                                    </p>
                                <?php } ?>

                                <p style="margin:8px 0 16px 0;">
                                    Сумма заказа: <?= $itemsPrice ?> ₽
                                </p>

                                <p style="margin:0 0 16px;">
                                    <?php if ($canceledBy === 'user') { ?>
                                        Заказ был отменён по вашему запросу.
                                    <?php } elseif ($canceledBy === 'provider') { ?>
                                        Ваш заказ был отменён со стороны магазина. Приносим извинения за неудобства.
                                    <?php } ?>
                                </p>

                                <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
                                    <tr>
                                        <td style="
                                            background-color: #4b4b4b;
                                            border: 1px solid black;
                                            border-radius: 8px;
                                        ">
                                            <a href="<?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                                display:inline-block;
                                                color: black;
                                                text-decoration:none;
                                                padding: 14px 28px;
                                                font-size: 16px;
                                            ">
                                                К заказу
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 24px 40px; font-family: Arial, sans-serif; font-size: 14px;">
                                <p style="margin:0 0 16px;">
                                    С уважением,<br>Разработчик GymBoss
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
	</body>
</html>