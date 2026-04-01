FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev libpq-dev chromium \
    && docker-php-ext-install pdo_pgsql zip bcmath \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs build-essential

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Install PHP deps
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev

# Install frontend deps
COPY package*.json ./
RUN npm install && npm run build

# Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000


CMD php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000