# GymBoss - Full-stack интернет-магазин

Учебный pet-project: интернет-магазин спортивных товаров, написанный с нуля на PHP и JS без фреймворков.
Проект прошёл полный рефакторинг из монолита независимых страниц в структурированное MPA на базе MVC.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Nginx](https://img.shields.io/badge/Nginx-configured-009639?logo=nginx&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)

## Стек

**Backend:** PHP 8 (ООП), собственный MVC-фреймворк, роутер, front controller, DI, PSR-4, Composer
**Frontend:** vanilla JS (ES6+, модули), HTML5, CSS (адаптив)
**База данных:** MySQL 8 (схема, индексы, оптимизация запросов)
**Инфраструктура:** Nginx + PHP-FPM, Docker / Docker Compose, GitHub Actions (CI)
**Внешние сервисы:** ЮKassa, Яндекс.Карты, DaData, Resend, Google reCAPTCHA v3

## Архитектура

Приложение - MPA на базе MVC с единой точкой входа через front controller.
Бизнес-логика разделена по доменам (Cart, Orders, Payments, Auth), каждый домен
содержит собственные сервисы и репозитории.

## Скриншоты

<p align="center">
  <img src="docs/screenshots/home.png" width="48%" alt="Главная страница">
  &nbsp;
  <img src="docs/screenshots/cart.png" width="48%" alt="Корзина">
</p>

<p align="center">
  <img src="docs/screenshots/checkout.png" width="48%" alt="Оформление заказа">
  &nbsp;
  <img src="docs/screenshots/order.png" width="48%" alt="Заказ">
</p>

**Основные паттерны и решения:**

- **Front controller + Router** - все запросы проходят через `public/index.php`
  и маршрутизируются на нужный контроллер
- **Repository pattern** - работа с БД инкапсулирована в репозиториях,
  контроллеры и сервисы не знают про SQL
- **Use-cases** для сложных операций над заказами
  (`CancelOrderUseCase`, `FulfillOrderUseCase`)
- **Слой интеграций** - каждый внешний сервис (ЮKassa, DaData, Resend, reCAPTCHA)
  изолирован в отдельный Gateway/Client с собственными DTO
- **Пошаговые SQL-миграции** в папке `db/` - 70+ пронумерованных файлов,
  отражающих эволюцию схемы

### Структура проекта

```
gym-boss-website/
├── public/
│ ├── index.php # front controller - единая точка входа
│ └── assets/ # статика: CSS, JS, изображения, шрифты
├── bootstrap/
│ └── app.php # инициализация приложения
├── config/ # конфигурации (app, services, delivery)
├── app/
│ ├── Api/ # REST-контроллеры (Auth, Cart, Order, Product, ...)
│ ├── Auth/ # сервисы и репозитории авторизации
│ ├── Cart/ # корзина: сервис, репозитории, сессия
│ ├── Orders/ # заказы: сервис, repositories, use-cases
│ ├── Payments/ # платежи, webhook, синхронизация статусов
│ ├── Products/ # каталог товаров
│ ├── Stores/ # пункты самовывоза
│ ├── Users/ # репозиторий пользователей
│ ├── Mail/ # отправка email
│ ├── Integrations/ # внешние сервисы
│ │ ├── Yookassa/ # - приём платежей
│ │ ├── Dadata/ # - подсказки адресов
│ │ ├── Resend/ # - email
│ │ └── GoogleRecaptcha/ # - защита форм
│ ├── Db/ # обёртка над PDO
│ ├── Support/ # helpers, Logger, Flash, AppException
│ ├── page-scripts/ # контроллеры страниц (MVC-controllers)
│ └── templates/ # шаблоны (layouts, pages, partials, email)
├── db/ # SQL-миграции
├── docker/ # конфигурация Nginx / PHP-FPM
├── storage/ # логи и кэш
├── .github/workflows/ # CI (GitHub Actions)
├── Dockerfile
├── docker-compose.yml
├── nginx.conf
└── composer.json
```

### Поток запроса

1. Nginx принимает запрос и проксирует на PHP-FPM → `public/index.php`
2. Bootstrap инициализирует автозагрузку (Composer PSR-4), конфигурацию, DI
3. Роутер сопоставляет URL → контроллер → метод
4. Контроллер вызывает сервис → репозиторий (БД) или интеграцию (внешний API)
5. Результат рендерится в шаблон через `app/templates/` и возвращается клиенту

## Функциональность

### Для пользователя

- Регистрация и авторизация, подтверждение email
- Каталог товаров, корзина
- Оформление заказа с выбором адреса доставки или пункта самовывоза на карте
- Онлайн-оплата через ЮKassa
- Личный кабинет: просмотр и отмена заказов, редактирование и удаление аккаунта

### Роли и доступы

Реализовано разграничение прав: **гость** -> **новый пользователь** (регистрация без подтверждения) -> **пользователь с подтверждённой почтой**. Критичные действия (оформление заказа, оплата) доступны только последним.

### Интеграции с внешними сервисами

- **ЮKassa** - приём онлайн-платежей
- **Яндекс.Карты** - выбор адреса доставки и пункта самовывоза
- **DaData** - автоподсказки адресов при вводе
- **Resend** - транзакционные email-уведомления
- **Google reCAPTCHA v3** - защита форм от ботов

### Безопасность

- Строгая серверная валидация всех входных данных, ограничения на фронте как дополнение
- Хэширование паролей (`password_hash`)
- Защита от XSS (экранирование при выводе).
- Content Security Policy в HTTP-заголовках
- HttpOnly, Secure, SameSite cookie
- Rate limiting на уровне Nginx с отдельными зонами для чувствительных эндпоинтов (логин, регистрация)
- reCAPTCHA v3 на критичных действиях
- Транзакции и try/catch на всех операциях с БД, структурированное логирование с контекстом
- Корректные HTTP-статусы в ответах

### SEO и сервисные страницы

- Динамический `robots.txt`
- Скрипт генерации актуальной `sitemap.xml`
- Политика конфиденциальности, составленная с учётом требований ФЗ-152

## Установка и запуск

Для установки нужны git и Docker.

Шаги установки:

1. Клонировать репозиторий:  
   `git clone https://github.com/mocus8/gym-boss-website.git`  
   `cd gym-boss-website`
2. Отредактировать файл окружения `.env.example` (тестовые ключи можно получить на соответствующих сайтах).
3. Создать и запустить Docker‑контейнеры:  
   `docker compose up -d --build`

После этого сайт будет локально доступен по указанному в `.env` адресу.

## CI

При каждом push и pull request GitHub Actions запускает базовые проверки кода
(линтер PHP, проверка синтаксиса). Конфигурация в `.github/workflows/`.

## Что реализовано и что в планах

**Реализовано:**

- Рефакторинг монолита в MVC/MPA с собственным роутером и DI-контейнером
- Регистрация, авторизация, подтверждение email, роли
- Корзина, оформление и оплата заказов, личный кабинет
- Интеграции с ЮKassa, Яндекс.Картами, DaData, Resend, reCAPTCHA
- Развёртывание в Docker, конфигурация Nginx с CSP и rate limiting
- CI через GitHub Actions

**В планах:**

- Публичная демо-версия на VPS
- Аудит и улучшение уровня a11y через lighthose в devtools
- Дополнительная настройка логаутов

## Чему научился на проекте

- Проектирование архитектуры MVC с нуля: front controller, роутер, контроллеры, слой моделей, DI
- Работа с Composer и PSR-4, организация namespaces
- Проектирование схемы БД, оптимизация запросов
- Настройка Nginx + PHP-FPM, CSP, rate limiting, безопасные cookie
- Интеграция со сторонними REST API, обработка ошибок сетевых запросов
- Работа с Docker и Docker Compose, построение локального окружения
- Настройка CI через GitHub Actions, ведение репозитория по GitHub Flow

_Учебный pet-project, разрабатывается одним человеком._
