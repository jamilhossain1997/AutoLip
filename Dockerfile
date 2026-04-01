FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev libpq-dev \
    nodejs npm chromium \
    && docker-php-ext-install pdo_pgsql zip bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Install PHP deps
RUN composer install --optimize-autoloader --no-dev

# Install frontend deps
RUN npm install && npm run build

# Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000


CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000