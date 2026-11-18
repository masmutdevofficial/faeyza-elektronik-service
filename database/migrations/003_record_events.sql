CREATE TABLE IF NOT EXISTS record_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id BIGINT UNSIGNED NULL,
 spare_part_id BIGINT UNSIGNED NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 action ENUM('deleted','restored') NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (customer_id) REFERENCES customers(id),
 FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
 FOREIGN KEY (actor_id) REFERENCES users(id),
 CHECK ((customer_id IS NULL) <> (spare_part_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
