-- Terminal Reports Table
CREATE TABLE IF NOT EXISTS `terminal_reports` (
  `report_id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `year` VARCHAR(10) NOT NULL,
  `term` VARCHAR(10) NOT NULL,
  `attendance_present` INT DEFAULT 0,
  `attendance_total` INT DEFAULT 0,
  `conduct` VARCHAR(50),
  `attitude` VARCHAR(50),
  `interest` VARCHAR(50),
  `teacher_remark` TEXT,
  `headmaster_remark` TEXT,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_year_term` (`student_id`, `year`, `term`),
  INDEX `idx_student` (`student_id`),
  INDEX `idx_year_term` (`year`, `term`),
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exam marks table (if not exists)
CREATE TABLE IF NOT EXISTS `exam_marks` (
  `mark_id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `year` VARCHAR(10) NOT NULL,
  `term` VARCHAR(10) NOT NULL,
  `total_score` DECIMAL(5,2) DEFAULT 0,
  `grade` VARCHAR(2),
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_subject` (`student_id`, `subject_id`, `year`, `term`),
  INDEX `idx_student` (`student_id`),
  INDEX `idx_subject` (`subject_id`),
  FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subject`(`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
