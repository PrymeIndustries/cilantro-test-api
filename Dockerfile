FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    nginx libpng-dev libonig-dev libxml2-dev zip unzip git \
    && docker-php-ext-install pdo_mysql mbstring exif bcmath gd

WORKDIR /var/www

# Copy project files
COPY . .

# Install Composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Ensure storage directory exists and fix permissions
RUN mkdir -p storage/logs \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage

# Copy custom Nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default

EXPOSE 80

# Run migrations and start Nginx + PHP-FPM
CMD service nginx start && php-fpm