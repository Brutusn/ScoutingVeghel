FROM composer:2 AS composer

FROM php:8.2-apache
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install extensions and tools needed by the application.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && docker-php-ext-install mysqli \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

WORKDIR /var/www/html
