FROM php:8.2-cli-alpine

# Установка системных зависимостей
RUN apk update && apk add --no-cache \
    git \
    curl \
    unzip \
    openssh-client \
    mysql-client \
    # Зависимости для PHP расширений
    $PHPIZE_DEPS \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    && rm -rf /var/cache/apk/*

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd zip sockets

# Копирование php.ini
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

# Установка Composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

WORKDIR /app