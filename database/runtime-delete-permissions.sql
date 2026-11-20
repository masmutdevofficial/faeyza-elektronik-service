-- Run with the Faeyza database administrator before deploying permanent deletion.
-- These identifiers match docker-compose.yml; adapt database/user/host on shared hosting.
-- No DELETE permission is granted on stock_movements or invoice_sequences.
GRANT DELETE ON `faeyza_service`.`service_order_parts` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`service_orders` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`record_events` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`customers` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`users` TO 'faeyza_runtime'@'%';
GRANT DELETE ON `faeyza_service`.`login_limits` TO 'faeyza_runtime'@'%';
