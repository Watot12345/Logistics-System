FROM php:8.2-fpm

# Install MySQL extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Copy application
COPY . /var/www/html/

WORKDIR /var/www/html