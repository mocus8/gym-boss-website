#!/bin/bash

# Однократный скрипт настройки production-сервера.
# Требует sudo, наличия Docker и наличия .env файла.

set -e  # выйти при первой ошибке

echo "=== GymBoss Production Bootstrap ==="

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

echo ""
echo "Bootstrap complete"
echo ""
echo "Next steps:"
echo "  1. Get SSL certificate"
echo "  2. Start services: docker compose -f docker-compose.prod.yml up -d --build"
# TODO: добавить cron-задачи, добавить их тут
echo "  3. Setup cron jobs: crontab -e and add jobs from deploy/cron.example"