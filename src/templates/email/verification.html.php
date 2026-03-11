<?php
$userName = 'Жмышенко валерий';
$verifyUrl = 'http://localhost/src/templates/email/verification.html.php';
?>


<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Подтвердите адрес электронной почты</title>
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
                                    Подтвердите адрес электронной почты для GymBoss
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding:24px 40px;">
                                <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 16px;
                                ">
                                    Здравствуйте, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>.
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 16px;
                                ">
                                    Вы зарегистрировались на GymBoss. Осталось подтвердить ваш email-адрес.
                                </p>

                                <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
                                    <tr>
                                        <td style="
                                            background-color: #4b4b4b;
                                            border: 1px solid black;
                                            border-radius: 8px;
                                        ">
                                            <a href="<?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                                display:inline-block;
                                                color: black;
                                                text-decoration:none;
                                                padding: 14px 28px;
                                                font-family: Arial, sans-serif;
                                                font-size: 16px;
                                            ">
                                                Подтвердить email
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 14px;
                                ">
                                    Если кнопка не работает, перейдите по ссылке:
                                    <a href="<?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>" style="
                                        color: #250083;
                                        text-decoration: none;
                                    ">
                                        <?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 14px;
                                ">
                                    Ссылка действительна в течении 15 минут
                                </p>

                                <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 14px;
                                ">
                                    Если вы не регистрировались на GymBoss — просто проигнорируйте это письмо.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 24px 40px;">
                            <p style="
                                    margin:0 0 16px;
                                    font-family: Arial, sans-serif;
                                    font-size: 14px;
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