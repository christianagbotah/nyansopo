-- =====================================================
-- Pre-Assigned Student Discount System
-- Replaces benefit_category system
-- =====================================================

-- 1. Discount Profiles Table (replaces benefit_category)
CREATE TABLE IF NOT EXISTS `discount_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_name` varchar(255) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `profile_name` (`profile_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Discount Profile Rules (class-specific discount amounts)
CREATE TABLE IF NOT EXISTS `discount_profile_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL COMMENT 'NULL or 0 means all classes',
  `bill_category_id` int(11) DEFAULT NULL COMMENT 'NULL means all bill categories',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  KEY `profile_id` (`profile_id`),
  KEY `class_id` (`class_id`),
  KEY `bill_category_id` (`bill_category_id`),
  FOREIGN KEY (`profile_id`) REFERENCES `discount_profiles` (`profile_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Student Discount Assignments (replaces benefit_status in student table)
CREATE TABLE IF NOT EXISTS `student_discount_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `term` int(1) DEFAULT NULL,
  `sem` int(1) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `profile_id` (`profile_id`),
  KEY `year_term` (`year`,`term`),
  FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`profile_id`) REFERENCES `discount_profiles` (`profile_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Add index for faster lookups
CREATE INDEX idx_student_year_term ON student_discount_assignments(student_id, year, term, is_active);
CREATE INDEX idx_profile_class ON discount_profile_rules(profile_id, class_id);

-- 5. Insert sample discount profiles
INSERT INTO `discount_profiles` (`profile_name`, `discount_type`, `description`) VALUES
('Staff Child', 'percentage', 'Discount for children of staff members'),
('Sibling Discount', 'percentage', 'Discount for students with siblings in school'),
('Financial Hardship', 'percentage', 'Discount for students facing financial difficulties'),
('Early Payment', 'percentage', 'Discount for early payment of fees'),
('Scholarship - Full', 'percentage', 'Full scholarship (100% discount)'),
('Scholarship - Partial', 'percentage', 'Partial scholarship (50% discount)');

-- 6. Sample rules (adjust as needed)
-- Staff Child: 20% on all fees for all classes
INSERT INTO `discount_profile_rules` (`profile_id`, `class_id`, `bill_category_id`, `discount_value`) 
SELECT profile_id, NULL, NULL, 20.00 FROM discount_profiles WHERE profile_name = 'Staff Child';

-- Sibling: 10% on all fees for all classes
INSERT INTO `discount_profile_rules` (`profile_id`, `class_id`, `bill_category_id`, `discount_value`) 
SELECT profile_id, NULL, NULL, 10.00 FROM discount_profiles WHERE profile_name = 'Sibling Discount';

-- Full Scholarship: 100% on all fees
INSERT INTO `discount_profile_rules` (`profile_id`, `class_id`, `bill_category_id`, `discount_value`) 
SELECT profile_id, NULL, NULL, 100.00 FROM discount_profiles WHERE profile_name = 'Scholarship - Full';
