-- =====================================================
-- STEP 1: Update discount_audit_trail table
-- Safe migration with error handling
-- =====================================================

-- Add entity_type column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND column_name = 'entity_type');

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `discount_audit_trail` ADD COLUMN `entity_type` ENUM(''discount_type'', ''benefit_category'', ''student_assignment'') DEFAULT ''discount_type'' COMMENT ''Type of entity being audited'' AFTER `audit_id`',
    'SELECT "Column entity_type already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rename discount_type_id to entity_id if not already renamed
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND column_name = 'discount_type_id');

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `discount_audit_trail` CHANGE COLUMN `discount_type_id` `entity_id` INT NOT NULL COMMENT ''ID of discount_type or benefit_category''',
    'SELECT "Column already renamed to entity_id" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop old index if exists
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND index_name = 'idx_discount_type_id');

SET @sql = IF(@idx_exists > 0,
    'ALTER TABLE `discount_audit_trail` DROP INDEX `idx_discount_type_id`',
    'SELECT "Index idx_discount_type_id does not exist" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new indexes if they don't exist
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND index_name = 'idx_entity');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `discount_audit_trail` ADD INDEX `idx_entity` (`entity_type`, `entity_id`)',
    'SELECT "Index idx_entity already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add idx_changed_by if doesn't exist
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND index_name = 'idx_changed_by');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `discount_audit_trail` ADD INDEX `idx_changed_by` (`changed_by`)',
    'SELECT "Index idx_changed_by already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add idx_changed_at if doesn't exist
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_audit_trail' 
    AND index_name = 'idx_changed_at');

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `discount_audit_trail` ADD INDEX `idx_changed_at` (`changed_at`)',
    'SELECT "Index idx_changed_at already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Step 1 Complete: discount_audit_trail updated successfully' AS status;
