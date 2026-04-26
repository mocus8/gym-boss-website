# Deployment Guide

## Production setup

### Prerequisites

- VPS with Ubuntu 24.04
- Docker + Docker Compose installed
- Domain pointing to VPS IP
- SSL certificate via Let's Encrypt

### First deployment

1. Clone the repo:

```bash
   git clone https://github.com/mocus8/gym-boss-website.git
   cd gym-boss-website
```

2. Create `.env` from template:

```bash
   cp .env.example .env
   nano .env  # fill production values
```

3. Get SSL certificate (first time only):

```bash
   sudo certbot certonly --webroot -w /var/www/certbot -d gymboss.mocus8.ru
```

4. Start services:

```bash
   docker compose -f docker-compose.prod.yml up -d --build
```

### Update deployment

```bash
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

### Useful commands

- View logs: `docker compose -f docker-compose.prod.yml logs -f`
- Restart service: `docker compose -f docker-compose.prod.yml restart php`
- Backup database: `docker compose -f docker-compose.prod.yml exec mysql mysqldump -u root -p gymboss_db > backup.sql`
