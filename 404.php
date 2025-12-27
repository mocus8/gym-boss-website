<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="robots" content="noindex, nofollow">
		<title>
            Gym Boss - спорттовары
		</title>
        <link rel="icon" href="/public/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <!-- <div class="loading-text">Загрузка...</div> -->
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/src/templates/partials/header.php'; ?>
            <main class="main">
                <div class="button_return_position">
                    <a href="/">
                        <div class="button_return">
                            <div class="button_return_text">
                                На главную
                            </div>
                            <img class="button_return_img" src="/img/arrow_back.png">
                        </div>
                    </a>
                </div>
                <div class="error_back">
                    <div class="error_name">
                        Ошибка 404!
                    </div>
                    <div class="error_text">
                        Такой страницы уже/ещё не существует, пожалуйста, проверьте адрес и попробуйте еще раз.
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/src/templates/partials/footer.php';?>
        </div>
	</body>
</html>