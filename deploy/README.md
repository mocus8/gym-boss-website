# Гайд для деплоя

## Настройка машины

### Требования

- VPS с Ubuntu 24.04
- Docker + Docker Compose
- Постоянный VPS IP и связанный домен
- SSL-сертификат через Let's Encrypt

### Первый деплой

1. Клонировать репозиторий:

```bash
   git clone https://github.com/mocus8/gym-boss-website.git
   cd gym-boss-website
```

2. Создать `.env` из шаблона:

```bash
   cp .env.example .env
   nano .env  # заполнить значениями
```

3. Выполнить bootstrap.sh:

```bash
   chmod +x bootstrap.sh && ./bootstrap.sh
```

4. Получить SSL-сертификат:

```bash
   sudo certbot certonly --webroot -w /var/www/certbot -d gymboss.mocus8.ru
```

5. Запустить сервисы:

```bash
   docker compose -f docker-compose.prod.yml up -d --build
```

### Обновить:

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
```
