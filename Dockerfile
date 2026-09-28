FROM php:8.3-fpm-alpine

# Dependencias del sistema y extensiones PHP necesarias
RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        icu-dev \
        mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos primero composer.json/lock para aprovechar cache de capas
COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copiamos el resto del código
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache storage/api-docs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
# Defensivo: por si el archivo llega con saltos de línea CRLF (p. ej. si Git
# en Windows lo reescribió), lo normalizamos a LF para que bash lo pueda
# ejecutar sin errores tipo "bad interpreter".
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
