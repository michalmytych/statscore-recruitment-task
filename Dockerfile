FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
     libonig-dev \
    libsqlite3-dev \
    sqlite3 \
    unzip \
    && docker-php-ext-install \
        pdo_sqlite \
        mbstring \ 
        sockets \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Create database directory
RUN mkdir -p db && chmod 777 db

# Expose port
EXPOSE 8000

# Run SQLite migration and start PHP built-in server
CMD ["sh", "-c", "php /app/db/migration.php && exec php -S 0.0.0.0:8000 -t public"]
