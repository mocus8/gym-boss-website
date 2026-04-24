<div class="container content-card">
    <h1 class="page-title">О сайте</h1>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        GymBoss - полнофункциональный интернет-магазин спортивных товаров, разработанный с нуля
        как full-stack веб-приложение. Проект создан для демонстрации навыков backend- и frontend-разработки,
        проектирования баз данных, работы с Docker, веб-серверами и внешними API.
    </p>

    <h2>Архитектура</h2>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        Сайт построен как многостраничное приложение (MPA) по паттерну MVC: собственный bootstrap,
        роутер с разбором URL, контроллеры и раздельные шаблоны представлений. Код организован
        по PSR-4 с использованием пространств имён и автозагрузки через Composer. Применяется
        Dependency Injection для слабой связанности компонентов. В системе реализовано разграничение
        доступа по ролям: гость, зарегистрированный пользователь, пользователь с подтверждённой почтой.
    </p>

    <h2>Технологический стек</h2>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li><b>Backend:</b> PHP (ООП, vanilla), MySQL, Composer.</li>
        <li><b>Frontend:</b> JavaScript (vanilla, ES6+ модули), HTML5, CSS3.</li>
        <li><b>Инфраструктура:</b> Docker, Docker Compose, Nginx + PHP-FPM.</li>
        <li><b>CI/CD:</b> GitHub Actions - автоматические проверки кода при push и pull request.</li>
        <li><b>Дизайн:</b> Figma.</li>
    </ul>

    <h2>Функциональность</h2>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li>Регистрация и авторизация пользователей, подтверждение email.</li>
        <li>Личный кабинет с возможностью редактирования и удаления аккаунта.</li>
        <li>Оформление и оплата заказов.</li>
        <li>Просмотр истории и отмена заказов.</li>
        <li>Работа с формами и валидация вводимых данных на клиенте и на сервере.</li>
    </ul>

    <h2>Frontend: вёрстка и доступность</h2>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        Вёрстка семантическая: используются корректные теги разметки (<code>header</code>, <code>main</code>,
        <code>nav</code>, <code>article</code>, <code>section</code>, <code>footer</code>).
        Интерфейс адаптивен под мобильные и десктопные устройства.
    </p>

    <h2>Базовые требования доступности (a11y)</h2>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li>Фокус и видимые состояния <code>:focus</code> для интерактивных элементов.</li>
        <li>Подписи к полям форм через <code>label</code>, связанные с инпутами.</li>
        <li>Атрибуты <code>alt</code> для содержательных изображений.</li>
        <li>Корректные ARIA-атрибуты и роли там, где это необходимо.</li>
    </ul>

    <h2>Интеграции с внешними сервисами</h2>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li><b>ЮKassa</b> - приём онлайн-платежей через официальный SDK.</li>
        <li><b>Yandex Maps API</b> - выбор пункта выдачи и адреса доставки на карте.</li>
        <li><b>DaData API</b> - подсказки и валидация адресов.</li>
        <li><b>Resend API</b> - транзакционные email-уведомления.</li>
        <li><b>Google reCAPTCHA v3</b> - защита критичных операций от ботов.</li>
    </ul>

    <h2>База данных</h2>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        Спроектирована реляционная схема MySQL: таблицы пользователей, товаров, заказов и связующие
        сущности. Используются индексы для ускорения частых запросов, транзакции для критичных
        операций (оформление заказа, изменение баланса), подготовленные запросы для защиты от
        SQL-инъекций.
    </p>

    <h2>Безопасность</h2>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        В проекте применяются современные практики безопасной веб-разработки:
    </p>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li>Строгая валидация входных данных на backend.</li>
        <li>Хэширование паролей.</li>
        <li>Защита от XSS, экранирование вывода.</li>
        <li>Content Security Policy (CSP).</li>
        <li>Безопасные cookie.</li>
        <li>Rate limiting на уровне Nginx.</li>
        <li>Транзакции БД и логирование с контекстом.</li>
        <li>Корректная обработка ошибок через try/catch и осмысленные HTTP-статусы.</li>
    </ul>

    <h2>SEO</h2>

    <ul class="flex-stack-row shape-cut-corners--diagonal">
        <li>Страницы политики конфиденциальности (составлена с учётом требований ФЗ-152 "О персональных данных") и контактов.</li>
        <li>Динамический <code>robots.txt</code>.</li>
        <li>Собственный скрипт генерации актуального <code>sitemap.xml</code>.</li>
        <li>Корректные meta-теги и заголовки страниц.</li>
    </ul>

    <h2>Исходный код и контакты</h2>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        Исходный код проекта доступен на
        <a href="https://github.com/mocus8/gym-boss-website" target="_blank" rel="noopener noreferrer">GitHub</a>.
    </p>

    <p class="flex-stack-row shape-cut-corners--diagonal">
        Связаться со мной:
        <a href="mailto:mocus8@gmail.com">mocus8@gmail.com</a>,
        <a href="https://api.whatsapp.com/send?phone=79167413418" target="_blank" rel="noopener noreferrer">WhatsApp</a>,
        <a href="https://t.me/mocus8" target="_blank" rel="noopener noreferrer">Telegram (@mocus8)</a>.
    </p>
</div>