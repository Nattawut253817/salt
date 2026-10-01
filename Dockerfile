FROM php:8.2-fpm

# Install system dependencies (เพิ่ม libpq-dev สำหรับ PostgreSQL)
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libpq-dev zip unzip nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (เปลี่ยน pdo_mysql เป็น pdo_pgsql pgsql)
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Nginx config & Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 80
CMD php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=80

# ติดตั้ง system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip

# ติดตั้ง PHP extensions (อย่าลืมเพิ่ม zip ต่อท้าย)
RUN docker-php-ext-install pdo pdo_pgsql pgsql bcmath gd exif pcntl zip
