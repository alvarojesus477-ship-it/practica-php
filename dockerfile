FROM php:8.3-apache

# Extensiones PHP para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# URLs amigables (el front controller recibe todas las peticiones)
RUN a2enmod rewrite

# Apache sirve public/ en vez de la raíz del proyecto
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
 && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html