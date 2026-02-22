#!/bin/bash
set -e

# Set up Apache document root from environment variable
APACHE_DOCUMENT_ROOT="${APACHE_DOCUMENT_ROOT:-/var/www/app/public}"

sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Wait for MySQL to be ready (since we don't use service_healthy in depends_on)
if [ -n "$DB_HOST" ]; then
  echo "Waiting for MySQL at $DB_HOST to be ready..."
  for i in $(seq 1 30); do
    if mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
      echo "MySQL is ready."
      break
    fi
    echo "  ($i/30) MySQL not ready yet, retrying in 3s..."
    sleep 3
  done
fi

exec apache2-foreground
