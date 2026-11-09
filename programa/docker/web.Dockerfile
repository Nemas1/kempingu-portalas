FROM composer:2 AS dependencies
WORKDIR /dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
FROM php:8.4-apache
ENV DB_HOST=db DB_NAME=kempingai DB_USER=stud
RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'date.timezone=Europe/Vilnius\nexpose_php=Off\nsession.cookie_httponly=1\nsession.cookie_samesite=Lax\n' > "$PHP_INI_DIR/conf.d/portal.ini" \
    && sed -i 's!/var/www/html!/var/www/app/public!g' /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www
COPY --from=dependencies /dependencies/vendor /var/www/vendor
COPY --from=dependencies /dependencies/composer.lock /var/www/composer.lock
COPY app /var/www/app
COPY database /var/www/database
EXPOSE 80
CMD ["sh", "-c", "php /var/www/database/seed.php && exec apache2-foreground"]
