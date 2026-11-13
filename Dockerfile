FROM php:8.3-apache

RUN a2enmod headers
COPY apache-panaderia.conf /etc/apache2/conf-available/panaderia.conf
RUN a2enconf panaderia

WORKDIR /var/www/html
COPY . /var/www/html
RUN find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} +

ENV DATA_DIR=/var/data
EXPOSE 80

CMD ["sh", "-c", "mkdir -p \"$DATA_DIR\" && chown -R www-data:www-data \"$DATA_DIR\" && exec apache2-foreground"]
