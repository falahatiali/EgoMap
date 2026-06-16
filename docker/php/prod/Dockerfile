FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache calendar

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 33 www-data && groupmod -g 33 www-data

RUN git config --system --add safe.directory /var/www/html

RUN mkdir -p /var/log/supervisor && chown -R www-data:www-data /var/log/supervisor

RUN chown -R www-data:www-data /var/www/html

COPY ./docker/php/prod/php.ini /usr/local/etc/php/php.ini

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
