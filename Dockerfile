# Use an official PHP image with Apache
FROM php:8.0-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql mysqli

WORKDIR /var/www/html

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем файлы вашего приложения в контейнер
COPY . /var/www/html

# Установка зависимостей вашего проекта с помощью Composer
RUN composer install --no-interaction --optimize-autoloader --working-dir=/var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update the Apache configuration to set the DocumentRoot and DirectoryIndex
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf


# Set the ServerName to suppress the warning
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]