FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Copy application
COPY . /var/www/html/

# Enable Apache modules
RUN a2enmod rewrite

WORKDIR /var/www/html

# Apache runs automatically