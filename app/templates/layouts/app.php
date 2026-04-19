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
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= htmlspecialchars($title ?? 'Gym Boss - спорттовары', ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="/assets/css/app.css">
	</head>

	<body
        class="loading"
        aria-busy="true"
        data-yandex-maps-key="<?= htmlspecialchars($servicesConfig['yandex_maps']['key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        data-recaptcha-site-key="<?= htmlspecialchars($servicesConfig['recaptcha']['site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
    >

        <!-- Базовый skip-link для перехода сразу к содежимому минуя header итд -->
        <a class="skip-link shape-cut-corners--diagonal" href="#main-content">
            Пропустить навигацию и перейти к содержимому
        </a>

        <div id="loader" class="loader-overlay flex-center" role="status">
            <img class="loader-overlay__spinner" src="/assets/images/ui/loader.png" alt="">
            <span class="visually-hidden">Страница загружается</span>
        </div>

        <div class="site-layout">
            <?php require_once __DIR__ . '/../partials/header.php'; ?>

            <main id="main-content">
                <?= $content ?? '' ?>
            </main>

            <?php require_once __DIR__ . '/../partials/footer.php'; ?>
        </div>

        <!-- Tost-уведомление -->
        <?php require __DIR__ . '/../partials/notification.php' ?>

        <!-- Универсальная модалка подтверждения -->
        <?php require __DIR__ . '/../partials/modals/confirmation_modal.php' ?>
        
        <!-- Модалка входа/регистрации -->
        <?php require __DIR__ . '/../partials/modals/auth_modal.php' ?>

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
	</body>
</html>