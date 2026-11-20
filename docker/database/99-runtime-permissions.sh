#!/bin/bash
# Sourced by the official MariaDB entrypoint ONLY for a new, empty data volume.
# Identifiers are constants in compose; reject overrides before building SQL.
if [[ "${MARIADB_DATABASE}" != "faeyza_service" || "${MARIADB_USER}" != "faeyza_runtime" ]]; then
    echo 'Unexpected Faeyza database/user; initialization refused.' >&2
    exit 1
fi
docker_process_sql --database=faeyza_service <<'SQL'
INSERT IGNORE INTO schema_migrations(version) VALUES (2);
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'faeyza_runtime'@'%';
GRANT SELECT, INSERT, UPDATE ON `faeyza_service`.* TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`service_order_parts` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`service_orders` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`record_events` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`customers` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`users` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`login_limits` TO 'faeyza_runtime'@'%';
SQL
