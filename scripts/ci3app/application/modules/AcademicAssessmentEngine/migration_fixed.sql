-- Portfolio Assessment Engine - Database Migration
-- GES SBA Compliant System

-- 1. Portfolio Headers (Weekly/Topic Assessment Sessions)
CREATE TABLE IF NOT EXISTS `portfolio_headers` (
  `header_id` INT PRIMARY KEY AUTO_INCREMENT,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `year` VARCHAR(10) NOT NULL,
  `term` ENUM('1','2','3') NOT NULL,
  `semester` ENUM('1','2') NULL,
  `week_number` TINYINT NOT NULL,
  `strand_topic` VARCHAR(200) NOT NULL,
  `assessment_date` DATE NOT NULL,
  `max_score` DECIMAL(5,2) DEFAULT 10.00,
  `status` ENUM('draft','published','locked') DEFAULT 'draft',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  INDEX idx_class_subject (class_id, subject_id, year, term),
  INDEX idx_teacher (teacher_id),
  INDEX idx_week (week_number, term, year),
  FOREIGN KEY (class_id) REFERENCES class(class_id),
  FOREIGN KEY (subject_id) REFERENCES subject(subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Portfolio Scores (Individual Student Scores)
CREATE TABLE IF NOT EXISTS `portfolio_scores` (
  `score_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `header_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `score` DECIMAL(5,2) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  `recorded_by` INT NOT NULL,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY unique_student_header (header_id, student_id, deleted_at),
  INDEX idx_student (student_id),
  FOREIGN KEY (header_id) REFERENCES portfolio_headers(header_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Portfolio Aggregates (Computed Averages)
CREATE TABLE IF NOT EXISTS `portfolio_aggregates` (
  `aggregate_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `year` VARCHAR(10) NOT NULL,
  `term` ENUM('1','2','3') NOT NULL,
  `semester` ENUM('1','2') NULL,
  `weekly_average` DECIMAL(5,2) NULL COMMENT 'Average for specific week',
  `term_average` DECIMAL(5,2) NULL COMMENT 'Overall term portfolio average',
  `total_assessments` INT DEFAULT 0,
  `computed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_subject_term (student_id, subject_id, year, term, semester),
  INDEX idx_student_term (student_id, year, term),
  INDEX idx_subject (subject_id),
  FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subject(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. SBA Components (GES SBA Structure)
CREATE TABLE IF NOT EXISTS `sba_components` (
  `component_id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `year` VARCHAR(10) NOT NULL,
  `term` ENUM('1','2','3') NOT NULL,
  `semester` ENUM('1','2') NULL,
  `class_test` DECIMAL(5,2) NULL COMMENT 'Auto-filled from portfolio',
  `project_work` DECIMAL(5,2) NULL,
  `homework` DECIMAL(5,2) NULL,
  `group_work` DECIMAL(5,2) NULL,
  `total_sba` DECIMAL(5,2) GENERATED ALWAYS AS (
    COALESCE(class_test, 0) + COALESCE(project_work, 0) + 
    COALESCE(homework, 0) + COALESCE(group_work, 0)
  ) STORED,
  `auto_filled` BOOLEAN DEFAULT FALSE COMMENT 'True if class_test auto-filled from portfolio',
  `last_sync_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_student_subject_sba (student_id, subject_id, year, term, semester),
  INDEX idx_student_sba (student_id, year, term),
  FOREIGN KEY (student_id) REFERENCES student(student_id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subject(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Portfolio Audit Trail
CREATE TABLE IF NOT EXISTS `portfolio_audit_trail` (
  `audit_id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `action_type` ENUM('create','update','delete','compute','sync_sba') NOT NULL,
  `entity_type` ENUM('header','score','aggregate','sba') NOT NULL,
  `entity_id` BIGINT NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `user_id` INT NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_user (user_id),
  INDEX idx_action (action_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Settings for Portfolio System
INSERT INTO settings (type, description) VALUES 
('enable_portfolio_auto_sba', '1'),
('portfolio_sba_weight', '30'),
('portfolio_min_assessments', '4')
ON DUPLICATE KEY UPDATE type=type;

-- 7. Add index on existing category column (class.category contains: 'Upper Primary', 'JHS', etc)
CREATE INDEX idx_category ON `class`(category);
