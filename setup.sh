#!/usr/bin/env bash

set -e

echo "🚀 Starting WAC Inventory Engine Setup..."

# 1. Environment file check
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
fi

# 2. Start Docker containers
echo "🐳 Starting Docker containers..."
docker compose up -d --build

# 3. Wait for MySQL database to be ready
echo "⏳ Waiting for MySQL database to initialize..."
until docker compose exec db mysqladmin ping -u root -proot_password --silent; do
    sleep 2
done

# 4. Install Composer dependencies
echo "📦 Installing Composer dependencies..."
docker compose exec app composer install --no-interaction --prefer-dist

# 5. Generate application key and JWT secret
echo "🔑 Generating application key and JWT secret..."
docker compose exec app php artisan key:generate --no-interaction
docker compose exec app php artisan jwt:secret --force --no-interaction

# 6. Run migrations & seeders
echo "🗄️ Running database migrations and seeders..."
docker compose exec app php artisan migrate:fresh --seed --no-interaction

# 7. Create test database & grant permissions
echo "🧪 Creating dedicated test database (wac_inventory_test)..."
docker compose exec db mysql -u root -proot_password -e "CREATE DATABASE IF NOT EXISTS wac_inventory_test;"
docker compose exec db mysql -u root -proot_password -e "GRANT ALL PRIVILEGES ON \`wac_inventory_test\`.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

echo "----------------------------------------------------"
echo "✅ WAC Inventory Engine Setup Complete!"
echo "----------------------------------------------------"
echo "• Web App API Base URL: http://localhost:8080/api"
echo "• Run Automated Tests:   docker compose exec app php artisan test"
echo "----------------------------------------------------"
