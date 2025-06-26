# Use the official PHP 8.3 FPM image on Alpine Linux as a base
FROM php:8.3-fpm-alpine

# Set the working directory inside the container
WORKDIR /var/www/html

# Install system dependencies needed for common PHP extensions and tools
# Using a multi-line RUN command for better readability and layer caching
RUN apk add --no-cache \
    bash \
    git \
    curl \
    icu-data-full \
    build-base \
    autoconf \
    $PHPIZE_DEPS \
    libzip-dev \
    zlib-dev \
    icu-dev \
    libpq-dev \
    hiredis-dev

# Install required PHP extensions in a single layer for efficiency
RUN docker-php-ext-install -j$(nproc) pdo_pgsql intl zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer (PHP dependency manager) from the official Composer image
COPY --from=composer:lts /usr/bin/composer /usr/local/bin/composer

# Set Composer environment variables to run as root and define cache directory
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME="/tmp" \
    COMPOSER_CACHE_DIR="/tmp/cache"

# --- THE DEFINITIVE BUILD SEQUENCE (DEBUGGING ATTEMPT) ---

# 1. Copy all application files from the local context into the container.
#    The .dockerignore file ensures that unnecessary files (like /vendor) are excluded.
COPY . .

# 2. **CRITICAL STEP 1**: Install dependencies WITHOUT running any Symfony scripts.
#    This isolates the file installation from the application execution.
#    Caches are still disabled to ensure maximum freshness.
RUN rm -rf vendor var/cache/* \
    && php -d opcache.enable=0 -d opcache.enable_cli=0 /usr/local/bin/composer install --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-scripts

# 3. **CRITICAL STEP 2**: Manually run the cache clear command.
#    If this step fails, it confirms the issue is with Symfony's kernel/config loading,
#    not the Composer process itself.
#RUN php bin/console cache:clear

# 4. Final steps: create necessary directories for Symfony's cache and logs,
#    and set the correct ownership to the 'www-data' user, which is used by php-fpm.
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var