ALTER TABLE spare_parts
 ADD COLUMN deleted_at DATETIME NULL,
 ADD COLUMN deleted_by BIGINT UNSIGNED NULL,
 ADD INDEX idx_part_deleted (deleted_at),
 ADD CONSTRAINT fk_part_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id)
