<?php
declare(strict_types=1);

// Главный шаблон сайта (приложения) - layout

// Определяем нужна ли индексация (если не указано то индексируем)
$robots = $robots ?? 'index,follow';

// Определяем каноникл страницы
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$defaultCanonical = $baseUrl . $path;
$canonical = $canonical ?? $defaultCanonical;

// Получаем flash сообщение из сессии если оно там есть
$flashMessage = $flash->get();
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
        <meta name="robots" content="<?= htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') ?>">
		<title>
            <?= htmlspecialchars($title ?? 'Gym Boss - спорттовары', ENT_QUOTES, 'UTF-8') ?>
        </title>
        <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/assets/css/app.css">
	</head>

	<body class="body is-loading" aria-busy="true"
        data-yandex-maps-key="<?= htmlspecialchars($servicesConfig['yandex_maps']['key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        data-recaptcha-site-key="<?= htmlspecialchars($servicesConfig['recaptcha']['site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
    >
        <div class="loader-overlay" id="loader">
            <img class="loader" src="/assets/images/ui/loader.png" alt="">
            <span class="sr-only">Страница загружается</span>
        </div>

        <div class="desktop">
            <?php require_once __DIR__ . '/../partials/header.php'; ?>
            <main class="main">
                <?= $content ?? '' ?>
            </main>
            <?php require_once __DIR__ . '/../partials/footer.php'; ?>
        </div>

        <!-- Tost-уведомление -->
        <div class="notification hidden" id="notification">
            <button class="notification_close_btn" id="notification-close-btn">✕</button>

            <div class="notification_top">
                <img class="notification_icon"src="/assets/images/ui/inf.png">
                <div class="notification_text" id="notification-text"></div>
            </div>
            
            <div class="notification_progress">
                <div class="notification_text_progress_fill" id="notification-progress-fill"></div>
            </div>
        </div>

        <!-- Подключаем разные скрипты -->

        <!-- Обязательные для всех страниц -->
        <script defer src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($servicesConfig['recaptcha']['site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></script>
        <script defer src="/assets/js/loader.js"></script>
        <script type="module" src="/assets/js/ui/flash-notifications.js"></script>
        <script type="module" src="/assets/js/ui/auth-modal.js"></script>
        <script type="module" src="/assets/js/header.js"></script>
        
        <!-- Данные для флеш-уведомления, если оно есть -->
        <?php if ($flashMessage !== null) { ?>
            <script id="server-flash" type="application/json">
                <?= json_encode(
                    ['message' => $flashMessage],
                    JSON_UNESCAPED_UNICODE
                    | JSON_HEX_TAG
                    | JSON_HEX_AMP
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                ) ?>
            </script>
        <?php } ?>

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

        <!-- Универсальная модалка подтверждения -->
        <?php require __DIR__ . '/../partials/modals/confirmation_modal.php' ?>
        <!-- Модалка входа/регистрации -->
        <?php require __DIR__ . '/../partials/modals/auth_modal.php' ?>
	</body>
</html>