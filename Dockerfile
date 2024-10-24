# Använd officiell PHP-bild med Apache
FROM php:8.0-apache

# Installera MySQL PDO och andra tillägg som behövs
RUN docker-php-ext-install pdo pdo_mysql

# Kopiera applikationsfiler till Apache dokumentrot
COPY . /var/www/html/

# Kopiera phpMyAdmin-konfiguration till Apache
COPY phpmyadmin.conf /etc/apache2/conf-available/phpmyadmin.conf

# Aktivera phpMyAdmin-konfiguration
RUN a2enconf phpmyadmin

# Ge rättigheter till www-data
RUN chown -R www-data:www-data /var/www/html/

# Exponera port 80
EXPOSE 80

# Starta Apache
CMD ["apache2-foreground"]
