FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install -j2 mysqli mbstring opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/faeyza.ini
COPY app/ app/
COPY config/ config/
COPY views/ views/
COPY public/ public/
COPY database/ database/
COPY bin/setup.php bin/create-admin.php bin/doctor.php bin/
COPY docker/web-entrypoint.sh /usr/local/bin/faeyza-entrypoint
RUN mkdir -p storage/logs storage/sessions \
    && chown -R www-data:www-data storage \
    && chmod 750 storage storage/logs storage/sessions \
    && chmod 755 /usr/local/bin/faeyza-entrypoint

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php /var/www/html/bin/doctor.php >/dev/null 2>&1 || exit 1
ENTRYPOINT ["faeyza-entrypoint"]
CMD ["apache2-foreground"]
