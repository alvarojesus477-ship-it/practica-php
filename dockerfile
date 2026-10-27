FROM php:8.3-apache

# Extensiones PHP para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Módulos de Apache habituales (URLs amigables, etc.)
RUN a2enmod rewrite

WORKDIR /var/www/html