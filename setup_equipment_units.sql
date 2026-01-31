-- Equipment Units System
-- This separates logical equipment types from physical units

-- Step 1: Create equipment_units table to track individual physical units
CREATE TABLE IF NOT EXISTS `equipment_units` (
  `unit_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `unit_number` int(11) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `status` enum('Available','Under Maintenance','Out of Order') NOT NULL DEFAULT 'Available',
  `purchase_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `unique_equipment_unit` (`equipment_id`, `unit_number`),
  CONSTRAINT `fk_unit_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 2: Modify equipment_maintenance to link to specific units
ALTER TABLE equipment_maintenance 
ADD COLUMN `unit_id` int(11) DEFAULT NULL AFTER `equipment_id`,
ADD CONSTRAINT `fk_maintenance_unit` FOREIGN KEY (`unit_id`) REFERENCES `equipment_units` (`unit_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Step 3: Create a trigger to auto-create units when equipment quantity is added
DELIMITER $$

CREATE TRIGGER after_equipment_insert
AFTER INSERT ON equipment
FOR EACH ROW
BEGIN
  DECLARE i INT DEFAULT 1;
  WHILE i <= NEW.total_quantity DO
    INSERT INTO equipment_units (equipment_id, unit_number, status, purchase_date)
    VALUES (NEW.equipment_id, i, 'Available', NEW.purchase_date);
    SET i = i + 1;
  END WHILE;
END$$

DELIMITER ;

-- Step 4: Migrate existing equipment to create units
-- This will create individual units for all existing equipment
INSERT INTO equipment_units (equipment_id, unit_number, status, purchase_date)
SELECT 
  e.equipment_id,
  numbers.n,
  CASE 
    WHEN numbers.n <= e.quantity_available THEN 'Available'
    ELSE 'Under Maintenance'
  END as status,
  e.purchase_date
FROM equipment e
CROSS JOIN (
  SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
  UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
  UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
  UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
  UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
  UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
  UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
  UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
  UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
  UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55
  UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60
  UNION SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65
  UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70
  UNION SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75
  UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80
  UNION SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85
  UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89 UNION SELECT 90
  UNION SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94 UNION SELECT 95
  UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99 UNION SELECT 100
) numbers
WHERE numbers.n <= e.total_quantity
AND NOT EXISTS (
  SELECT 1 FROM equipment_units WHERE equipment_id = e.equipment_id
);

-- Note: Run these queries in phpMyAdmin SQL tab one by one
