-- Add equipment type columns to equipment table
ALTER TABLE equipment ADD COLUMN is_machine TINYINT(1) DEFAULT 0 AFTER status;
ALTER TABLE equipment ADD COLUMN is_weights TINYINT(1) DEFAULT 0 AFTER is_machine;
ALTER TABLE equipment ADD COLUMN weight_kg DECIMAL(8, 2) NULL AFTER is_weights;
