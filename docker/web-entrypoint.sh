#!/bin/sh
set -eu
mkdir -p /var/www/html/storage/logs /var/www/html/storage/sessions
chown www-data:www-data /var/www/html/storage /var/www/html/storage/logs /var/www/html/storage/sessions
chmod 750 /var/www/html/storage /var/www/html/storage/logs /var/www/html/storage/sessions
exec docker-php-entrypoint "$@"
