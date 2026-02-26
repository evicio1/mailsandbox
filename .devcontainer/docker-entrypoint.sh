#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# 1. Configure Apache document root from environment variable
# ---------------------------------------------------------------------------
APACHE_DOCUMENT_ROOT="${APACHE_DOCUMENT_ROOT:-/var/www/app/public}"
sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# ---------------------------------------------------------------------------
# 2. Wait for MySQL to be healthy (safety net; depends_on: service_healthy
#    should already guarantee this, but the loop avoids edge cases)
# ---------------------------------------------------------------------------
if [ -n "$DB_HOST" ]; then
  echo "Waiting for MySQL at $DB_HOST..."
  for i in $(seq 1 30); do
    if mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; then
      echo "MySQL is ready."
      break
    fi
    echo "  ($i/30) MySQL not ready yet, retrying in 3s..."
    sleep 3
  done
fi

# ---------------------------------------------------------------------------
# 3. Laravel bootstrap (only runs if we're inside the app directory)
# ---------------------------------------------------------------------------
APP_DIR="/var/www/app"
if [ -f "$APP_DIR/composer.json" ] && [ "$SKIP_SETUP" != "true" ]; then
  cd "$APP_DIR"

  echo "==> Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader

  echo "==> Installing Node dependencies and building assets..."
  npm ci --prefer-offline
  npm run build

  echo "==> Ensuring .env exists..."
  if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --ansi
  fi

  echo "==> Running migrations..."
  php artisan migrate --force --no-interaction

  echo "==> Caching config..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  echo "==> Setting storage permissions..."
  mkdir -p storage/attachments
  chmod -R 777 storage bootstrap/cache
elif [ -f "$APP_DIR/composer.json" ]; then
  cd "$APP_DIR"
  echo "==> Skipping setup (SKIP_SETUP is true). Waiting for web container to build assets..."
  sleep 10
fi

# ---------------------------------------------------------------------------
# 4. Start Process (Apache or custom command)
# ---------------------------------------------------------------------------
if [ $# -gt 0 ]; then
  exec "$@"
else
  exec apache2-foreground
fi
