# LifePlanner SIM v1.0 — Dockerfile
# @see agent.md | Docker Configuration
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    postgresql-dev

# Configure GD with JPEG and FreeType support
RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-freetype

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create system user
RUN addgroup -g 1000 laravel && adduser -u 1000 -G laravel -D laravel
RUN chown -R laravel:laravel /var/www/html

# Switch to non-root user
USER laravel

EXPOSE 9000

CMD ["php-fpm"]
