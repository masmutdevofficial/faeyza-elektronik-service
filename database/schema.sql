CREATE TABLE IF NOT EXISTS schema_migrations (
 version INT PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 phone VARCHAR(16) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 session_version INT UNSIGNED NOT NULL DEFAULT 1,
 role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS customers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 address TEXT NOT NULL,
 deleted_at DATETIME NULL,
 deleted_by BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_customer_name(name),
 INDEX idx_customer_deleted(deleted_at),
 CONSTRAINT fk_customer_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id),
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS invoice_sequences (
 business_date DATE PRIMARY KEY,
 last_number INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS service_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id BIGINT UNSIGNED NOT NULL,
 invoice_number VARCHAR(40) NOT NULL UNIQUE,
 issued_on DATE NOT NULL,
 submission_key CHAR(64) NOT NULL,
 received_date DATE NOT NULL,
 customer_name VARCHAR(150) NOT NULL,
 contact_phone VARCHAR(16) NOT NULL,
 address TEXT NOT NULL,
 item_name VARCHAR(120) NOT NULL,
 brand VARCHAR(120) NOT NULL,
 model VARCHAR(120) NOT NULL,
 serial_number VARCHAR(150) NOT NULL DEFAULT '',
 accessories TEXT NOT NULL,
 complaint TEXT NOT NULL,
 location ENUM('Bengkel','Home Service') NOT NULL,
 notes TEXT NOT NULL,
 technician_notes TEXT NOT NULL,
 status ENUM('Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan') NOT NULL DEFAULT 'Menunggu Konfirmasi',
 completed_date DATE NULL,
 warranty_date DATE NULL,
 total_cost DECIMAL(14,2) NULL,
 show_workshop_signature TINYINT NOT NULL DEFAULT 1 CHECK (show_workshop_signature IN (0,1)),
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_submission(customer_id,submission_key),
 INDEX idx_service_status(status), INDEX idx_service_phone(contact_phone), INDEX idx_service_date(received_date),
 FOREIGN KEY (customer_id) REFERENCES customers(id),
 CONSTRAINT chk_total_cost CHECK (total_cost IS NULL OR total_cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS spare_parts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sku VARCHAR(60) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 stock INT NOT NULL DEFAULT 0,
 minimum_stock INT NOT NULL DEFAULT 3,
 purchase_price DECIMAL(14,2) NULL,
 sale_price DECIMAL(14,2) NULL,
 unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
 deleted_at DATETIME NULL,
 deleted_by BIGINT UNSIGNED NULL,
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_part_name(name),
 INDEX idx_part_deleted(deleted_at),
 CONSTRAINT fk_part_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id),
 CONSTRAINT chk_stock CHECK (stock >= 0), CONSTRAINT chk_minimum CHECK (minimum_stock >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS service_order_parts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 service_order_id BIGINT UNSIGNED NOT NULL,
 spare_part_id BIGINT UNSIGNED NOT NULL,
 quantity INT NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_allocation(service_order_id,spare_part_id),
 FOREIGN KEY (service_order_id) REFERENCES service_orders(id),
 FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
 CONSTRAINT chk_quantity CHECK (quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS stock_movements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 spare_part_id BIGINT UNSIGNED NOT NULL,
 service_order_id BIGINT UNSIGNED NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 delta INT NOT NULL,
 balance_after INT NOT NULL,
 reason VARCHAR(255) NOT NULL,
 operation_key CHAR(64) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
 FOREIGN KEY (service_order_id) REFERENCES service_orders(id),
 FOREIGN KEY (actor_id) REFERENCES users(id),
 CONSTRAINT chk_movement_balance CHECK (balance_after >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS login_limits (
 bucket CHAR(64) PRIMARY KEY,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_started DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO schema_migrations(version) VALUES (1);
