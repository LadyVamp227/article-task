# syntax=docker/dockerfile:1

FROM php:8.4-fpm-bookworm

# System packages: git + unzip for Composer; libpq/libzip headers to build the
# PHP extensions below.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions: PostgreSQL (pdo_pgsql, pgsql), zip (Composer), and pcntl
# (used by the queue worker for signal handling / graceful shutdown).
RUN docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql zip pcntl

# Composer (pulled from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Run as the host user so files written to the mounted volume stay writable.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
