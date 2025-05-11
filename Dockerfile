# Use official PHP image with Apache
FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Copy your app into the Apache root
COPY . /var/www/html/

# Set permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (default)
EXPOSE 80
