FROM php:8.2-fpm

WORKDIR /var/www/html

# Install system dependencies + PostgreSQL + Node
RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev libpq-dev nodejs npm \
    && docker-php-ext-install pdo pdo_pgsql zip

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

EXPOSE 9000

CMD ["php-fpm"]