#!/bin/bash

# If composer.json doesn't exist, create a new Laravel project
if [ ! -f "composer.json" ]; then
    composer create-project --prefer-dist laravel/laravel:^11.0 tmp
    # Move all files from tmp to current directory
    shopt -s dotglob
    mv tmp/* .
    rmdir tmp
    shopt -u dotglob
else
    composer install
fi

# Install npm dependencies if package.json exists
if [ -f "package.json" ]; then
    npm install
    npm run dev
fi

php artisan key:generate

php artisan config:cache

# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Start PHP-FPM
php-fpm
