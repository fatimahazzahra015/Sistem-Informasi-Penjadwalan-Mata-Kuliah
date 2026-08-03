FROM php:8.3-cli

# Install system dependencies, PHP extensions, and Node.js
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js 20.x for Vite assets build
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# 1. Install Composer dependencies first (so vendor/tightenco/ziggy exists)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2. Install NPM dependencies & build Vite assets
RUN npm ci || npm install
RUN npm run build

# Prepare SQLite database file if sqlite is used
RUN mkdir -p database && touch database/database.sqlite

# Set permissions for storage and bootstrap/cache
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Copy & prepare entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENV PORT=80

ENTRYPOINT ["entrypoint.sh"]
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-80}"]
