#!/bin/bash
# Sourced by the official MariaDB entrypoint ONLY for a new, empty data volume.
# Identifiers are constants in compose; reject overrides before building SQL.
if [[ "${MARIADB_DATABASE}" != "faeyza_service" || "${MARIADB_USER}" != "faeyza_runtime" ]]; then
    echo 'Unexpected Faeyza database/user; initialization refused.' >&2
    exit 1
fi
docker_process_sql --database=faeyza_service <<'SQL'
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'faeyza_runtime'@'%';
GRANT SELECT, INSERT, UPDATE ON `faeyza_service`.* TO 'faeyza_runtime'@'%';
SQL
