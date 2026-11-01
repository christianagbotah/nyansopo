-- =====================================================
-- STEP 3: Add foreign key (Optional - only if discount_types exists)
-- Safe migration with error handling
-- =====================================================

-- Check if discount_types table exists
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'discount_types');

-- Check if foreign key already exists
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE table_schema = DATABASE() 
    AND table_name = 'benefit_category' 
    AND constraint_name = 'fk_benefit_discount_type');

-- Only add foreign key if table exists and FK doesn't exist
SET @sql = IF(@table_exists > 0 AND @fk_exists = 0,
    'ALTER TABLE `benefit_category` ADD CONSTRAINT `fk_benefit_discount_type` FOREIGN KEY (`discount_type_id`) REFERENCES `discount_types`(`discount_type_id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key not added - either discount_types table missing or FK already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Step 3 Complete: Foreign key handled' AS status;
