FROM php:8.2-apache

# Install MySQL drivers
RUN docker-php-ext-install pdo_mysql mysqli

# Copy your app
COPY . /var/www/html/
