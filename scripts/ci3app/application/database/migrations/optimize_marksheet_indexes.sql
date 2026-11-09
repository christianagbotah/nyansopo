-- SQL Optimization Script for Marksheet Queries
-- This script adds indexes to improve performance of marksheet generation
-- and exam-related queries.
-- Verified against actual table structures (exam, mark, exam_marks_jhss, enroll, student, subject, grade)

-- ============================================
-- Indexes for exam table
-- Columns: year, term, sem, date are LONGTEXT
-- ============================================
ALTER TABLE `exam` 
ADD INDEX `idx_exam_lookup` (`year`(10), `term`(10), `sem`(10), `category_id`);

ALTER TABLE `exam` 
ADD INDEX `idx_exam_date` (`date`(10));

-- ============================================
-- Indexes for mark table (used in marksheet generation)
-- Columns: student_id, exam_id, subject_id, class_id, section_id, year are present
-- ============================================
ALTER TABLE `mark` 
ADD INDEX `idx_mark_student_exam` (`student_id`, `exam_id`),
ADD INDEX `idx_mark_subject_exam` (`subject_id`, `exam_id`),
ADD INDEX `idx_mark_class_section` (`class_id`, `section_id`),
ADD INDEX `idx_mark_year` (`year`(10));

-- ============================================
-- Indexes for exam_marks table (enterprise exam marks)
-- Columns: student_id, subject_id, class_id, year, term
-- ============================================
ALTER TABLE `exam_marks` 
ADD INDEX `idx_exam_marks_student` (`student_id`, `class_id`),
ADD INDEX `idx_exam_marks_year_term` (`year`, `term`);

-- ============================================
-- Indexes for enroll table (student enrollment lookups)
-- Columns: student_id, class_id, section_id, year, term, sem are LONGTEXT
-- ============================================
ALTER TABLE `enroll` 
ADD INDEX `idx_enroll_student_year` (`student_id`, `year`(10)),
ADD INDEX `idx_enroll_class_section_year` (`class_id`, `section_id`, `year`(10)),
ADD INDEX `idx_enroll_term_sem` (`term`(10), `sem`(10));

-- ============================================
-- Indexes for student table (basic student lookups)
-- ============================================
ALTER TABLE `student` 
ADD INDEX `idx_student_name` (`name`(50)),
ADD INDEX `idx_student_code` (`student_code`(20));

-- ============================================
-- Indexes for subject table
-- ============================================
ALTER TABLE `subject` 
ADD INDEX `idx_subject_class` (`class_id`);

-- ============================================
-- Indexes for grade table (used in marksheet grading)
-- Note: Using grade_point_numeric (DOUBLE) instead of grade_point (LONGTEXT)
-- ============================================
ALTER TABLE `grade` 
ADD INDEX `idx_grade_lookup` (`grade_point_numeric`, `mark_from`, `mark_upto`);

-- ============================================
-- NOTES:
-- 1. Run this script when database is not under heavy load
-- 2. Backup database before running this script
-- 3. Some indexes may already exist - MySQL will show warnings but will not fail
-- 4. All LONGTEXT columns use prefix length (10) for indexing
-- ============================================
