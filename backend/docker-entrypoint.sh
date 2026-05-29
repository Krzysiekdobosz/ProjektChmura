#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
MAX_TRIES=60
TRIES=0
until mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --skip-ssl --connect-timeout=2 -e "SELECT 1" > /dev/null 2>&1; do
    TRIES=$((TRIES + 1))
    if [ "$TRIES" -ge "$MAX_TRIES" ]; then
        echo "MySQL did not become ready in time. Exiting."
        exit 1
    fi
    echo "MySQL not ready yet (attempt $TRIES/$MAX_TRIES), waiting 2 seconds..."
    sleep 2
done
echo "MySQL is ready."

echo "Clearing config cache..."
php artisan config:clear

echo "Running migrations..."
php artisan migrate --force --no-interaction

if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force --no-interaction
fi

echo "Starting Apache..."
exec apache2-foreground
