#!/bin/bash
set -e

# Parse DB credentials from environment variables
DB_HOST="database"
DB_PORT="3306"
DB_NAME="${MYSQL_DATABASE:-pesca}"
DB_USER="${MYSQL_USER:-pesca}"
DB_PASS="${MYSQL_PASSWORD:-pesca_secret}"

echo "⏳ Waiting for MySQL to be ready..."
until php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}'); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    sleep 2
    echo "  ...still waiting for MySQL"
done
echo "✅ MySQL is ready!"

# Install dependencies if vendor is empty (first run with volume mount)
if [ ! -f vendor/autoload.php ]; then
    echo "📦 Installing Composer dependencies..."
    if [ "${APP_ENV}" = "prod" ]; then
        composer install --no-interaction --no-dev --optimize-autoloader
    else
        composer install --no-interaction --optimize-autoloader
    fi
fi

echo "📦 Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Load fixtures if DB is empty (first run check)
USER_COUNT=$(php -r "
\$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}', '${DB_USER}', '${DB_PASS}');
try {
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM user');
    echo \$stmt->fetchColumn();
} catch(Exception \$e) {
    echo '0';
}
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ] && [ "${APP_ENV}" != "prod" ]; then
    echo "🌱 Loading fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction --append || true
fi

echo "🔧 Clearing cache..."
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "📁 Ensuring upload directories exist..."
mkdir -p public/uploads/products public/uploads/tailors
chown -R www-data:www-data public/uploads var

echo "🚀 Starting PHP-FPM..."
exec php-fpm
