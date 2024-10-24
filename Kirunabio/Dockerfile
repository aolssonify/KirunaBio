# Använd officiell PHP-bild med Apache
FROM php:8.0-apache

# Installera MySQL PDO och andra tillägg som behövs
RUN docker-php-ext-install pdo pdo_mysql

# Kopiera din applikation till Apache dokumentrot
COPY . /var/www/html/

# Ändra rättigheter så att Apache kan komma åt filerna
RUN chown -R www-data:www-data /var/www/html/

# Exponera port 80 för Apache
EXPOSE 80

# Starta Apache när containern körs
CMD ["apache2-foreground"]
