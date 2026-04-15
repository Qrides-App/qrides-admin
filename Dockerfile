# Production PHP-FPM + Apache image for Qrides Laravel API
FROM php:8.3-apache

# Install system deps
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev \
       libssl-dev zlib1g-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd intl exif

# Required PECL extensions
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Enable Apache rewrite
RUN a2enmod rewrite headers

# Set workdir
WORKDIR /var/www/html

# Copy composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app files
COPY . .

# Composer install (no dev, optimized autoload)
# Skip ext-grpc platform check to avoid long grpc source builds on Render.
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-req=ext-grpc

# Laravel permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port
EXPOSE 8080

# Apache listens on 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
