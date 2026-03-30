<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Заказ передан в доставку</title>
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
                                    Ваш заказ №<?= $orderId ?> передан в доставку.
                                </p>

                                <p style="margin:0 0 16px;">
                                    Состав заказа:
                                </p>

                                <?php foreach ($orderItems as $item) { ?>
                                    <p style="margin:0 0 8px;">
                                        <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $item['quantity'] ?> шт.)
                                    </p>
                                <?php } ?>

                                <p style="margin:0 0 8px;">
                                    Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <p style="margin:0 0 16px;">
                                    Примерный срок доставки: с <?= $courierDeliveryFrom ?> до <?= $courierDeliveryTo ?>
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

                                <p style="margin:0 0 16px;">
                                    По вопросам доставки напишите нам:
                                    <a href="mailto:<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>" style="
                                        color: #250083;
                                        text-decoration: none;
                                    ">
                                        <?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </p>
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