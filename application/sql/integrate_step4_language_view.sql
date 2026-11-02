-- =====================================================
-- STEP 4: Add language phrases and create view
-- Safe migration with error handling
-- =====================================================

-- Add language phrases
INSERT INTO `language` (`phrase`, `english`) 
SELECT 'discount_type_reference', 'Discount Type Reference'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'discount_type_reference');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'link_to_discount_type', 'Link to Discount Type'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'link_to_discount_type');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'audit_trail', 'Audit Trail'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'audit_trail');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'view_audit_trail', 'View Audit Trail'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'view_audit_trail');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'change_history', 'Change History'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'change_history');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'no_changes_recorded', 'No changes recorded'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'no_changes_recorded');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'changed_by', 'Changed By'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'changed_by');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'changed_at', 'Changed At'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'changed_at');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'old_values', 'Old Values'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'old_values');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'new_values', 'New Values'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'new_values');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'action_performed', 'Action Performed'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'action_performed');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'benefit_category_created', 'Benefit Category Created'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'benefit_category_created');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'benefit_category_updated', 'Benefit Category Updated'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'benefit_category_updated');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'benefit_category_deleted', 'Benefit Category Deleted'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'benefit_category_deleted');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'student_assigned_to_benefit', 'Student Assigned to Benefit'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'student_assigned_to_benefit');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'student_removed_from_benefit', 'Student Removed from Benefit'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'student_removed_from_benefit');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'optional_reference', 'Optional Reference'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'optional_reference');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'governance_layer', 'Governance Layer'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'governance_layer');

INSERT INTO `language` (`phrase`, `english`) 
SELECT 'operational_layer', 'Operational Layer'
WHERE NOT EXISTS (SELECT 1 FROM `language` WHERE `phrase` = 'operational_layer');

-- Create audit summary view
DROP VIEW IF EXISTS `v_discount_audit_summary`;

CREATE VIEW `v_discount_audit_summary` AS
SELECT 
    dat.audit_id,
    dat.entity_type,
    dat.entity_id,
    dat.action,
    CASE 
        WHEN dat.entity_type = 'discount_type' THEN IFNULL(dt.name, 'Deleted')
        WHEN dat.entity_type = 'benefit_category' THEN IFNULL(bc.name, 'Deleted')
        ELSE 'Unknown'
    END AS entity_name,
    IFNULL(CONCAT(a.name, ' (', a.email, ')'), 'Unknown User') AS changed_by_name,
    dat.changed_at,
    dat.ip_address,
    DATE_FORMAT(dat.changed_at, '%Y-%m-%d %H:%i:%s') AS formatted_date
FROM discount_audit_trail dat
LEFT JOIN discount_types dt ON dat.entity_type = 'discount_type' AND dat.entity_id = dt.discount_type_id
LEFT JOIN benefit_category bc ON dat.entity_type = 'benefit_category' AND dat.entity_id = bc.category_id
LEFT JOIN admin a ON dat.changed_by = a.admin_id
ORDER BY dat.changed_at DESC;

SELECT 'Step 4 Complete: Language phrases and view created successfully' AS status;
