# Гайд по деплою на production

## Требования

- VPS с Ubuntu 24.04 LTS
- Минимум 2 GB RAM, 20 GB SSD
- Открытые порты: 22 (SSH), 80 (HTTP), 443 (HTTPS)
- Зарегистрированный домен с A-записью на IP сервера

## Подготовка сервера

### Базовая безопасность

1. Отключить root-логин по SSH:

```bash
   sudo nano /etc/ssh/sshd_config
   # Установить: PermitRootLogin no, PasswordAuthentication no
   sudo systemctl restart sshd
```

2. Создать непривилегированного пользователя:

```bash
   sudo adduser deploy
   sudo usermod -aG sudo deploy
```

3. Настроить UFW:

```bash
   sudo ufw allow 22/tcp
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
```

### Установка Docker

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# Перезайти, чтобы права применились
```

## Первый деплой

1. Клонировать репозиторий:

```bash
   git clone https://github.com/mocus8/gym-boss-website.git
   cd gym-boss-website
```

2. Создать `.env` из шаблона:

```bash
   cp .env.example .env
   nano .env
```

Сгенерировать секретные значения:

```bash
   openssl rand -hex 24  # для MYSQL_ROOT_PASSWORD, DB_PASS, ENCRYPTION_KEY
```

3. Выполнить bootstrap-скрипт (настройка прав, директорий):

```bash
   chmod +x deploy/bootstrap.sh
   ./deploy/bootstrap.sh
```

4. Получить SSL-сертификат (bootstrap-режим Nginx):

    Создать временный конфиг `nginx.bootstrap.conf` с обработкой ACME challenge,
    запустить минимальный nginx, выполнить:

```bash
   sudo certbot certonly --webroot \
     -w /var/lib/docker/volumes/gym-boss-website_certbot_webroot/_data \
     -d yourdomen.com \
     --email your@email.com \
     --agree-tos
```

5. Запустить production-сервисы:

```bash
   docker compose -f docker-compose.prod.yml up -d --build
```

6. Сгенерировать первичный sitemap.xml через скрипт (далее автоматически через cron):

```bash
   docker compose -f /home/mocus8/gym-boss-website/docker-compose.prod.yml exec -T php php /var/www/html/scripts/generate_sitemap.php
```

## Обновление кода

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

Что пересобирается:

- При изменении PHP-кода — образ `php`
- При изменении файлов в `public/` — образ `nginx`
- При изменении `nginx.prod.conf` — достаточно `restart nginx`

## Структура production-окружения

- **3 Docker-контейнера**: nginx, php-fpm, mysql
- **3 named volumes**: mysql_data (БД), php_sessions (сессии), certbot_webroot (SSL challenge)
- **bind-mount**: `./storage` (логи, кэш приложения)
- **Сеть**: `gymboss` (изолированная Docker-network)
- **MySQL**: bind на 127.0.0.1:3306 (недоступен из интернета, только через Docker network или SSH-туннель)
- **Cron-задачи**: устанавливаются автоматически через `bootstrap.sh`, для ручного применения: `crontab deploy/cron.production`, для просмотра: `crontab -l`. Текущие задачи: _SSL auto-renewal_ - ежедневно в 2:00, перезагружает nginx при обновлении; _BD backups_ - каждый день в 3:00 делает бэкап бд, удаляет устарвешие бэкапы; _Sync payment statuses_ - ежедневно в 4:00 синхронизируются статусы заказов и платежей; _Generate sitemap_ - ежедневно в 4:05 заново генерируется актуальная sitemap.xml; _Clean carts_ - в 4:10 очищаются старые гостевые корзины; _Clean login attempts_ - ежедневно в 4:15 очищаются старые попытки входа.
