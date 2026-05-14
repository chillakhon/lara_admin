#!/bin/bash
cd /var/www/html/laravel
while true; do
  php artisan schedule:run >> /dev/null 2>&1
  sleep 60
done
