FROM php:8.4-cli
RUN apt-get update && apt-get install -y git unzip libzip-dev libonig-dev libxml2-dev libpng-dev libicu-dev ca-certificates && docker-php-ext-install pdo_mysql mbstring zip gd intl bcmath
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs
EXPOSE 10000
CMD php artisan config:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
