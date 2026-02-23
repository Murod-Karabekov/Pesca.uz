#!/bin/bash
set -e

echo "⏳ Waiting for MySQL to be ready..."
until php -r "try { new PDO('mysql:host=database;port=3306;dbname=pesca', 'pesca', 'pesca_secret'); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    sleep 2
    echo "  ...still waiting for MySQL"
done
echo "✅ MySQL is ready!"

# Install dependencies if vendor is empty (first run with volume mount)
if [ ! -f vendor/autoload.php ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

echo "📦 Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Load fixtures if DB is empty (first run check)
USER_COUNT=$(php -r "
require 'vendor/autoload.php';
\$pdo = new PDO('mysql:host=database;port=3306;dbname=pesca', 'pesca', 'pesca_secret');
try {
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM user');
    echo \$stmt->fetchColumn();
} catch(Exception \$e) {
    echo '0';
}
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
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
