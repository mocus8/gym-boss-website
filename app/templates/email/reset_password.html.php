<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Сброс пароля GymBoss</title>
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
                                    Сброс пароля для GymBoss
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:24px 40px; color: #f6f1f1;">
                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 16px;
                                    line-height: 1.5;
                                ">
                                    Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 16px;
                                    line-height: 1.5;
                                ">
                                    Вы запросили сброс пароля для вашего аккаунта GymBoss.
                                </p>

                                <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
                                    <tr>
                                        <td style="
                                            background-color: #8a1624;
                                            border: 1px solid #6b1b29;
                                            border-radius: 8px;
                                        ">
                                            <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                                display:inline-block;
                                                color: #f6f1f1;
                                                text-decoration:none;
                                                padding: 14px 28px;
                                                font-family: 'Jost', Arial, sans-serif;
                                                font-size: 16px;
                                            ">
                                                Сбросить пароль
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 14px; color: #b8abab;
                                    line-height: 1.5;
                                ">
                                    Если кнопка не работает, перейдите по ссылке:
                                    <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                        color: #8a1624;
                                        text-decoration: none;
                                    ">
                                        <?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 14px; color: #b8abab;
                                    line-height: 1.5;
                                ">
                                    Ссылка действительна в течение 15 минут
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 14px; color: #b8abab;
                                    line-height: 1.5;
                                ">
                                    Если вы не запрашивали сброс пароля — просто проигнорируйте это письмо.
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
                                <p style="
                                    margin:0 0 16px;
                                    font-family: 'Jost', Arial, sans-serif;
                                    font-size: 14px; color: #b8abab;
                                    line-height: 1.5;
                                ">
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