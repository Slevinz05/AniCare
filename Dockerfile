FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    default-mysql-client \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_mysql \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/anicare.ini /usr/local/etc/php/conf.d/anicare.ini

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && sed -i '1s/^\xEF\xBB\xBF//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

COPY . .

RUN mkdir -p var public/uploads/animals public/uploads/health-book-entries \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

EXPOSE 80

ENTRYPOINT ["/bin/sh", "/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]