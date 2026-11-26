FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    tzdata \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set timezone to Asia/Jakarta
ENV TZ=Asia/Jakarta
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Configure Apache DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN printf '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/bdi-public.conf \
    && a2enconf bdi-public

# PHP production settings for photo uploads
RUN printf 'upload_max_filesize = 16M\n\
post_max_size = 16M\n\
memory_limit = 256M\n\
date.timezone = Asia/Jakarta\n\
session.cookie_httponly = 1\n\
session.use_strict_mode = 1\n' > /usr/local/etc/php/conf.d/bdi-custom.ini

WORKDIR /var/www/html

COPY . /var/www/html

# Ensure writable storage directory
RUN mkdir -p /var/www/html/storage/uploads/photos \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

EXPOSE 80
