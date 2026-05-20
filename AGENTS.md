# AGENTS.md

## Cursor Cloud specific instructions

### Service Overview

This is a Laravel 5.8 PHP API for Colombian electronic invoicing (DIAN). It runs via Docker Compose with three services: nginx, php (FPM), and mariadb.

### Running the Application

```bash
# Start all services (from /workspace)
sudo docker compose up -d

# Install PHP dependencies (if vendor/ is missing)
sudo docker compose exec -T php composer install --no-interaction --prefer-dist

# Fix storage permissions (required after fresh setup)
sudo docker compose exec -T php bash -c "mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/{cache,sessions,views} /var/www/html/storage/api-docs && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache"

# Run migrations and seed
sudo docker compose exec -T php php artisan migrate --force
sudo docker compose exec -T php php artisan db:seed --force
```

### Key Gotchas

1. **`public/index.php` does not exist in the repo** — the file is named `public/1index.php`. You must copy it: `cp public/1index.php public/index.php`
2. **The `docker/nginx/sites-available/` directory does not exist in the repo** — create it with a standard Laravel nginx config (see the file created in the workspace for reference).
3. **Storage permissions** — PHP-FPM runs as a different user; `chmod -R 777 storage/ bootstrap/cache/` is needed after fresh setup.
4. **PHPUnit tests fail** due to `header()` calls in `routes/api.php` (line 14). This is a pre-existing codebase issue, not a setup problem.
5. **Company registration API** returns 500 after successful creation due to a missing `type` column in the `software` table (referenced by an eager-load relationship). The record is created correctly in the database.
6. **Docker daemon** must be started manually: `sudo dockerd &>/tmp/dockerd.log &` — wait 2-3 seconds before running compose commands.

### Running Tests

```bash
# PHPUnit (inside PHP container)
sudo docker compose exec -T php php vendor/bin/phpunit

# PHP syntax lint (all app files)
sudo docker compose exec -T php bash -c "find app -name '*.php' -exec php -l {} \;"
```

### API Endpoints

- Swagger UI: `http://localhost:8000/api/ubl2.1/documentation`
- Listings (reference data): `GET http://localhost:8000/listings`
- Company Registration: `POST http://localhost:8000/api/ubl2.1/config/{nit}/{dv}`
- All authenticated endpoints require `Authorization: Bearer {token}` header

### Tech Stack

- PHP 7.3 (via Docker image `stenfrank/php:1.3`)
- Laravel 5.8
- MariaDB (port 3307 on host, 3306 inside Docker network)
- Nginx (port 8000 on host)
- Composer for PHP deps, npm for frontend assets (optional)
