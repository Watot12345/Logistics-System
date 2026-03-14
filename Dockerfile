FROM php:8.2-apache

# Force Apache to use prefork MPM
RUN apt-get update && \
    apt-get install -y apache2 && \
    a2dismod mpm_event mpm_worker || true && \
    a2enmod mpm_prefork && \
    docker-php-ext-install pdo_mysql mysqli

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
CMD ["apache2-foreground"]