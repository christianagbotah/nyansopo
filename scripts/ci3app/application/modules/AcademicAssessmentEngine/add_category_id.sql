-- Run this AFTER main migration if category_id column doesn't exist

-- Check if column exists first
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'class' 
AND COLUMN_NAME = 'category_id';

-- Add column if it doesn't exist
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `class` ADD COLUMN `category_id` INT NULL COMMENT "JHS=1, SHS=2, Primary=3" AFTER `name`', 
    'SELECT "Column category_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index
CREATE INDEX IF NOT EXISTS idx_category ON `class`(category_id);

-- Populate category_id based on class names
UPDATE `class` SET category_id = 1 WHERE name LIKE '%JHS%' OR name = 'JHSS';
UPDATE `class` SET category_id = 2 WHERE name LIKE '%SHS%';
UPDATE `class` SET category_id = 3 WHERE name LIKE '%Primary%' OR name LIKE '%Creche%';
