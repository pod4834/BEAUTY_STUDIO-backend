FROM php:8.2-cli
RUN apt-get update && apt-get install -y git unzip libzip-dev libonig-dev libxml2-dev libpng-dev ca-certificates && docker-php-ext-install pdo_mysql mbstring zip gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
EXPOSE 10000
CMD php artisan config:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
