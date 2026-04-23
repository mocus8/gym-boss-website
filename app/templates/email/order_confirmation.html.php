<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Заказ оплачен и передан в обработку</title>
	</head>

	<body style="background-color: #030303;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center" style="padding: 40px 16px;">
                    <table width="560" cellpadding="0" cellspacing="0" border="0" style="
                        background-color: #221013;
                        border: 1px solid #6b1b29;
                        border-radius:4px;
                    ">
                        <tr>
                            <td style="padding: 40px; color: #f6f1f1;">
                                <p style="
                                    margin:0;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 22px;
                                    font-weight: 700;
                                    line-height: 1.2;
                                ">
                                    Заказ №<?= (int)$orderId ?>, GymBoss
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="
                                padding:24px 40px;
                                color: #f6f1f1; 
                                font-family: 'Jost', Arial, sans-serif;
                                font-size: 14px;
                                color: #b8abab;
                                line-height: 1.5;
                            ">
                                <p style="
                                    margin:0 0 16px;
                                    font-size: 16px;
                                    line-height: 1.5;
                                ">
                                    Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-size: 16px;
                                    line-height: 1.5;
                                ">
                                    Ваш заказ №<?= (int)$orderId ?> оплачен и принят в обработку.
                                </p>

                                <p style="margin:0 0 16px;">
                                    Состав заказа:
                                </p>

                                <?php foreach ($items as $item) { ?>
                                    <p style="margin:0 0 8px;">
                                        <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$item['quantity'] ?> шт.) - <?= (float)$item['price'] ?> ₽
                                    </p>
                                <?php } ?>

                                <p style="margin:0 0 16px;">
                                    Стоимость товаров: <?= (float)$itemsPrice ?> ₽
                                </p>

                                <?php if ($deliveryTypeCode === 'courier') { ?>
                                    <p style="margin:0 0 8px;">
                                        Стоимость доставки: <?= (float)$deliveryCost ?> ₽
                                    </p>

                                    <p style="margin:0 0 16px;">
                                        Итоговая стоимость заказа: <?= (float)$totalPrice ?> ₽
                                    </p>

                                    <p style="margin:0 0 8px;">
                                        Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p style="margin:0 0 8px;">
                                        Адрес доставки: <?= htmlspecialchars($deliveryAddressText, ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p style="margin:0 0 16px;">
                                        Примерный срок доставки: с <?= htmlspecialchars($deliveryFrom, ENT_QUOTES, 'UTF-8') ?> до <?= htmlspecialchars($deliveryTo, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php } elseif ($deliveryTypeCode === 'pickup') { ?>
                                    <p style="margin:0 0 16px;">
                                        Итоговая стоимость заказа: <?= (float)$totalPrice ?> ₽
                                    </p>

                                    <p style="margin:0 0 8px;">
                                        Тип доставки: <?= htmlspecialchars($deliveryTypeName, ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p style="margin:0 0 8px;">
                                        Выбранный для самовывоза магазин: <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <p style="margin:0 0 16px;">
                                        Примерный срок готовности заказа: с <?= htmlspecialchars($deliveryFrom, ENT_QUOTES, 'UTF-8') ?> до <?= htmlspecialchars($deliveryTo, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php } ?>

                                <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
                                    <tr>
                                        <td style="
                                            background-color: #8a1624;
                                            border: 1px solid #6b1b29;
                                            border-radius: 8px;
                                        ">
                                            <a href="<?= htmlspecialchars($orderUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                                display:inline-block;
                                                color: #f6f1f1;
                                                text-decoration:none;
                                                padding: 14px 28px;
                                                font-family: 'Jost', Arial, sans-serif;
                                                font-size: 16px;
                                            ">
                                                К заказу
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 16px;">
                                    Спасибо за покупку в GymBoss!
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="
                                padding: 24px 40px;
                                font-family: 'Jost', Arial, sans-serif;
                                font-size: 14px;
                                color: #b8abab;
                                line-height: 1.5;
                            ">
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