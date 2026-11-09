-- ============================================================================
-- Add Class Category to Bill Items
-- ============================================================================
-- This migration adds class_category field to bill_item table to enable
-- creating bills per class category (Pre-School, Lower Primary, Upper Primary, JHS)
-- ============================================================================

-- Add class_category column to bill_item table
-- ============================================================================

ALTER TABLE `bill_item` 
ADD COLUMN `class_category` VARCHAR(50) DEFAULT NULL COMMENT 'Class category: Pre-School, Lower Primary, Upper Primary, JHS' AFTER `bill_category_id`;

-- Add index for faster lookups
ALTER TABLE `bill_item` 
ADD INDEX `idx_class_category` (`class_category`);

-- Update existing bill items to have a default category (optional - can be set manually)
-- You can uncomment and modify based on your needs
-- UPDATE `bill_item` SET `class_category` = 'All' WHERE `class_category` IS NULL;

-- ============================================================================
-- USAGE NOTES:
-- ============================================================================
-- 1. When creating a bill item, select the class category it applies to
-- 2. Options: Pre-School, Lower Primary, Upper Primary, JHS, or NULL (for all classes)
-- 3. During invoice creation, system will only show bill items matching student's class category
-- 4. During admission, system will fetch bill items based on the new student's class category
-- ============================================================================

-- ============================================================================
-- ROLLBACK SCRIPT (commented out - uncomment to rollback)
-- ============================================================================

-- ALTER TABLE `bill_item` DROP INDEX `idx_class_category`;
-- ALTER TABLE `bill_item` DROP COLUMN `class_category`;
