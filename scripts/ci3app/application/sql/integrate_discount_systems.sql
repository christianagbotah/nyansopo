-- =====================================================
-- Enterprise-Grade Discount System Integration
-- Integrates discount_types with benefit_category
-- =====================================================

-- Step 1: Enhance discount_audit_trail to support both systems
ALTER TABLE `discount_audit_trail` 
ADD COLUMN `entity_type` VARCHAR(50) DEFAULT 'discount_type' COMMENT 'Type: discount_type or benefit_category' AFTER `audit_id`;

-- Step 2: Add linking columns to benefit_category
ALTER TABLE `benefit_category`
ADD COLUMN `discount_type_id` INT(11) NULL COMMENT 'Optional reference to discount_types' AFTER `details`,
ADD COLUMN `icon` VARCHAR(10) NULL COMMENT 'Emoji icon for category' AFTER `discount_type_id`,
ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Active status' AFTER `icon`,
ADD COLUMN `created_by` INT(11) NULL COMMENT 'Admin who created' AFTER `is_active`,
ADD COLUMN `created_at` INT(11) NULL COMMENT 'Creation timestamp' AFTER `created_by`,
ADD COLUMN `updated_at` INT(11) NULL COMMENT 'Last update timestamp' AFTER `created_at`;

-- Step 3: Add foreign key (optional, for referential integrity)
ALTER TABLE `benefit_category`
ADD INDEX `idx_discount_type` (`discount_type_id`);

-- Step 4: Add indexes for performance
ALTER TABLE `discount_audit_trail`
ADD INDEX `idx_entity_type` (`entity_type`),
ADD INDEX `idx_entity_id_type` (`entity_id`, `entity_type`);

-- Step 5: Add language phrases
INSERT INTO `language` (`phrase`, `english`) VALUES
('audit_trail', 'Audit Trail'),
('action_performed', 'Action Performed'),
('entity_name', 'Entity Name'),
('changed_by', 'Changed By'),
('changed_at', 'Changed At'),
('details', 'Details'),
('view', 'View'),
('discount_type_reference', 'Discount Type Reference'),
('optional_reference', 'Optional'),
('none', 'None'),
('link_to_discount_type', 'Link this category to a discount type'),
('icon', 'Icon'),
('benefit_category_created', 'Benefit category created successfully'),
('benefit_category_updated', 'Benefit category updated successfully'),
('benefit_category_deleted', 'Benefit category deleted successfully'),
('audit_log_created', 'Audit log entry created'),
('formatted_date', 'Date'),
('entity_type', 'Entity Type')
ON DUPLICATE KEY UPDATE `english` = VALUES(`english`);

-- Step 6: Create audit summary view
CREATE OR REPLACE VIEW `v_discount_audit_summary` AS
SELECT 
    da.audit_id,
    da.entity_type,
    da.entity_id,
    da.action,
    da.changed_by,
    da.changed_at,
    da.ip_address,
    CASE 
        WHEN da.entity_type = 'discount_type' THEN dt.name
        WHEN da.entity_type = 'benefit_category' THEN bc.name
        ELSE 'Unknown'
    END AS entity_name,
    COALESCE(a.name, 'System') AS changed_by_name,
    FROM_UNIXTIME(da.changed_at, '%Y-%m-%d %H:%i:%s') AS formatted_date
FROM discount_audit_trail da
LEFT JOIN discount_types dt ON da.entity_type = 'discount_type' AND da.entity_id = dt.discount_type_id
LEFT JOIN benefit_category bc ON da.entity_type = 'benefit_category' AND da.entity_id = bc.category_id
LEFT JOIN admin a ON da.changed_by = a.admin_id
ORDER BY da.changed_at DESC;

-- =====================================================
-- Migration Complete
-- =====================================================
