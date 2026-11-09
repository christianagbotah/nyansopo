-- ============================================================================
-- Fee Structure System Migration
-- ============================================================================
-- This migration creates tables for managing fee structures by class groups
-- and individual classes (JHS). Supports grouping classes like Pre-School,
-- Lower Primary, Upper Primary, and treating JHS 1, 2, 3 individually.
-- ============================================================================

-- Table 1: fee_structure_groups
-- Defines fee structure groups (e.g., Pre-School, Lower Primary, Upper Primary)
-- and maps which classes belong to each group
-- ============================================================================

CREATE TABLE IF NOT EXISTS `fee_structure_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL COMMENT 'Name of the fee group (e.g., Pre-School, Lower Primary, Upper Primary, JHS 1)',
  `group_type` enum('grouped','individual') NOT NULL DEFAULT 'grouped' COMMENT 'grouped = multiple classes share fees, individual = specific class',
  `description` text DEFAULT NULL COMMENT 'Description of this fee group',
  `class_ids` text NOT NULL COMMENT 'Comma-separated list of class_id values belonging to this group',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'admin_id who created this group',
  `updated_by` int(11) DEFAULT NULL COMMENT 'admin_id who last updated this group',
  -- Sync columns for multi-device support
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') DEFAULT 'PENDING',
  `last_modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_by` int(11) DEFAULT NULL,
  `device_id` varchar(50) DEFAULT 'local-server-001',
  `version` int(11) DEFAULT 1,
  `retry_count` int(11) DEFAULT 0,
  `sync_error` text DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  KEY `idx_group_type` (`group_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Fee structure groups for class-level billing';


-- Table 2: fee_structures
-- Stores bill items and amounts for each fee structure group
-- Replaces the need to manually bill each class individually
-- ============================================================================

CREATE TABLE IF NOT EXISTS `fee_structures` (
  `fee_structure_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL COMMENT 'Reference to fee_structure_groups.group_id',
  `bill_item_id` int(11) NOT NULL COMMENT 'Reference to bill_item.id',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Fee amount for this bill item',
  `year` varchar(20) NOT NULL COMMENT 'Academic year (e.g., 2025-2026)',
  `term` int(11) NOT NULL COMMENT 'Term number (1, 2, or 3)',
  `residence_type` enum('Day','Boarding','Both') NOT NULL DEFAULT 'Both' COMMENT 'Applies to Day/Boarding/Both students',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'admin_id who created this fee',
  `updated_by` int(11) DEFAULT NULL COMMENT 'admin_id who last updated this fee',
  -- Sync columns for multi-device support
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') DEFAULT 'PENDING',
  `last_modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_by` int(11) DEFAULT NULL,
  `device_id` varchar(50) DEFAULT 'local-server-001',
  `version` int(11) DEFAULT 1,
  `retry_count` int(11) DEFAULT 0,
  `sync_error` text DEFAULT NULL,
  PRIMARY KEY (`fee_structure_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_bill_item_id` (`bill_item_id`),
  KEY `idx_year_term` (`year`, `term`),
  KEY `idx_residence_type` (`residence_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  -- Composite index for common queries
  KEY `idx_lookup` (`group_id`, `year`, `term`, `residence_type`, `is_active`),
  -- Foreign keys
  CONSTRAINT `fk_fee_structures_group` FOREIGN KEY (`group_id`) REFERENCES `fee_structure_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_structures_bill_item` FOREIGN KEY (`bill_item_id`) REFERENCES `bill_item` (`id`) ON DELETE CASCADE,
  -- Unique constraint to prevent duplicate fees
  UNIQUE KEY `unique_fee` (`group_id`, `bill_item_id`, `year`, `term`, `residence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Fee structures with bill items per group/term/year';


-- Insert default fee structure groups based on existing class categories
-- ============================================================================

INSERT INTO `fee_structure_groups` (`group_name`, `group_type`, `description`, `class_ids`, `is_active`, `created_by`) 
VALUES 
('Pre-School', 'grouped', 'All CRECHE, NURSERY, and KG classes', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `category` = 'Pre-School'),
 1, NULL),
 
('Lower Primary', 'grouped', 'BASIC 1, 2, and 3 classes', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `category` = 'Lower Primary'),
 1, NULL),
 
('Upper Primary', 'grouped', 'BASIC 4 and 5 classes', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `category` = 'Upper Primary'),
 1, NULL);

-- Insert individual JHS classes (name_numeric 6 = JHS 1, 7 = JHS 2, 8/9 = JHS 3)
-- ============================================================================

-- JHS 1 (BASIC 6)
INSERT INTO `fee_structure_groups` (`group_name`, `group_type`, `description`, `class_ids`, `is_active`, `created_by`)
VALUES 
('JHS 1', 'individual', 'Junior High School Form 1 (BASIC 6)', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `name` = 'BASIC' AND `name_numeric` = '6'),
 1, NULL);

-- JHS 2 (BASIC 7)
INSERT INTO `fee_structure_groups` (`group_name`, `group_type`, `description`, `class_ids`, `is_active`, `created_by`)
VALUES 
('JHS 2', 'individual', 'Junior High School Form 2 (BASIC 7)', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `name` = 'BASIC' AND `name_numeric` = '7'),
 1, NULL);

-- JHS 3 (BASIC 8 and 9)
INSERT INTO `fee_structure_groups` (`group_name`, `group_type`, `description`, `class_ids`, `is_active`, `created_by`)
VALUES 
('JHS 3', 'individual', 'Junior High School Form 3 (BASIC 8 and 9)', 
 (SELECT GROUP_CONCAT(class_id) FROM `class` WHERE `name` = 'BASIC' AND `name_numeric` IN ('8', '9')),
 1, NULL);


-- Create settings entry to enable/disable fee structure system
-- ============================================================================

INSERT INTO `settings` (`type`, `description`) 
VALUES ('use_fee_structures', 'no')
ON DUPLICATE KEY UPDATE `type` = 'use_fee_structures';


-- Add sync table entries for the new tables
-- ============================================================================

INSERT INTO `sync_tables` (`table_name`, `priority`, `parent_table`, `parent_column`, `requires_year_term`, `has_student_link`, `timestamp_column`, `has_class_filter`, `scope`, `has_section_filter`, `batch_size`, `description`)
VALUES 
('fee_structure_groups', 90, NULL, NULL, 0, 0, 'updated_at', 0, 'GLOBAL', 0, 100, 'Fee structure group definitions'),
('fee_structures', 91, 'fee_structure_groups', 'group_id', 0, 0, 'updated_at', 0, 'GLOBAL', 0, 100, 'Fee structure definitions per group')
ON DUPLICATE KEY UPDATE 
  `priority` = VALUES(`priority`),
  `description` = VALUES(`description`);


-- ============================================================================
-- ROLLBACK SCRIPT (commented out - uncomment to rollback)
-- ============================================================================

-- DROP TABLE IF EXISTS `fee_structures`;
-- DROP TABLE IF EXISTS `fee_structure_groups`;
-- DELETE FROM `settings` WHERE `type` = 'use_fee_structures';
-- DELETE FROM `sync_tables` WHERE `table_name` IN ('fee_structure_groups', 'fee_structures');
