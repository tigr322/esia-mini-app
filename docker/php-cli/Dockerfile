FROM php:8.1-cli-alpine

# Установка системных зависимостей
RUN apk update && apk add --no-cache \
    git \
    curl \
    unzip \
    openssh-client \
    && rm -rf /var/cache/apk/*

# Копирование php.ini
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

# Установка Composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

# Установка необходимых PHP расширений (если нужны)
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        sockets \
        pcntl \
    && apk del $PHPIZE_DEPS

WORKDIR /app