# Base image with PHP 8.2 and Apache
FROM php:8.2-apache

# Install required system packages
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    bcmath

# Enable Apache modules
RUN a2enmod rewrite headers

# PHP runtime configurations
RUN { \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'date.timezone = UTC'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /dev/stderr'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Copy custom Apache virtual host
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Setup permissions for uploads and web directory
RUN mkdir -p /var/www/html/uploads/documents \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Setup entrypoint
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Default port for Render
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
