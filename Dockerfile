FROM php:8.2-apache

# Fix MPM issue - disable event, enable prefork
RUN a2dismod mpm_event && \
    a2enmod mpm_prefork && \
    a2enmod php8.2

# Install MySQL extensions
RUN docker-php-ext-install pdo_mysql mysqli

# Enable Apache rewrite module (useful for many PHP apps)
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html