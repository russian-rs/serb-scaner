# Стадия 1: Сборка PHP с необходимыми расширениями и Xdebug
FROM php:8.1-fpm

# Установка необходимых пакетов
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    unzip \
    zip \
    git \
    libzip-dev \
    curl \
    ca-certificates \
    gosu \
    # Для установки Node.js
    gnupg \
    # Для установки PHP с PPA
    lsb-release \
    software-properties-common

# Установка PHP расширений
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd
RUN docker-php-ext-install zip bcmath

# Установка xDebug и его конфигурация
RUN pecl install xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app