FROM php:8.2-apache


RUN docker-php-ext-install pdo pdo_mysql


RUN a2enmod rewrite


COPY . /var/www/app/


ENV APACHE_DOCUMENT_ROOT /var/www/app

RUN sed -ri 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf


RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

EXPOSE 80
