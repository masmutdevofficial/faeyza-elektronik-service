FROM mariadb:11.4
COPY database/schema.sql /docker-entrypoint-initdb.d/10-faeyza-schema.sql
COPY docker/database/99-runtime-permissions.sh /docker-entrypoint-initdb.d/99-runtime-permissions.sh
RUN chmod 644 /docker-entrypoint-initdb.d/99-runtime-permissions.sh
