FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    postgresql-dev \
    sqlite-dev \
    zip \
    libzip-dev \
    git \
    unzip \
    curl \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    zip \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
