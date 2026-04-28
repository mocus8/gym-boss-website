#!/bin/bash

# Однократный скрипт настройки production-сервера.
# Требует sudo, наличия Docker и наличия .env файла.

set -e  # выйти при первой ошибке

echo "GymBoss Production Bootstrap"
echo ""

# Проверки окружения
if ! command -v docker &> /dev/null; then
    echo "Docker is not installed"
    exit 1
fi

if [ ! -f .env ]; then
    echo ".env file not found"
    echo "Copy .env.example to .env and fill production values first"
    exit 1
fi

# Storage permissions
echo "Setting up storage permissions..."
mkdir -p storage/logs storage/cache
sudo chown -R 82:82 storage
sudo chmod -R 775 storage

# Защитить .env
echo "Securing .env file..."
chmod 600 .env

# Создать папку для бэкапов
echo "Creating backups directory..."
mkdir -p ~/backups

# Настроить sudo NOPASSWD для certbot
echo "Configuring sudo for certbot auto-renewal..."

SUDO_FILE="/etc/sudoers.d/certbot-renewal"
if [ ! -f "$SUDO_FILE" ]; then
    echo "$USER ALL=(root) NOPASSWD: /usr/bin/certbot" | sudo tee "$SUDO_FILE" > /dev/null
    sudo chmod 440 "$SUDO_FILE"
    echo "Created $SUDO_FILE"
else
    echo "$SUDO_FILE already exists"
fi

# Проверка наличия SSL-сертификата
SSL_CERT_PATH="/etc/letsencrypt/live/gymboss.mocus8.ru/fullchain.pem"
if [ -f "$SSL_CERT_PATH" ]; then
    echo "SSL certificate found"
else
    echo "SSL certificate NOT found at $SSL_CERT_PATH"
    echo "Run certbot first to obtain a certificate."
    echo "See deploy/README.md for instructions."
    echo "Cron auto-renewal will be set up but won't work until certificate exists."
fi

# Установка cron-задач
echo "Installing cron jobs..."

if [ -f "deploy/cron.production" ]; then
    crontab deploy/cron.production
    echo "Cron jobs installed"
    echo "Current crontab:"
    crontab -l | grep -v "^#" | grep -v "^$" | sed 's/^/    /'
else
    echo "deploy/cron.production not found, skipping"
fi

echo ""
echo "Bootstrap complete"
echo ""
echo "Next steps:"
echo "  1. Get SSL certificate"
echo "  2. Start services: docker compose -f docker-compose.prod.yml up -d --build"