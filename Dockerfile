# Install Apache och PHP
FROM php:8.0-apache

# Installera MySQL PDO-tillägget
RUN docker-php-ext-install pdo pdo_mysql

# Kopiera phpMyAdmin-filer till Apache dokumentroot
COPY phpmyadmin/ /var/www/html/phpmyadmin/

# Kopiera Apache-konfiguration för phpMyAdmin
COPY phpmyadmin.conf /etc/apache2/conf-available/phpmyadmin.conf

# Aktivera phpMyAdmin-konfiguration i Apache
RUN a2enconf phpmyadmin

# Exponera port 80
EXPOSE 80

# Starta Apache
CMD ["apache2-foreground"]
