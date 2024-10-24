# Använd officiell PHP-bild med Apache
FROM php:8.0-apache

# Installera MySQL PDO och MySQLi-tillägg
RUN docker-php-ext-install pdo pdo_mysql
RUN docker-php-ext-install mysqli

# Kopiera applikationsfiler till Apache dokumentrot
COPY . /var/www/html/

# Ge rättigheter till Apache
RUN chown -R www-data:www-data /var/www/html/

# Exponera port 80
EXPOSE 80

# Starta Apache
CMD ["apache2-foreground"]
