#!/bin/sh
set -eu
mkdir -p /var/www/html/storage/logs /var/www/html/storage/sessions
chown www-data:www-data /var/www/html/storage /var/www/html/storage/logs /var/www/html/storage/sessions
chmod 750 /var/www/html/storage /var/www/html/storage/logs /var/www/html/storage/sessions
# Trust only the actual Coolify proxy address resolved on this resource's network.
# Standalone containers without Coolify keep REMOTE_ADDR unchanged.
proxy_ips="$(getent ahostsv4 coolify-proxy 2>/dev/null | awk '{print $1}' | sort -u)"
proxy_conf=/etc/apache2/conf-enabled/faeyza-proxy.conf
printf '# Generated from the local Docker DNS, never from request headers.\n' > "$proxy_conf"
if [ -n "$proxy_ips" ]; then
    printf 'RemoteIPHeader X-Forwarded-For\n' >> "$proxy_conf"
    for proxy_ip in $proxy_ips; do
        case "$proxy_ip" in *[!0-9.]*|'') exit 1;; esac
        printf 'RemoteIPTrustedProxy %s\n' "$proxy_ip" >> "$proxy_conf"
    done
fi
exec docker-php-entrypoint "$@"
