-- ============================================================================
-- Add Specific Class IDs to Bill Items
-- ============================================================================
-- This migration adds specific_class_ids field to bill_item table to enable
-- creating bills for specific classes within a category (e.g., Creche within Pre-School)
-- ============================================================================

-- Add specific_class_ids column to bill_item table
-- ============================================================================

ALTER TABLE `bill_item` 
ADD COLUMN `specific_class_ids` TEXT DEFAULT NULL COMMENT 'Comma-separated class IDs for granular filtering (e.g., "1,2,3")' AFTER `class_category`;

-- Add index for faster lookups (Full-text index for searching within comma-separated values)
ALTER TABLE `bill_item` 
ADD FULLTEXT INDEX `idx_specific_class_ids` (`specific_class_ids`);

-- ============================================================================
-- USAGE NOTES:
-- ============================================================================
-- Filtering Priority (Hierarchical):
-- 1. If specific_class_ids is set → Apply ONLY to those specific classes
-- 2. Else if class_category is set → Apply to ALL classes in that category
-- 3. Else (both NULL) → Apply to ALL classes globally
--
-- Examples:
-- - "Creche Tuition": class_category = "Pre-School", specific_class_ids = "5" (Creche class_id)
--   → Applies ONLY to Creche
--
-- - "Pre-School Books": class_category = "Pre-School", specific_class_ids = NULL
--   → Applies to ALL Pre-School classes (Creche, Nursery, KG)
--
-- - "School Uniform": class_category = NULL, specific_class_ids = NULL
--   → Applies to ALL classes in the school
-- ============================================================================

-- ============================================================================
-- ROLLBACK SCRIPT (commented out - uncomment to rollback)
-- ============================================================================

-- ALTER TABLE `bill_item` DROP INDEX `idx_specific_class_ids`;
-- ALTER TABLE `bill_item` DROP COLUMN `specific_class_ids`;
