<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup_examination extends CI_Controller {
    
    public function create_tables() {
        // Create enterprise_exams table
        $this->db->query("CREATE TABLE IF NOT EXISTS `enterprise_exams` (
          `exam_id` INT PRIMARY KEY AUTO_INCREMENT,
          `exam_name` VARCHAR(100) NOT NULL,
          `exam_type` ENUM('mid_term','end_term','mock','waec') NOT NULL,
          `class_id` INT NOT NULL,
          `year` VARCHAR(10) NOT NULL,
          `term` VARCHAR(10) NOT NULL,
          `start_date` DATE,
          `end_date` DATE,
          `status` ENUM('draft','active','completed','published') DEFAULT 'draft',
          `created_by` INT,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_class_year_term (class_id, year, term)
        )");
        
        // Create enterprise_exam_subjects table
        $this->db->query("CREATE TABLE IF NOT EXISTS `enterprise_exam_subjects` (
          `id` INT PRIMARY KEY AUTO_INCREMENT,
          `exam_id` INT NOT NULL,
          `subject_id` INT NOT NULL,
          `total_marks` INT DEFAULT 100,
          `pass_mark` INT DEFAULT 50,
          `weight_percentage` DECIMAL(5,2) DEFAULT 100.00,
          UNIQUE KEY unique_exam_subject (exam_id, subject_id)
        )");
        
        // Create enterprise_student_marks table
        $this->db->query("CREATE TABLE IF NOT EXISTS `enterprise_student_marks` (
          `mark_id` INT PRIMARY KEY AUTO_INCREMENT,
          `exam_id` INT NOT NULL,
          `student_id` INT NOT NULL,
          `subject_id` INT NOT NULL,
          `class_score` DECIMAL(5,2) DEFAULT 0,
          `exam_score` DECIMAL(5,2) DEFAULT 0,
          `total_score` DECIMAL(5,2) DEFAULT 0,
          `grade` VARCHAR(2),
          `remark` VARCHAR(50),
          `position` INT,
          `recorded_by` INT,
          `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY unique_student_exam_subject (exam_id, student_id, subject_id),
          INDEX idx_student_exam (student_id, exam_id)
        )");
        
        // Create waec_grading_scale table
        $this->db->query("CREATE TABLE IF NOT EXISTS `waec_grading_scale` (
          `id` INT PRIMARY KEY AUTO_INCREMENT,
          `grade` VARCHAR(2) NOT NULL,
          `min_score` INT NOT NULL,
          `max_score` INT NOT NULL,
          `grade_point` DECIMAL(3,2),
          `remark` VARCHAR(50),
          `is_pass` BOOLEAN DEFAULT TRUE
        )");
        
        // Insert WAEC grades
        $grades = [
            ['A1', 80, 100, 1.00, 'Excellent', 1],
            ['B2', 70, 79, 2.00, 'Very Good', 1],
            ['B3', 65, 69, 3.00, 'Good', 1],
            ['C4', 60, 64, 4.00, 'Credit', 1],
            ['C5', 55, 59, 5.00, 'Credit', 1],
            ['C6', 50, 54, 6.00, 'Credit', 1],
            ['D7', 45, 49, 7.00, 'Pass', 1],
            ['E8', 40, 44, 8.00, 'Pass', 1],
            ['F9', 0, 39, 9.00, 'Fail', 0]
        ];
        
        foreach($grades as $grade) {
            $this->db->query("INSERT IGNORE INTO waec_grading_scale (grade, min_score, max_score, grade_point, remark, is_pass) VALUES (?, ?, ?, ?, ?, ?)", $grade);
        }
        
        echo "Examination tables created successfully!";
    }
}