#!/bin/bash

# Скрипт для создания бэкапа бд и удаления старых бэкапов

set -e  # выйти при первой ошибке

DATE=$(date +%Y-%m-%d_%H-%M)
# Определяем корень проекта
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# Папка для бэкапов
BACKUP_DIR="$HOME/backups"
# Название создаваемого файла с бэкапом
BACKUP_FILE="$BACKUP_DIR/db_$DATE.sql.gz"

# Загружаем переменные из .env
source <(grep -E '^(DB_NAME|DB_USER|DB_PASS)=' "$PROJECT_ROOT/.env")

# Делаем дамп, сразу сжимаем
docker compose -f "$PROJECT_ROOT/docker-compose.prod.yml" exec -T mysql \
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    | gzip > "$BACKUP_FILE"

echo "Backup created: $BACKUP_FILE"

# Удаляем бэкапы старше 30 дней
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +30 -delete
echo "Old backups cleaned up"