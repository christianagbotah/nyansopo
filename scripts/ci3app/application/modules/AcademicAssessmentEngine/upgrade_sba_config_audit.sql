-- SBA Weighting Configuration & Audit Tracking System
-- GES Compliant with Full Audit Trail

-- 1. SBA Weight Configuration (Dynamic per class category)
CREATE TABLE IF NOT EXISTS `sba_weight_config` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `academic_year` VARCHAR(10) NOT NULL,
  `term` ENUM('1','2','3') NOT NULL,
  `class_category` VARCHAR(50) NOT NULL COMMENT 'JHS, Upper Primary, SHS, etc',
  `class_test_weight` DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  `project_weight` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `exam_weight` DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `portfolio_as_class_test` BOOLEAN DEFAULT TRUE COMMENT 'Use portfolio for class test',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY unique_config (academic_year, term, class_category, deleted_at),
  INDEX idx_year_term (academic_year, term),
  INDEX idx_category (class_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default GES-compliant weights
INSERT INTO `sba_weight_config` (academic_year, term, class_category, class_test_weight, project_weight, exam_weight, portfolio_as_class_test, created_by) VALUES
('2024-2025', '1', 'JHS', 30.00, 10.00, 60.00, 1, 1),
('2024-2025', '2', 'JHS', 30.00, 10.00, 60.00, 1, 1),
('2024-2025', '3', 'JHS', 30.00, 10.00, 60.00, 1, 1),
('2024-2025', '1', 'Upper Primary', 40.00, 0.00, 60.00, 1, 1),
('2024-2025', '2', 'Upper Primary', 40.00, 0.00, 60.00, 1, 1),
('2024-2025', '3', 'Upper Primary', 40.00, 0.00, 60.00, 1, 1)
ON DUPLICATE KEY UPDATE class_test_weight=class_test_weight;

-- 2. SBA Score Sources (Audit Trail for Every Score)
CREATE TABLE IF NOT EXISTS `sba_score_sources` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `sba_component_id` INT NOT NULL,
  `component_type` ENUM('class_test','project_work','homework','group_work') NOT NULL,
  `source_type` ENUM('portfolio','manual_entry','import','migration') NOT NULL,
  `source_reference_id` BIGINT NULL COMMENT 'portfolio_aggregates.id or import batch id',
  `original_value` DECIMAL(5,2) NULL,
  `computed_value` DECIMAL(5,2) NOT NULL,
  `computation_formula` VARCHAR(255) NULL,
  `computed_by` INT NOT NULL,
  `computed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  INDEX idx_sba_component (sba_component_id),
  INDEX idx_source_type (source_type),
  INDEX idx_computed_by (computed_by),
  FOREIGN KEY (sba_component_id) REFERENCES sba_components(component_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Curriculum Structure (GES Standards)
CREATE TABLE IF NOT EXISTS `curriculum_strands` (
  `strand_id` INT PRIMARY KEY AUTO_INCREMENT,
  `strand_code` VARCHAR(20) NOT NULL,
  `strand_name` VARCHAR(200) NOT NULL,
  `subject_id` INT NOT NULL,
  `class_category` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_strand (strand_code, subject_id, class_category),
  INDEX idx_subject (subject_id),
  FOREIGN KEY (subject_id) REFERENCES subject(subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `curriculum_sub_strands` (
  `sub_strand_id` INT PRIMARY KEY AUTO_INCREMENT,
  `strand_id` INT NOT NULL,
  `sub_strand_code` VARCHAR(20) NOT NULL,
  `sub_strand_name` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_sub_strand (sub_strand_code, strand_id),
  INDEX idx_strand (strand_id),
  FOREIGN KEY (strand_id) REFERENCES curriculum_strands(strand_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `curriculum_indicators` (
  `indicator_id` INT PRIMARY KEY AUTO_INCREMENT,
  `sub_strand_id` INT NOT NULL,
  `indicator_code` VARCHAR(20) NOT NULL,
  `indicator_text` TEXT NOT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_indicator (indicator_code, sub_strand_id),
  INDEX idx_sub_strand (sub_strand_id),
  FOREIGN KEY (sub_strand_id) REFERENCES curriculum_sub_strands(sub_strand_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Upgrade portfolio_headers with curriculum references
ALTER TABLE `portfolio_headers` 
ADD COLUMN `strand_id` INT NULL AFTER `strand_topic`,
ADD COLUMN `sub_strand_id` INT NULL AFTER `strand_id`,
ADD COLUMN `indicator_id` INT NULL AFTER `sub_strand_id`,
ADD INDEX idx_strand (strand_id),
ADD INDEX idx_sub_strand (sub_strand_id),
ADD INDEX idx_indicator (indicator_id);

-- 5. Add foreign keys (optional - comment out if causing issues)
-- ALTER TABLE `portfolio_headers` 
-- ADD FOREIGN KEY (strand_id) REFERENCES curriculum_strands(strand_id) ON DELETE SET NULL,
-- ADD FOREIGN KEY (sub_strand_id) REFERENCES curriculum_sub_strands(sub_strand_id) ON DELETE SET NULL,
-- ADD FOREIGN KEY (indicator_id) REFERENCES curriculum_indicators(indicator_id) ON DELETE SET NULL;

-- 6. Backward compatibility view
CREATE OR REPLACE VIEW `portfolio_headers_with_curriculum` AS
SELECT 
  ph.*,
  cs.strand_name,
  css.sub_strand_name,
  ci.indicator_text,
  COALESCE(
    CONCAT(cs.strand_code, ' - ', css.sub_strand_code, ' - ', ci.indicator_code),
    ph.strand_topic
  ) AS full_curriculum_path
FROM portfolio_headers ph
LEFT JOIN curriculum_strands cs ON cs.strand_id = ph.strand_id
LEFT JOIN curriculum_sub_strands css ON css.sub_strand_id = ph.sub_strand_id
LEFT JOIN curriculum_indicators ci ON ci.indicator_id = ph.indicator_id;

-- 7. Add weight tracking to sba_components
ALTER TABLE `sba_components`
ADD COLUMN `applied_class_test_weight` DECIMAL(5,2) NULL AFTER `class_test`,
ADD COLUMN `applied_project_weight` DECIMAL(5,2) NULL AFTER `project_work`,
ADD COLUMN `applied_exam_weight` DECIMAL(5,2) NULL AFTER `group_work`,
ADD COLUMN `weight_config_id` INT NULL COMMENT 'Reference to sba_weight_config used',
ADD INDEX idx_weight_config (weight_config_id);
