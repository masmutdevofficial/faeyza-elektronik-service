ALTER TABLE customers
 ADD COLUMN deleted_at DATETIME NULL,
 ADD COLUMN deleted_by BIGINT UNSIGNED NULL,
 ADD INDEX idx_customer_deleted (deleted_at),
 ADD CONSTRAINT fk_customer_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
