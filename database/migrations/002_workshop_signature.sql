ALTER TABLE service_orders
 ADD COLUMN show_workshop_signature TINYINT NOT NULL DEFAULT 1
 CHECK (show_workshop_signature IN (0,1));
