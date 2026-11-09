-- =====================================================
-- STEP 2: Update benefit_category table
-- Safe migration with error handling
-- =====================================================

-- Add discount_type_id column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'discount_type_id');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `discount_type_id` INT NULL COMMENT ''Optional reference to discount_types for governance'' AFTER `category_id`',
    'SELECT "Column discount_type_id already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add icon column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'icon');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `icon` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT ''Visual icon (emoji) for category'' AFTER `name`',
    'SELECT "Column icon already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add is_active column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'is_active');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 COMMENT ''Active status flag'' AFTER `details`',
    'SELECT "Column is_active already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_by column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'created_by');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `created_by` INT NULL COMMENT ''Admin ID who created this category'' AFTER `is_active`',
    'SELECT "Column created_by already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add created_at column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'created_at');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT ''Creation timestamp'' AFTER `created_by`',
    'SELECT "Column created_at already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at column if doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND column_name = 'updated_at');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `benefit_category` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT ''Last update timestamp'' AFTER `created_at`',
    'SELECT "Column updated_at already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records with timestamps
UPDATE `benefit_category` 
SET `created_at` = NOW(), 
    `updated_at` = NOW() 
WHERE `created_at` IS NULL OR `updated_at` IS NULL;

-- Add indexes if they don't exist
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND index_name = 'idx_discount_type');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `benefit_category` ADD INDEX `idx_discount_type` (`discount_type_id`)',
    'SELECT "Index idx_discount_type already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND index_name = 'idx_is_active');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `benefit_category` ADD INDEX `idx_is_active` (`is_active`)',
    'SELECT "Index idx_is_active already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND index_name = 'idx_created_by');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `benefit_category` ADD INDEX `idx_created_by` (`created_by`)',
    'SELECT "Index idx_created_by already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Step 2 Complete: benefit_category updated successfully' AS status;
