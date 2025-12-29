<?php
// Главный шаблон сайта (приложения) - layout

// Определяем нужна ли индексация (если не указано то индексируем)
$robots = $robots ?? 'index,follow';

// Определяем каноникл страницы
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$defaultCanonical = $baseUrl . $path;
$canonical = $canonical ?? $defaultCanonical;
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="robots" content="<?= htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') ?>">
		<title>
            <?= htmlspecialchars($title ?? 'Gym Boss - спорттовары', ENT_QUOTES, 'UTF-8') ?>
        </title>
        <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
        <link rel="icon" href="/public/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/styles.css">
	</head>

	<body class="body" data-yandex-maps-key="<?= getenv('YANDEX_MAPS_KEY') ?>">
        <div class="loader-overlay" id="loader">
            <img class="loader" src="/img/loader.png" alt="Загрузка">
        </div>

        <div class="desktop">
            <?php require_once __DIR__ . '/../partials/header.php'; ?>
            <main class="main">
                <?= $content ?? '' ?>
            </main>
            <?php require_once __DIR__ . '/../partials/footer.php'; ?>
        </div>

        <!-- Подключаем разные скрипты -->

        <!-- Обязательные для всех страниц -->
        <script defer src="/js/loader.js"></script>
        <script defer src="/js/modals.js"></script>
        <script defer src="https://www.google.com/recaptcha/api.js?render=<?= getenv('GOOGLE_RECAPTCHA_SITE_KEY') ?>"></script>
        <script defer src="/js/inputmask.min.js"></script>

        <!-- Внешние и обычные из контроллера -->
        <?php if (!empty($pageScripts) && is_array($pageScripts)) { ?>
            <?php foreach ($pageScripts as $script) { ?>
                <script defer src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
            <?php } ?>
        <?php } ?>

        <!-- Модульные из контроллера -->
        <?php if (!empty($pageModuleScripts) && is_array($pageModuleScripts)) { ?>
            <?php foreach ($pageModuleScripts as $script) { ?>
                <script type="module" src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>"></script>
            <?php } ?>
        <?php } ?>
	</body>
</html>