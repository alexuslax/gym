-- Fix Foreign Key Constraint for equipment_maintenance
-- Run this script in phpMyAdmin SQL tab

-- ISSUE: equipment_id is varchar(10) but should be int(11) to match equipment table

-- Step 1: Check for orphaned records (maintenance records with invalid equipment_id)
SELECT em.* 
FROM equipment_maintenance em
LEFT JOIN equipment e ON em.equipment_id = e.equipment_id
WHERE e.equipment_id IS NULL;

-- Step 2: Delete orphaned records (if any exist)
DELETE em 
FROM equipment_maintenance em
LEFT JOIN equipment e ON em.equipment_id = e.equipment_id
WHERE e.equipment_id IS NULL;

-- Step 3: Change equipment_id column type from varchar(10) to int(11)
ALTER TABLE equipment_maintenance
MODIFY COLUMN equipment_id int(11) NOT NULL;

-- Step 4: Add the foreign key constraint
ALTER TABLE equipment_maintenance
ADD CONSTRAINT fk_maintenance_equipment
FOREIGN KEY (equipment_id)
REFERENCES equipment(equipment_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- Verify the constraint was added
SHOW CREATE TABLE equipment_maintenance;
