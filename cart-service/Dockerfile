FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Install dependensi PostgreSQL dan PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install

# Expose port
EXPOSE 3002

# Command untuk menunggu DB siap, jalankan migration, lalu start server
CMD bash -c " \
    until pg_isready -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USERNAME}; do \
        echo 'Waiting for PostgreSQL...'; \
        sleep 3; \
    done; \
    php artisan migrate --force; \
    php -S 0.0.0.0:3002 -t public \
"
