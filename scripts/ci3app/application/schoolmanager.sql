-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 14, 2026 at 10:53 PM
-- Server version: 9.6.0
-- PHP Version: 8.3.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schoolmanager`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_syllabus`
--

DROP TABLE IF EXISTS `academic_syllabus`;
CREATE TABLE IF NOT EXISTS `academic_syllabus` (
  `academic_syllabus_id` int NOT NULL AUTO_INCREMENT,
  `academic_syllabus_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `title` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int DEFAULT NULL,
  `uploader_type` longtext COLLATE utf8mb4_unicode_520_ci,
  `uploader_id` int DEFAULT NULL,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `file_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `subject_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`academic_syllabus_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accountant`
--

DROP TABLE IF EXISTS `accountant`;
CREATE TABLE IF NOT EXISTS `accountant` (
  `accountant_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`accountant_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `bank_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `branch` longtext COLLATE utf8mb4_unicode_520_ci,
  `account_name` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_number` longtext COLLATE utf8mb4_unicode_520_ci,
  `account_type` int NOT NULL,
  `opening_balance` double DEFAULT '0',
  `current_balance` double DEFAULT '0',
  `account_is_bank` int NOT NULL DEFAULT '0',
  `account_is_transferrable` int NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`account_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accounts_payable`
--

DROP TABLE IF EXISTS `accounts_payable`;
CREATE TABLE IF NOT EXISTS `accounts_payable` (
  `accounts_payable_id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int DEFAULT NULL,
  `po_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `amount` double DEFAULT '0',
  `amount_paid` double DEFAULT '0',
  `amount_due` double DEFAULT '0',
  `creation_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `payment_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `date` longtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `payment_method` longtext COLLATE utf8mb4_unicode_520_ci,
  `payment_details` longtext COLLATE utf8mb4_unicode_520_ci,
  `account_id` int DEFAULT NULL,
  `status` longtext COLLATE utf8mb4_unicode_520_ci,
  `track_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `p_prev_paid` double NOT NULL DEFAULT '0',
  `prev_paid` double NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`accounts_payable_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_type`
--

DROP TABLE IF EXISTS `account_type`;
CREATE TABLE IF NOT EXISTS `account_type` (
  `account_type_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`account_type_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addition`
--

DROP TABLE IF EXISTS `addition`;
CREATE TABLE IF NOT EXISTS `addition` (
  `addi_id` int NOT NULL AUTO_INCREMENT,
  `salary_id` int NOT NULL,
  `basic` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `medical` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `house_rent` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `conveyance` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`addi_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_code` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `other_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `ssnit_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ghana_card_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `level` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `account_number` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `tier2_provider_id` int DEFAULT NULL COMMENT 'FK to pension_tier2_providers',
  `tier2_member_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Employee ID with Tier 2 provider',
  `account_details` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `can_collect_daily_fees` tinyint(1) DEFAULT '0' COMMENT '1=can collect fees, 0=cannot',
  `collection_point` enum('classroom','office','bus') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `transport_only` tinyint(1) DEFAULT '0' COMMENT '1=can only collect transport fare, 0=can collect all fees',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`admin_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `tier2_provider_id` (`tier2_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_category`
--

DROP TABLE IF EXISTS `admission_category`;
CREATE TABLE IF NOT EXISTS `admission_category` (
  `admission_category_id` int NOT NULL AUTO_INCREMENT,
  `admission_category_name` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`admission_category_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_logs`
--

DROP TABLE IF EXISTS `admission_logs`;
CREATE TABLE IF NOT EXISTS `admission_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `admitted_by` int NOT NULL COMMENT 'Admin ID who admitted the student',
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `residence_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `bill_items` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'JSON array of bill items with amounts',
  `total_bill_amount` decimal(10,2) NOT NULL,
  `admission_date` int NOT NULL,
  `created_at` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  KEY `admitted_by` (`admitted_by`),
  KEY `admission_date` (`admission_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aggregation`
--

DROP TABLE IF EXISTS `aggregation`;
CREATE TABLE IF NOT EXISTS `aggregation` (
  `aggregate_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `exam_id` int DEFAULT NULL,
  `aggregate_mark` double DEFAULT NULL,
  `raw_score` double DEFAULT NULL,
  `grand_total` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `attitude` longtext COLLATE utf8mb4_unicode_520_ci,
  `conduct` longtext COLLATE utf8mb4_unicode_520_ci,
  `interest` longtext COLLATE utf8mb4_unicode_520_ci,
  `c1` longtext COLLATE utf8mb4_unicode_520_ci,
  `c2` longtext COLLATE utf8mb4_unicode_520_ci,
  `c3` longtext COLLATE utf8mb4_unicode_520_ci,
  `c4` longtext COLLATE utf8mb4_unicode_520_ci,
  `c5` longtext COLLATE utf8mb4_unicode_520_ci,
  `c6` longtext COLLATE utf8mb4_unicode_520_ci,
  `c7` longtext COLLATE utf8mb4_unicode_520_ci,
  `c8` longtext COLLATE utf8mb4_unicode_520_ci,
  `c9` longtext COLLATE utf8mb4_unicode_520_ci,
  `c10` longtext COLLATE utf8mb4_unicode_520_ci,
  `c11` longtext COLLATE utf8mb4_unicode_520_ci,
  `c12` longtext COLLATE utf8mb4_unicode_520_ci,
  `c13` longtext COLLATE utf8mb4_unicode_520_ci,
  `c14` longtext COLLATE utf8mb4_unicode_520_ci,
  `c15` longtext COLLATE utf8mb4_unicode_520_ci,
  `remarks` longtext COLLATE utf8mb4_unicode_520_ci,
  `head_teacher_remarks` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_teacher_remarks` longtext COLLATE utf8mb4_unicode_520_ci,
  `days_opened` int DEFAULT '0',
  `days_present` int DEFAULT '0',
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`aggregate_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aging_report_snapshots`
--

DROP TABLE IF EXISTS `aging_report_snapshots`;
CREATE TABLE IF NOT EXISTS `aging_report_snapshots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL COMMENT 'Date of snapshot',
  `student_id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `invoice_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `days_outstanding` int NOT NULL,
  `age_category` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'current, 30days, 60days, 90days',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `snapshot_date` (`snapshot_date`),
  KEY `student_id` (`student_id`),
  KEY `age_category` (`age_category`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Historical aging report snapshots';

-- --------------------------------------------------------

--
-- Table structure for table `alumni`
--

DROP TABLE IF EXISTS `alumni`;
CREATE TABLE IF NOT EXISTS `alumni` (
  `alumni_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `year_batch` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`alumni_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Alumni, list of completed students';

-- --------------------------------------------------------

--
-- Table structure for table `approval_requests`
--

DROP TABLE IF EXISTS `approval_requests`;
CREATE TABLE IF NOT EXISTS `approval_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_type` enum('invoice_edit','invoice_delete','payment_edit','payment_delete') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `record_id` int NOT NULL COMMENT 'ID of invoice or payment',
  `record_type` enum('invoice','payment') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `requested_by` int NOT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `handled_by` int DEFAULT NULL,
  `handled_at` timestamp NULL DEFAULT NULL,
  `handler_notes` text COLLATE utf8mb4_unicode_520_ci,
  `record_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Snapshot of record at request time',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_type`,`record_id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_methods_master`
--

DROP TABLE IF EXISTS `assessment_methods_master`;
CREATE TABLE IF NOT EXISTS `assessment_methods_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `display_order` int DEFAULT '0',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
CREATE TABLE IF NOT EXISTS `assets` (
  `ass_id` int NOT NULL AUTO_INCREMENT,
  `catid` varchar(14) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `ass_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ass_brand` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ass_model` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ass_code` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `configuration` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `purchasing_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ass_price` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ass_qty` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `in_stock` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`ass_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets_category`
--

DROP TABLE IF EXISTS `assets_category`;
CREATE TABLE IF NOT EXISTS `assets_category` (
  `cat_id` int NOT NULL AUTO_INCREMENT,
  `cat_status` enum('ASSETS','LOGISTIC') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'ASSETS',
  `cat_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`cat_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assign_leave`
--

DROP TABLE IF EXISTS `assign_leave`;
CREATE TABLE IF NOT EXISTS `assign_leave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `app_id` varchar(11) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `type_id` int NOT NULL,
  `day` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `hour` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `total_day` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `dateyear` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assign_task`
--

DROP TABLE IF EXISTS `assign_task`;
CREATE TABLE IF NOT EXISTS `assign_task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `project_id` int NOT NULL,
  `assign_user` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_type` enum('Team Head','Collaborators') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Collaborators',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `student_id` int NOT NULL,
  `class_routine_id` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `checked_in` int NOT NULL DEFAULT '0',
  `checked_in_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `checked_out` int NOT NULL DEFAULT '0',
  `checked_out_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `mute` enum('0','1') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `feeding_charged` decimal(10,2) DEFAULT '0.00' COMMENT 'Feeding amount charged for this day',
  `breakfast_charged` decimal(10,2) DEFAULT '0.00' COMMENT 'Breakfast amount charged for this day',
  `classes_charged` decimal(10,2) DEFAULT '0.00' COMMENT 'Classes amount charged for this day',
  `water_charged` decimal(10,2) DEFAULT '0.00' COMMENT 'Water amount charged for this day',
  `transport_charged` decimal(10,2) DEFAULT '0.00' COMMENT 'Transport amount charged for this day',
  `transport_status` enum('none','in','out','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'none' COMMENT 'Transport usage for the day',
  `breakfast_opted` tinyint(1) DEFAULT '0' COMMENT '1=student took breakfast, 0=did not take',
  `payment_status` enum('paid','unpaid','partial','advance') COLLATE utf8mb4_unicode_520_ci DEFAULT 'unpaid',
  `marked_by` int DEFAULT NULL COMMENT 'User ID who marked attendance',
  `marked_by_role` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'teacher, cashier, conductor',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`attendance_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_attendance_student_id` (`student_id`),
  KEY `idx_attendance_timestamp` (`timestamp`(20)),
  KEY `idx_attendance_class_section_timestamp` (`class_id`,`section_id`,`timestamp`(20)),
  KEY `idx_attendance_student_timestamp` (`student_id`,`timestamp`(20)),
  KEY `idx_attendance_full_lookup` (`class_id`,`section_id`,`student_id`,`timestamp`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_billing_log`
--

DROP TABLE IF EXISTS `attendance_billing_log`;
CREATE TABLE IF NOT EXISTS `attendance_billing_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operation_type` enum('auto_bill','payment','bulk_payment','adjustment') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `performed_by` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_operation_date` (`operation_type`,`created_at`),
  KEY `idx_student_log` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` bigint NOT NULL AUTO_INCREMENT COMMENT 'Primary key for audit log entries',
  `user_id` int NOT NULL COMMENT 'User who performed the action',
  `module` varchar(50) NOT NULL COMMENT 'Module name (e.g., payroll, staff, student)',
  `action` varchar(50) NOT NULL COMMENT 'Action performed (create, update, delete, view)',
  `record_id` varchar(100) DEFAULT NULL COMMENT 'Identifier of the affected record',
  `before_data` text COMMENT 'JSON snapshot of data before change',
  `after_data` text COMMENT 'JSON snapshot of data after change',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of the user (supports IPv6)',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'Browser/device user agent string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when action occurred',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text,
  PRIMARY KEY (`log_id`),
  KEY `idx_module_action` (`module`,`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_id_created` (`user_id`,`created_at` DESC),
  KEY `idx_module_created_at` (`module`,`created_at` DESC),
  KEY `idx_module_created` (`module`,`created_at` DESC),
  KEY `idx_user_created` (`user_id`,`created_at` DESC),
  KEY `idx_module_record_created` (`module`,`record_id`,`created_at`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_module_action` (`created_at` DESC,`module`,`action`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Immutable audit trail for all payroll operations';

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE IF NOT EXISTS `audit_trail` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `record_type` enum('invoice','payment','journal','budget','expense','account','discount','other') COLLATE utf8mb4_general_ci NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('create','update','delete','lock','unlock','post','void','approve','reject') COLLATE utf8mb4_general_ci NOT NULL,
  `old_values` text COLLATE utf8mb4_general_ci COMMENT 'JSON encoded old values',
  `new_values` text COLLATE utf8mb4_general_ci COMMENT 'JSON encoded new values',
  `performed_by` int NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_record_type` (`record_type`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_performed_at` (`performed_at`),
  KEY `idx_composite` (`record_type`,`record_id`,`performed_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `bank_account_id` int NOT NULL AUTO_INCREMENT,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_type` enum('checking','savings','credit_card','mobile_money') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT 'GHS',
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `chart_account_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `branch` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `swift_code` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`bank_account_id`),
  KEY `chart_account_id` (`chart_account_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_account_id` int NOT NULL COMMENT 'Reference to bank account',
  `reconciliation_date` date NOT NULL COMMENT 'Date of reconciliation',
  `statement_date` date NOT NULL COMMENT 'Bank statement date',
  `statement_balance` decimal(12,2) NOT NULL COMMENT 'Balance per bank statement',
  `book_balance` decimal(12,2) NOT NULL COMMENT 'Balance per books',
  `difference` decimal(12,2) NOT NULL COMMENT 'Difference amount',
  `reconciled_items_count` int DEFAULT '0' COMMENT 'Number of items reconciled',
  `reconciled_amount` decimal(12,2) DEFAULT '0.00' COMMENT 'Total reconciled amount',
  `status` enum('pending','completed','reviewed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending' COMMENT 'Reconciliation status',
  `notes` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Reconciliation notes',
  `reconciled_by` int NOT NULL COMMENT 'User who performed reconciliation',
  `reviewed_by` int DEFAULT NULL COMMENT 'User who reviewed',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `reconciliation_date` (`reconciliation_date`),
  KEY `status` (`status`),
  KEY `idx_account_date` (`bank_account_id`,`reconciliation_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Bank reconciliation records';

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

DROP TABLE IF EXISTS `bank_transactions`;
CREATE TABLE IF NOT EXISTS `bank_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `bank_account_id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('deposit','withdrawal','transfer','fee','interest') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `payee_payer` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `journal_entry_id` int DEFAULT NULL,
  `is_reconciled` tinyint(1) DEFAULT '0',
  `reconciled_by` int DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `reconciled_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`transaction_id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `transaction_date` (`transaction_date`),
  KEY `journal_entry_id` (`journal_entry_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `beneficiary_list`
--

DROP TABLE IF EXISTS `beneficiary_list`;
CREATE TABLE IF NOT EXISTS `beneficiary_list` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `term` varchar(5) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `year_term` (`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benefit_category`
--

DROP TABLE IF EXISTS `benefit_category`;
CREATE TABLE IF NOT EXISTS `benefit_category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `discount_type_id` int DEFAULT NULL COMMENT 'Optional reference to discount_types for governance',
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `icon` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Visual icon (emoji) for category',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Active status flag',
  `created_by` int DEFAULT NULL COMMENT 'Admin ID who created this category',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`category_id`),
  KEY `idx_discount_type` (`discount_type_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_history`
--

DROP TABLE IF EXISTS `billing_history`;
CREATE TABLE IF NOT EXISTS `billing_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `billing_date` date NOT NULL,
  `billing_type` enum('feeding','classes','transport') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `amount_charged` decimal(10,2) DEFAULT '0.00',
  `amount_paid` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) DEFAULT '0.00',
  `benefit_category_id` int DEFAULT NULL,
  `discount_applied` decimal(10,2) DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_student_billing` (`student_id`,`billing_date`,`billing_type`),
  KEY `idx_billing_date` (`billing_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_category`
--

DROP TABLE IF EXISTS `bill_category`;
CREATE TABLE IF NOT EXISTS `bill_category` (
  `bill_category_id` int NOT NULL AUTO_INCREMENT,
  `bill_category_name` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`bill_category_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_item`
--

DROP TABLE IF EXISTS `bill_item`;
CREATE TABLE IF NOT EXISTS `bill_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `bill_category_id` int NOT NULL,
  `amount` double NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `description` (`description`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_bill_item_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_item_history`
--

DROP TABLE IF EXISTS `bill_item_history`;
CREATE TABLE IF NOT EXISTS `bill_item_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_item_id` int NOT NULL,
  `class_id` int NOT NULL,
  `term` int NOT NULL,
  `year` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `bill_item_amount` double NOT NULL DEFAULT '0',
  `timestamp` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `residence_type` enum('Day','Boarding','Both') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Day',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_group`
--

DROP TABLE IF EXISTS `blood_group`;
CREATE TABLE IF NOT EXISTS `blood_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `blood_group` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boarding_bed`
--

DROP TABLE IF EXISTS `boarding_bed`;
CREATE TABLE IF NOT EXISTS `boarding_bed` (
  `bed_id` int NOT NULL AUTO_INCREMENT,
  `bed_code` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `dormitory_id` int NOT NULL,
  `bed_status` enum('Available','Assigned','Maintenance','Unknown') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Available',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`bed_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boarding_dormitory`
--

DROP TABLE IF EXISTS `boarding_dormitory`;
CREATE TABLE IF NOT EXISTS `boarding_dormitory` (
  `dormitory_id` int NOT NULL AUTO_INCREMENT,
  `dormitory_name` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `dormitory_capacity` int DEFAULT NULL,
  `dormitory_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `dormitory_floor` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `dormitory_description` longtext COLLATE utf8mb4_unicode_520_ci,
  `house_id` int NOT NULL,
  `dormitory_prefect_id` int DEFAULT NULL,
  `dormitory_bed_capacity` bigint NOT NULL DEFAULT '0',
  `dormitory_status` enum('Available','Assigned','Maintenance','Unknown') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Available',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`dormitory_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boarding_house`
--

DROP TABLE IF EXISTS `boarding_house`;
CREATE TABLE IF NOT EXISTS `boarding_house` (
  `house_id` int NOT NULL AUTO_INCREMENT,
  `house_name` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `house_description` longtext COLLATE utf8mb4_unicode_520_ci,
  `house_image_link` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `house_master_id` int DEFAULT NULL,
  `house_gps_code` text COLLATE utf8mb4_unicode_520_ci,
  `house_prefect_id` int DEFAULT NULL,
  `house_capacity` bigint NOT NULL DEFAULT '0',
  `house_year_established` text COLLATE utf8mb4_unicode_520_ci,
  `house_user_fee` double NOT NULL DEFAULT '0',
  `house_status` enum('Available','Assigned','Maintenance','Unknown') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Available',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`house_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

DROP TABLE IF EXISTS `book`;
CREATE TABLE IF NOT EXISTS `book` (
  `book_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `author` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `price` longtext COLLATE utf8mb4_unicode_520_ci,
  `total_copies` int DEFAULT NULL,
  `issued_copies` int DEFAULT NULL,
  `status` longtext COLLATE utf8mb4_unicode_520_ci,
  `file_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`book_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book_request`
--

DROP TABLE IF EXISTS `book_request`;
CREATE TABLE IF NOT EXISTS `book_request` (
  `book_request_id` int NOT NULL AUTO_INCREMENT,
  `book_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `issue_start_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `issue_end_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `status` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`book_request_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
CREATE TABLE IF NOT EXISTS `budgets` (
  `budget_id` int NOT NULL AUTO_INCREMENT,
  `budget_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `fiscal_year` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','approved','active','closed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`budget_id`),
  KEY `fiscal_year` (`fiscal_year`),
  KEY `status` (`status`),
  KEY `idx_year_status` (`fiscal_year`,`status`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_lines`
--

DROP TABLE IF EXISTS `budget_lines`;
CREATE TABLE IF NOT EXISTS `budget_lines` (
  `budget_line_id` int NOT NULL AUTO_INCREMENT,
  `budget_id` int NOT NULL,
  `account_id` int NOT NULL,
  `budgeted_amount` decimal(15,2) NOT NULL,
  `actual_amount` decimal(15,2) DEFAULT '0.00',
  `variance` decimal(15,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `utilized_amount` decimal(10,2) DEFAULT '0.00',
  `utilization_percentage` decimal(5,2) DEFAULT '0.00',
  `remaining_amount` decimal(10,2) DEFAULT '0.00',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`budget_line_id`),
  KEY `budget_id` (`budget_id`),
  KEY `account_id` (`account_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_utilization_log`
--

DROP TABLE IF EXISTS `budget_utilization_log`;
CREATE TABLE IF NOT EXISTS `budget_utilization_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `budget_id` int NOT NULL,
  `budget_line_id` int NOT NULL,
  `expense_id` int DEFAULT NULL COMMENT 'Reference to expense',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount utilized',
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `recorded_by` int NOT NULL,
  `recorded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `budget_id` (`budget_id`),
  KEY `budget_line_id` (`budget_line_id`),
  KEY `expense_id` (`expense_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Budget utilization tracking';

-- --------------------------------------------------------

--
-- Table structure for table `bus_attendance`
--

DROP TABLE IF EXISTS `bus_attendance`;
CREATE TABLE IF NOT EXISTS `bus_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `route_id` int DEFAULT NULL,
  `attendance_date` int NOT NULL,
  `transport_direction` varchar(10) DEFAULT 'none',
  `boarded_in` tinyint(1) DEFAULT '0',
  `boarded_out` tinyint(1) DEFAULT '0',
  `in_time` varchar(10) DEFAULT NULL,
  `out_time` varchar(10) DEFAULT NULL,
  `in_fare` decimal(10,2) DEFAULT '0.00',
  `out_fare` decimal(10,2) DEFAULT '0.00',
  `total_fare` decimal(10,2) DEFAULT '0.00',
  `amount_charged` decimal(10,2) DEFAULT '0.00',
  `payment_status` varchar(20) DEFAULT 'pending',
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `payment_time` int DEFAULT NULL,
  `payment_source` varchar(20) DEFAULT NULL,
  `cash_collected` decimal(10,2) DEFAULT '0.00',
  `prepaid_deducted` decimal(10,2) DEFAULT '0.00',
  `collected_by` int DEFAULT NULL,
  `collected_by_role` varchar(20) DEFAULT NULL,
  `collection_point` varchar(20) DEFAULT 'office',
  `conductor_id` int DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `term` varchar(10) DEFAULT NULL,
  `sem` varchar(10) DEFAULT NULL,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `attendance_date` (`attendance_date`),
  KEY `route_id` (`route_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_category` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `parent_account_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `opening_balance` decimal(15,2) DEFAULT '0.00',
  `current_balance` decimal(15,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `parent_account_id` (`parent_account_id`),
  KEY `account_type` (`account_type`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id` varchar(40) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `timestamp` int UNSIGNED NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(11) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `category` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'Pre-School',
  `name_numeric` varchar(3) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `teacher_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`class_id`),
  KEY `idx_category` (`category`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_routine`
--

DROP TABLE IF EXISTS `class_routine`;
CREATE TABLE IF NOT EXISTS `class_routine` (
  `class_routine_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `time_start` int DEFAULT NULL,
  `time_end` int DEFAULT NULL,
  `time_start_min` int DEFAULT NULL,
  `time_end_min` int DEFAULT NULL,
  `day` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`class_routine_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `core_competencies`
--

DROP TABLE IF EXISTS `core_competencies`;
CREATE TABLE IF NOT EXISTS `core_competencies` (
  `competency_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`competency_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_applications`
--

DROP TABLE IF EXISTS `credit_applications`;
CREATE TABLE IF NOT EXISTS `credit_applications` (
  `application_id` int NOT NULL AUTO_INCREMENT,
  `credit_id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `invoice_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `applied_amount` decimal(10,2) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`application_id`),
  KEY `idx_credit_id` (`credit_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_applied_at` (`applied_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
CREATE TABLE IF NOT EXISTS `credit_notes` (
  `credit_note_id` int NOT NULL AUTO_INCREMENT,
  `credit_note_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` enum('pending','approved','applied','cancelled') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `created_at` int NOT NULL,
  `created_by` int NOT NULL,
  `approved_at` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `applied_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`credit_note_id`),
  UNIQUE KEY `unique_credit_note_number` (`credit_note_number`),
  KEY `idx_student` (`student_id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_system_logs`
--

DROP TABLE IF EXISTS `credit_system_logs`;
CREATE TABLE IF NOT EXISTS `credit_system_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `action` enum('credit_created','credit_applied','credit_adjusted','credit_expired') COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_id` int DEFAULT NULL,
  `reference_type` enum('payment','invoice','adjustment') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`log_id`),
  KEY `idx_student_action` (`student_id`,`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_content_standards`
--

DROP TABLE IF EXISTS `curriculum_content_standards`;
CREATE TABLE IF NOT EXISTS `curriculum_content_standards` (
  `content_standard_id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., B4.1.1.1',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_strand_id` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`content_standard_id`),
  UNIQUE KEY `unique_standard` (`code`,`sub_strand_id`),
  KEY `idx_sub_strand` (`sub_strand_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_indicators`
--

DROP TABLE IF EXISTS `curriculum_indicators`;
CREATE TABLE IF NOT EXISTS `curriculum_indicators` (
  `indicator_id` int NOT NULL AUTO_INCREMENT,
  `sub_strand_id` int NOT NULL COMMENT 'Reference to parent sub-strand',
  `indicator_code` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Unique code for the indicator (e.g., B1.1.1.1)',
  `indicator_text` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Full text description of the learning indicator',
  `display_order` int DEFAULT '0' COMMENT 'Order for displaying indicators within sub-strand',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`indicator_id`),
  UNIQUE KEY `unique_indicator` (`indicator_code`,`sub_strand_id`),
  KEY `idx_sub_strand` (`sub_strand_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='GES Curriculum Learning Indicators - specific learning outcomes';

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_learning_indicators`
--

DROP TABLE IF EXISTS `curriculum_learning_indicators`;
CREATE TABLE IF NOT EXISTS `curriculum_learning_indicators` (
  `indicator_id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., B4.1.1.1.1',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_standard_id` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`indicator_id`),
  UNIQUE KEY `unique_indicator` (`code`,`content_standard_id`),
  KEY `idx_content_standard` (`content_standard_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_strands`
--

DROP TABLE IF EXISTS `curriculum_strands`;
CREATE TABLE IF NOT EXISTS `curriculum_strands` (
  `strand_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `subject_id` int NOT NULL COMMENT 'References subject.subject_id',
  `class_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Class level this strand applies to',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`strand_id`),
  UNIQUE KEY `unique_strand_subject` (`name`,`subject_id`,`class_level`),
  KEY `idx_subject_class` (`subject_id`,`class_level`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_sub_strands`
--

DROP TABLE IF EXISTS `curriculum_sub_strands`;
CREATE TABLE IF NOT EXISTS `curriculum_sub_strands` (
  `sub_strand_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `strand_id` int NOT NULL,
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`sub_strand_id`),
  UNIQUE KEY `unique_sub_strand` (`name`,`strand_id`),
  KEY `idx_strand` (`strand_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_charge_log`
--

DROP TABLE IF EXISTS `daily_charge_log`;
CREATE TABLE IF NOT EXISTS `daily_charge_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `charge_date` int NOT NULL COMMENT 'Midnight timestamp of the day',
  `feeding_charged` decimal(10,2) DEFAULT '0.00',
  `breakfast_charged` decimal(10,2) DEFAULT '0.00',
  `classes_charged` decimal(10,2) DEFAULT '0.00',
  `water_charged` decimal(10,2) DEFAULT '0.00',
  `transport_charged` decimal(10,2) DEFAULT '0.00',
  `total_charged` decimal(10,2) DEFAULT '0.00',
  `charged_at` int NOT NULL,
  `synced_to_ledger` tinyint(1) DEFAULT '0' COMMENT 'Whether synced to student ledger',
  `ledger_entry_id` int DEFAULT NULL COMMENT 'Reference to student_ledger table',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_date` (`student_id`,`charge_date`),
  KEY `charge_date` (`charge_date`),
  KEY `idx_charge_sync` (`synced_to_ledger`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_fee_audit_log`
--

DROP TABLE IF EXISTS `daily_fee_audit_log`;
CREATE TABLE IF NOT EXISTS `daily_fee_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'charge, payment, adjustment, refund',
  `fee_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'feeding, breakfast, classes, water, transport',
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) DEFAULT '0.00',
  `balance_after` decimal(10,2) DEFAULT '0.00',
  `arrears_before` decimal(10,2) DEFAULT '0.00',
  `arrears_after` decimal(10,2) DEFAULT '0.00',
  `performed_by` int NOT NULL,
  `performed_by_role` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `reference_id` int DEFAULT NULL COMMENT 'Reference to transaction or attendance ID',
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `created_at` int NOT NULL,
  `modified_at` int NOT NULL,
  `modified_by` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`,`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_fee_rates`
--

DROP TABLE IF EXISTS `daily_fee_rates`;
CREATE TABLE IF NOT EXISTS `daily_fee_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `feeding_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Daily feeding/lunch charge',
  `breakfast_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Daily breakfast charge',
  `classes_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Daily classes charge',
  `water_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Weekly water charge',
  `breakfast_enabled` tinyint(1) DEFAULT '0' COMMENT '1=breakfast available, 0=not available',
  `water_enabled` tinyint(1) DEFAULT '0' COMMENT '1=water charge enabled, 0=disabled',
  `year` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` int DEFAULT NULL,
  `sem` int DEFAULT NULL,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`,`year`,`term`,`sem`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_fee_transactions`
--

DROP TABLE IF EXISTS `daily_fee_transactions`;
CREATE TABLE IF NOT EXISTS `daily_fee_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int NOT NULL,
  `payment_date` int NOT NULL,
  `feeding_amount` decimal(10,2) DEFAULT '0.00',
  `breakfast_amount` decimal(10,2) DEFAULT '0.00',
  `classes_amount` decimal(10,2) DEFAULT '0.00',
  `water_amount` decimal(10,2) DEFAULT '0.00',
  `transport_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_type` enum('arrears','current','advance','mixed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'current',
  `payment_method` int DEFAULT '1' COMMENT '1=cash, 2=cheque, 3=momo, 4=bank',
  `collected_by` int NOT NULL COMMENT 'User ID who collected payment',
  `collection_point` enum('classroom','office','bus') COLLATE utf8mb4_unicode_520_ci DEFAULT 'office',
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `year` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `residence_type` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Day or Boarding - captured at payment time',
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `synced_to_accounts` tinyint(1) DEFAULT '0',
  `journal_entry_id` int DEFAULT NULL,
  `synced_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `modified_at` int NOT NULL,
  `modified_by` int NOT NULL,
  `modification_count` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `student_id` (`student_id`,`payment_date`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_synced` (`synced_to_accounts`),
  KEY `idx_journal_entry` (`journal_entry_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_residence_type` (`residence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_fee_wallet`
--

DROP TABLE IF EXISTS `daily_fee_wallet`;
CREATE TABLE IF NOT EXISTS `daily_fee_wallet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `feeding_balance` decimal(10,2) DEFAULT '0.00' COMMENT 'Prepaid balance for feeding/lunch',
  `breakfast_balance` decimal(10,2) DEFAULT '0.00' COMMENT 'Prepaid balance for breakfast',
  `classes_balance` decimal(10,2) DEFAULT '0.00' COMMENT 'Prepaid balance for classes',
  `water_balance` decimal(10,2) DEFAULT '0.00' COMMENT 'Prepaid balance for water',
  `transport_balance` decimal(10,2) DEFAULT '0.00' COMMENT 'Prepaid balance for transport',
  `feeding_arrears` decimal(10,2) DEFAULT '0.00' COMMENT 'Outstanding feeding debt',
  `breakfast_arrears` decimal(10,2) DEFAULT '0.00' COMMENT 'Outstanding breakfast debt',
  `classes_arrears` decimal(10,2) DEFAULT '0.00' COMMENT 'Outstanding classes debt',
  `water_arrears` decimal(10,2) DEFAULT '0.00' COMMENT 'Outstanding water debt',
  `transport_arrears` decimal(10,2) DEFAULT '0.00' COMMENT 'Outstanding transport debt',
  `last_updated` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_transport_choices`
--

DROP TABLE IF EXISTS `daily_transport_choices`;
CREATE TABLE IF NOT EXISTS `daily_transport_choices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `choice_date` int NOT NULL COMMENT 'Date for which choice is made',
  `transport_direction` enum('none','in','out','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'none',
  `choice_made_at` int NOT NULL COMMENT 'When parent made the choice',
  `choice_made_by` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'parent, teacher, admin',
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Optional reason/note',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_date` (`student_id`,`choice_date`),
  KEY `student_id` (`student_id`,`choice_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
CREATE TABLE IF NOT EXISTS `department` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dep_name` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `desciplinary`
--

DROP TABLE IF EXISTS `desciplinary`;
CREATE TABLE IF NOT EXISTS `desciplinary` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `action` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `title` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `description` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `designation`
--

DROP TABLE IF EXISTS `designation`;
CREATE TABLE IF NOT EXISTS `designation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `des_name` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_applications`
--

DROP TABLE IF EXISTS `discount_applications`;
CREATE TABLE IF NOT EXISTS `discount_applications` (
  `application_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `profile_id` int NOT NULL,
  `discount_category` enum('invoice','daily_fees') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'invoice, daily_fee_transaction',
  `reference_id` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'invoice_code or transaction_id',
  `bill_item_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'school_fees, feeding, classes, etc',
  `original_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `final_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `year` int NOT NULL,
  `term` int DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_by` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`application_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_profile` (`profile_id`),
  KEY `idx_category` (`discount_category`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_applied_at` (`applied_at`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_reporting` (`discount_category`,`year`,`term`,`applied_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Enterprise discount application ledger';

-- --------------------------------------------------------

--
-- Table structure for table `discount_approvals`
--

DROP TABLE IF EXISTS `discount_approvals`;
CREATE TABLE IF NOT EXISTS `discount_approvals` (
  `approval_id` int NOT NULL AUTO_INCREMENT,
  `discount_type_id` int NOT NULL,
  `requested_by` int NOT NULL,
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_unicode_520_ci,
  `approval_notes` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`approval_id`),
  KEY `discount_type_id` (`discount_type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_audit_log`
--

DROP TABLE IF EXISTS `discount_audit_log`;
CREATE TABLE IF NOT EXISTS `discount_audit_log` (
  `audit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` bigint UNSIGNED DEFAULT NULL,
  `action` enum('created','modified','reversed','approved','rejected') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `entity_id` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `performed_by` int NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_performed_at` (`performed_at`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Comprehensive audit trail for discount operations';

-- --------------------------------------------------------

--
-- Table structure for table `discount_audit_trail`
--

DROP TABLE IF EXISTS `discount_audit_trail`;
CREATE TABLE IF NOT EXISTS `discount_audit_trail` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `entity_type` enum('discount_type','benefit_category','student_assignment') COLLATE utf8mb4_unicode_520_ci DEFAULT 'discount_type' COMMENT 'Type of entity being audited',
  `entity_id` int NOT NULL COMMENT 'ID of discount_type or benefit_category',
  `action` enum('created','updated','deleted','activated','deactivated') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_520_ci,
  `new_values` text COLLATE utf8mb4_unicode_520_ci,
  `changed_by` int NOT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_discount_type` (`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_categories`
--

DROP TABLE IF EXISTS `discount_categories`;
CREATE TABLE IF NOT EXISTS `discount_categories` (
  `category_id` tinyint NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_profiles`
--

DROP TABLE IF EXISTS `discount_profiles`;
CREATE TABLE IF NOT EXISTS `discount_profiles` (
  `profile_id` int NOT NULL AUTO_INCREMENT,
  `profile_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `discount_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `bill_item_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `discount_category` enum('invoice','daily_fees') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'invoice',
  `discount_method` enum('percentage','fixed') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `profile_name` (`profile_name`),
  KEY `idx_discount_category` (`discount_category`,`is_active`),
  KEY `idx_bill_item_ids` (`bill_item_ids`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_profile_rules`
--

DROP TABLE IF EXISTS `discount_profile_rules`;
CREATE TABLE IF NOT EXISTS `discount_profile_rules` (
  `rule_id` int NOT NULL AUTO_INCREMENT,
  `profile_id` int NOT NULL,
  `class_id` int DEFAULT NULL COMMENT 'NULL or 0 means all classes',
  `bill_category_id` int DEFAULT NULL COMMENT 'NULL means all bill categories',
  `discount_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`rule_id`),
  KEY `profile_id` (`profile_id`),
  KEY `class_id` (`class_id`),
  KEY `bill_category_id` (`bill_category_id`),
  KEY `idx_profile_class` (`profile_id`,`class_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_summary_cache`
--

DROP TABLE IF EXISTS `discount_summary_cache`;
CREATE TABLE IF NOT EXISTS `discount_summary_cache` (
  `cache_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `discount_category` enum('invoice','daily_fees') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `year` int NOT NULL,
  `term` int DEFAULT NULL,
  `profile_id` int DEFAULT NULL,
  `total_applications` int NOT NULL DEFAULT '0',
  `total_original_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_final_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `unique_students` int NOT NULL DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `idx_unique_summary` (`discount_category`,`year`,`term`,`profile_id`),
  KEY `idx_category_period` (`discount_category`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Aggregated discount statistics cache';

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
CREATE TABLE IF NOT EXISTS `document` (
  `document_id` int NOT NULL AUTO_INCREMENT,
  `title` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `file_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `file_path` longtext COLLATE utf8mb4_unicode_520_ci,
  `file_type` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `teacher_id` int DEFAULT NULL,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `start_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `end_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `subject_id` int DEFAULT NULL,
  `status` enum('Approved','Declined','Pending') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Pending',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`document_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dormitory`
--

DROP TABLE IF EXISTS `dormitory`;
CREATE TABLE IF NOT EXISTS `dormitory` (
  `dormitory_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `number_of_room` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`dormitory_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `earned_leave`
--

DROP TABLE IF EXISTS `earned_leave`;
CREATE TABLE IF NOT EXISTS `earned_leave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `present_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `hour` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `education`
--

DROP TABLE IF EXISTS `education`;
CREATE TABLE IF NOT EXISTS `education` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `edu_type` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `institute` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `result` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `year` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
CREATE TABLE IF NOT EXISTS `email_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `subject` varchar(500) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` enum('invoice','receipt','notification','other') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `reference_id` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sent_at` int NOT NULL,
  `status` enum('sent','failed','pending') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_520_ci,
  `sent_by` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `idx_recipient` (`recipient`),
  KEY `idx_type` (`type`),
  KEY `idx_reference` (`reference_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `subject` varchar(500) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` enum('sent','failed') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'sent',
  `sent_at` datetime NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`),
  KEY `status` (`status`),
  KEY `sent_at` (`sent_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
CREATE TABLE IF NOT EXISTS `employee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_code` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `des_id` int DEFAULT NULL,
  `dep_id` int DEFAULT NULL,
  `first_name` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_email` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_password` varchar(512) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `em_role` enum('ADMIN','EMPLOYEE','SUPER ADMIN') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'EMPLOYEE',
  `em_address` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'ACTIVE',
  `em_gender` enum('Male','Female') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Male',
  `em_phone` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_phone2` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_birthday` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_blood_group` enum('O+','O-','A+','A-','B+','B-','AB+','OB+') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_joining_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_contact_end` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_image` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `em_nid` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_file`
--

DROP TABLE IF EXISTS `employee_file`;
CREATE TABLE IF NOT EXISTS `employee_file` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `file_title` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `file_url` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_address`
--

DROP TABLE IF EXISTS `emp_address`;
CREATE TABLE IF NOT EXISTS `emp_address` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `city` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `country` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `address` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `type` enum('Present','Permanent') COLLATE utf8mb4_unicode_520_ci DEFAULT 'Present',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_assets`
--

DROP TABLE IF EXISTS `emp_assets`;
CREATE TABLE IF NOT EXISTS `emp_assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `assets_id` int NOT NULL,
  `given_date` date NOT NULL,
  `return_date` date NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_attendance`
--

DROP TABLE IF EXISTS `emp_attendance`;
CREATE TABLE IF NOT EXISTS `emp_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `atten_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `signin_time` time DEFAULT NULL,
  `signout_time` time DEFAULT NULL,
  `working_hour` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `place` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `absence` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `overtime` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `earnleave` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_bank_info`
--

DROP TABLE IF EXISTS `emp_bank_info`;
CREATE TABLE IF NOT EXISTS `emp_bank_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `holder_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `bank_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `branch_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `account_number` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `account_type` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_experience`
--

DROP TABLE IF EXISTS `emp_experience`;
CREATE TABLE IF NOT EXISTS `emp_experience` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `exp_company` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `exp_com_position` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `exp_com_address` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `exp_workduration` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_leave`
--

DROP TABLE IF EXISTS `emp_leave`;
CREATE TABLE IF NOT EXISTS `emp_leave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `em_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `typeid` int NOT NULL,
  `leave_type` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `start_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `end_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `leave_duration` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `apply_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `reason` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `leave_status` enum('Approve','Not Approve','Rejected') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Not Approve',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_penalty`
--

DROP TABLE IF EXISTS `emp_penalty`;
CREATE TABLE IF NOT EXISTS `emp_penalty` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `penalty_id` int NOT NULL,
  `penalty_desc` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_salary`
--

DROP TABLE IF EXISTS `emp_salary`;
CREATE TABLE IF NOT EXISTS `emp_salary` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `type_id` int NOT NULL,
  `total` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_training`
--

DROP TABLE IF EXISTS `emp_training`;
CREATE TABLE IF NOT EXISTS `emp_training` (
  `id` int NOT NULL,
  `trainig_id` int NOT NULL,
  `emp_id` int NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enroll`
--

DROP TABLE IF EXISTS `enroll`;
CREATE TABLE IF NOT EXISTS `enroll` (
  `enroll_id` int NOT NULL AUTO_INCREMENT,
  `enroll_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `transport_id` int DEFAULT NULL,
  `roll` int DEFAULT NULL,
  `date_added` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `status` enum('open','close') COLLATE utf8mb4_unicode_520_ci DEFAULT 'open',
  `status_attendance` enum('open','close') COLLATE utf8mb4_unicode_520_ci DEFAULT 'open',
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `mute` enum('0','1','2') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `residence_type` enum('Day','Boarding') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Day',
  `house_id` int DEFAULT NULL,
  `dormitory_id` int DEFAULT NULL,
  `bed_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`enroll_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterprise_exams`
--

DROP TABLE IF EXISTS `enterprise_exams`;
CREATE TABLE IF NOT EXISTS `enterprise_exams` (
  `exam_id` int NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `exam_type` enum('mid_term','end_term','mock','waec') COLLATE utf8mb4_general_ci NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','published') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`exam_id`),
  KEY `idx_class_year_term` (`class_id`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterprise_exam_subjects`
--

DROP TABLE IF EXISTS `enterprise_exam_subjects`;
CREATE TABLE IF NOT EXISTS `enterprise_exam_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `total_marks` int DEFAULT '100',
  `pass_mark` int DEFAULT '50',
  `weight_percentage` decimal(5,2) DEFAULT '100.00',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_subject` (`exam_id`,`subject_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterprise_student_marks`
--

DROP TABLE IF EXISTS `enterprise_student_marks`;
CREATE TABLE IF NOT EXISTS `enterprise_student_marks` (
  `mark_id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_score` decimal(5,2) DEFAULT '0.00',
  `exam_score` decimal(5,2) DEFAULT '0.00',
  `total_score` decimal(5,2) GENERATED ALWAYS AS ((`class_score` + `exam_score`)) STORED,
  `grade` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remark` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` int DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`mark_id`),
  UNIQUE KEY `unique_student_exam_subject` (`exam_id`,`student_id`,`subject_id`),
  KEY `idx_student_exam` (`student_id`,`exam_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enterprise_terminal_reports`
--

DROP TABLE IF EXISTS `enterprise_terminal_reports`;
CREATE TABLE IF NOT EXISTS `enterprise_terminal_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `total_score` decimal(8,2) DEFAULT NULL,
  `average_score` decimal(5,2) DEFAULT NULL,
  `aggregate` decimal(5,2) DEFAULT NULL,
  `position` int DEFAULT NULL,
  `out_of` int DEFAULT NULL,
  `attendance_present` int DEFAULT '0',
  `attendance_total` int DEFAULT '0',
  `conduct` text COLLATE utf8mb4_general_ci,
  `interest` text COLLATE utf8mb4_general_ci,
  `head_teacher_remarks` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','published') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `unique_student_exam` (`student_id`,`exam_id`),
  KEY `idx_class_year_term` (`class_id`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

DROP TABLE IF EXISTS `exam`;
CREATE TABLE IF NOT EXISTS `exam` (
  `exam_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `date` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `category_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`exam_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_category`
--

DROP TABLE IF EXISTS `exam_category`;
CREATE TABLE IF NOT EXISTS `exam_category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`category_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_marks`
--

DROP TABLE IF EXISTS `exam_marks`;
CREATE TABLE IF NOT EXISTS `exam_marks` (
  `mark_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `total_score` decimal(5,2) DEFAULT '0.00',
  `grade` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`mark_id`),
  UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`,`year`,`term`),
  KEY `idx_student` (`student_id`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses_enhanced`
--

DROP TABLE IF EXISTS `expenses_enhanced`;
CREATE TABLE IF NOT EXISTS `expenses_enhanced` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL COMMENT 'Date of expense',
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Expense description',
  `category_id` int NOT NULL COMMENT 'Expense category',
  `budget_line_id` int DEFAULT NULL,
  `vendor` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Vendor/Supplier name',
  `amount` decimal(10,2) NOT NULL COMMENT 'Expense amount',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'cash, bank_transfer, cheque, mobile_money',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Payment reference',
  `attachment` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Receipt/invoice file path',
  `notes` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Additional notes',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending' COMMENT 'Approval status',
  `requested_by` int NOT NULL COMMENT 'User who submitted',
  `approved_by` int DEFAULT NULL COMMENT 'User who approved/rejected',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'Approval timestamp',
  `rejection_reason` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Reason for rejection',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `linked_at` datetime DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_date_status` (`expense_date`,`status`),
  KEY `budget_line_id` (`budget_line_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Enhanced expense tracking with approval workflow';

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories_enhanced`
--

DROP TABLE IF EXISTS `expense_categories_enhanced`;
CREATE TABLE IF NOT EXISTS `expense_categories_enhanced` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Category name',
  `description` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Category description',
  `budget_code` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Budget account code',
  `requires_approval` tinyint(1) DEFAULT '1' COMMENT 'Requires approval flag',
  `approval_threshold` decimal(10,2) DEFAULT NULL COMMENT 'Auto-approve below this amount',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Expense categories';

-- --------------------------------------------------------

--
-- Table structure for table `expense_category`
--

DROP TABLE IF EXISTS `expense_category`;
CREATE TABLE IF NOT EXISTS `expense_category` (
  `expense_category_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'folder',
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`expense_category_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_collection_assignments`
--

DROP TABLE IF EXISTS `fee_collection_assignments`;
CREATE TABLE IF NOT EXISTS `fee_collection_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `can_collect_feeding` tinyint(1) DEFAULT '0',
  `can_collect_classes` tinyint(1) DEFAULT '0',
  `can_collect_transport` tinyint(1) DEFAULT '0',
  `can_collect_breakfast` tinyint(1) NOT NULL DEFAULT '0',
  `can_collect_water` tinyint(1) NOT NULL DEFAULT '0',
  `year` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`teacher_id`,`class_id`,`year`,`term`),
  KEY `teacher_id` (`teacher_id`),
  KEY `class_id` (`class_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_collection_modes`
--

DROP TABLE IF EXISTS `fee_collection_modes`;
CREATE TABLE IF NOT EXISTS `fee_collection_modes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mode_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
CREATE TABLE IF NOT EXISTS `fee_structures` (
  `structure_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `academic_year` int NOT NULL,
  `term` int NOT NULL,
  `fee_items` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'JSON array of fee items',
  `total_amount` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`structure_id`),
  KEY `class_id` (`class_id`),
  KEY `academic_year` (`academic_year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `field_visit`
--

DROP TABLE IF EXISTS `field_visit`;
CREATE TABLE IF NOT EXISTS `field_visit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` varchar(256) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `field_location` varchar(512) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `start_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `approx_end_date` varchar(28) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `total_days` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` varchar(500) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `actual_return_date` varchar(28) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` enum('Approved','Not Approve','Rejected') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Not Approve',
  `attendance_updated` varchar(11) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_audit_trail`
--

DROP TABLE IF EXISTS `finance_audit_trail`;
CREATE TABLE IF NOT EXISTS `finance_audit_trail` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('create','update','delete','post','void') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_520_ci,
  `new_values` text COLLATE utf8mb4_unicode_520_ci,
  `user_id` int NOT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`audit_id`),
  KEY `table_record` (`table_name`,`record_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_dashboard_cache`
--

DROP TABLE IF EXISTS `finance_dashboard_cache`;
CREATE TABLE IF NOT EXISTS `finance_dashboard_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cache_data` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `year` int NOT NULL,
  `term` int NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `cache_key_year_term` (`cache_key`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_alert_resolutions`
--

DROP TABLE IF EXISTS `financial_alert_resolutions`;
CREATE TABLE IF NOT EXISTS `financial_alert_resolutions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `alert_key` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `resolved_by` int NOT NULL,
  `resolved_at` int NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `alert_key` (`alert_key`),
  KEY `resolved_at` (`resolved_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_analytics_cache`
--

DROP TABLE IF EXISTS `financial_analytics_cache`;
CREATE TABLE IF NOT EXISTS `financial_analytics_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cache_data` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `period` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` int NOT NULL,
  `expires_at` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `unique_cache_key` (`cache_key`,`period`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_trail`
--

DROP TABLE IF EXISTS `financial_audit_trail`;
CREATE TABLE IF NOT EXISTS `financial_audit_trail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Module name (expense, budget, reconciliation, etc)',
  `action` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Action performed (create, update, delete, approve, etc)',
  `record_id` int NOT NULL COMMENT 'ID of affected record',
  `old_values` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON of old values',
  `new_values` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON of new values',
  `user_id` int NOT NULL COMMENT 'User who performed action',
  `user_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'admin, teacher, etc',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `module` (`module`),
  KEY `action` (`action`),
  KEY `record_id` (`record_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Audit trail for financial operations';

-- --------------------------------------------------------

--
-- Table structure for table `financial_integration_log`
--

DROP TABLE IF EXISTS `financial_integration_log`;
CREATE TABLE IF NOT EXISTS `financial_integration_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `integration_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'fee_to_accounts, invoice_to_accounts, etc',
  `source_table` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `source_id` int NOT NULL,
  `target_table` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `target_id` int DEFAULT NULL,
  `status` enum('pending','success','failed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` int NOT NULL,
  `processed_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `idx_integration_type` (`integration_type`),
  KEY `idx_status` (`status`),
  KEY `idx_source` (`source_table`,`source_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports_cache`
--

DROP TABLE IF EXISTS `financial_reports_cache`;
CREATE TABLE IF NOT EXISTS `financial_reports_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `report_parameters` text COLLATE utf8mb4_unicode_520_ci,
  `report_data` longtext COLLATE utf8mb4_unicode_520_ci,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`cache_id`),
  KEY `report_type` (`report_type`),
  KEY `expires_at` (`expires_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_periods`
--

DROP TABLE IF EXISTS `fiscal_periods`;
CREATE TABLE IF NOT EXISTS `fiscal_periods` (
  `period_id` int NOT NULL AUTO_INCREMENT,
  `fiscal_year_id` int NOT NULL,
  `period_name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `period_number` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed','locked') COLLATE utf8mb4_unicode_520_ci DEFAULT 'open',
  `closed_by` int DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`period_id`),
  KEY `fiscal_year_id` (`fiscal_year_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_years`
--

DROP TABLE IF EXISTS `fiscal_years`;
CREATE TABLE IF NOT EXISTS `fiscal_years` (
  `fiscal_year_id` int NOT NULL AUTO_INCREMENT,
  `year_name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('open','closed','locked') COLLATE utf8mb4_unicode_520_ci DEFAULT 'open',
  `is_current` tinyint(1) DEFAULT '0',
  `closed_by` int DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`fiscal_year_id`),
  UNIQUE KEY `year_name` (`year_name`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flutter_user`
--

DROP TABLE IF EXISTS `flutter_user`;
CREATE TABLE IF NOT EXISTS `flutter_user` (
  `flutter_id` int NOT NULL AUTO_INCREMENT,
  `flutter_name` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `flutter_phone` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `flutter_email` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `flutter_password` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`flutter_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_field_preferences`
--

DROP TABLE IF EXISTS `form_field_preferences`;
CREATE TABLE IF NOT EXISTS `form_field_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `field_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_field` (`field_name`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontend_events`
--

DROP TABLE IF EXISTS `frontend_events`;
CREATE TABLE IF NOT EXISTS `frontend_events` (
  `frontend_events_id` int NOT NULL AUTO_INCREMENT,
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`frontend_events_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontend_gallery`
--

DROP TABLE IF EXISTS `frontend_gallery`;
CREATE TABLE IF NOT EXISTS `frontend_gallery` (
  `frontend_gallery_id` int NOT NULL AUTO_INCREMENT,
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `date_added` int DEFAULT NULL,
  `image` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `show_on_website` int NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`frontend_gallery_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontend_gallery_image`
--

DROP TABLE IF EXISTS `frontend_gallery_image`;
CREATE TABLE IF NOT EXISTS `frontend_gallery_image` (
  `frontend_gallery_image_id` int NOT NULL AUTO_INCREMENT,
  `frontend_gallery_id` int DEFAULT NULL,
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `image` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`frontend_gallery_image_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontend_general_settings`
--

DROP TABLE IF EXISTS `frontend_general_settings`;
CREATE TABLE IF NOT EXISTS `frontend_general_settings` (
  `frontend_general_settings_id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`frontend_general_settings_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontend_news`
--

DROP TABLE IF EXISTS `frontend_news`;
CREATE TABLE IF NOT EXISTS `frontend_news` (
  `frontend_news_id` int NOT NULL AUTO_INCREMENT,
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `date_added` int DEFAULT NULL,
  `image` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`frontend_news_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

DROP TABLE IF EXISTS `grade`;
CREATE TABLE IF NOT EXISTS `grade` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `grade_point` longtext COLLATE utf8mb4_unicode_520_ci,
  `grade_point_numeric` double NOT NULL DEFAULT '0',
  `mark_from` double DEFAULT NULL,
  `mark_upto` double DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`grade_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_2`
--

DROP TABLE IF EXISTS `grade_2`;
CREATE TABLE IF NOT EXISTS `grade_2` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `grade_point` longtext COLLATE utf8mb4_unicode_520_ci,
  `mark_from` double DEFAULT NULL,
  `mark_upto` double DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`grade_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_creche`
--

DROP TABLE IF EXISTS `grade_creche`;
CREATE TABLE IF NOT EXISTS `grade_creche` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `full_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `abbrev` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`grade_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_message`
--

DROP TABLE IF EXISTS `group_message`;
CREATE TABLE IF NOT EXISTS `group_message` (
  `group_message_id` int NOT NULL AUTO_INCREMENT,
  `group_message_thread_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `sender` longtext COLLATE utf8mb4_unicode_520_ci,
  `message` longtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_status` int DEFAULT NULL,
  `attached_file_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`group_message_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_message_other`
--

DROP TABLE IF EXISTS `group_message_other`;
CREATE TABLE IF NOT EXISTS `group_message_other` (
  `member_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `group_message_thread` int NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`member_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_message_thread`
--

DROP TABLE IF EXISTS `group_message_thread`;
CREATE TABLE IF NOT EXISTS `group_message_thread` (
  `group_message_thread_id` int NOT NULL AUTO_INCREMENT,
  `group_message_thread_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `members` longtext COLLATE utf8mb4_unicode_520_ci,
  `group_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `last_message_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `created_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`group_message_thread_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hod_subjects`
--

DROP TABLE IF EXISTS `hod_subjects`;
CREATE TABLE IF NOT EXISTS `hod_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL COMMENT 'HOD teacher ID - references teacher.teacher_id',
  `subject_id` int NOT NULL COMMENT 'References subject.subject_id',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int NOT NULL COMMENT 'Admin user ID - references admin.admin_id',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hod_subject` (`teacher_id`,`subject_id`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_assigned_by` (`assigned_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday`
--

DROP TABLE IF EXISTS `holiday`;
CREATE TABLE IF NOT EXISTS `holiday` (
  `id` int NOT NULL AUTO_INCREMENT,
  `holiday_name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `from_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `to_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `number_of_days` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `year` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hubtel_transaction_logs`
--

DROP TABLE IF EXISTS `hubtel_transaction_logs`;
CREATE TABLE IF NOT EXISTS `hubtel_transaction_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `request_data` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `response_data` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `response_code` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `transaction_ref` (`transaction_ref`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incomplete_fee_transactions`
--

DROP TABLE IF EXISTS `incomplete_fee_transactions`;
CREATE TABLE IF NOT EXISTS `incomplete_fee_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `fees` text COLLATE utf8mb4_unicode_520_ci,
  `tendered` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_date` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_audit_log`
--

DROP TABLE IF EXISTS `inventory_audit_log`;
CREATE TABLE IF NOT EXISTS `inventory_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `performed_by` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','EXCLUDED') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_sync_attempt` datetime DEFAULT NULL,
  `sync_error_message` text COLLATE utf8mb4_unicode_ci,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`table_name`,`record_id`),
  KEY `idx_date` (`created_at`),
  KEY `idx_action` (`action_type`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_sync` (`last_sync_attempt`),
  KEY `idx_device` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_locations`
--

DROP TABLE IF EXISTS `inventory_locations`;
CREATE TABLE IF NOT EXISTS `inventory_locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `manager_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_products`
--

DROP TABLE IF EXISTS `inventory_products`;
CREATE TABLE IF NOT EXISTS `inventory_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_asset` tinyint(1) DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `cost_price` decimal(10,2) DEFAULT '0.00',
  `selling_price` decimal(10,2) DEFAULT '0.00',
  `quantity` int DEFAULT '0',
  `unit_of_measure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pieces',
  `reorder_level` int DEFAULT '10',
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_quantity` (`quantity`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchases`
--

DROP TABLE IF EXISTS `inventory_purchases`;
CREATE TABLE IF NOT EXISTS `inventory_purchases` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_id` int DEFAULT NULL COMMENT 'FK to inventory_suppliers',
  `purchase_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Invoice/PO number',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Total amount paid so far',
  `payment_status` enum('unpaid','partially_paid','fully_paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid' COMMENT 'Current payment status',
  `last_payment_date` date DEFAULT NULL COMMENT 'Date of most recent payment',
  `status` enum('pending','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL COMMENT 'FK to admin',
  `received_by` int DEFAULT NULL COMMENT 'FK to admin',
  `received_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_po_code` (`po_code`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_reference` (`reference_number`),
  KEY `idx_purchase_status_date` (`status`,`purchase_date`),
  KEY `fk_purchase_receiver` (`received_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_last_payment_date` (`last_payment_date`),
  KEY `idx_payment_status_date` (`payment_status`,`last_payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_items`
--

DROP TABLE IF EXISTS `inventory_purchase_items`;
CREATE TABLE IF NOT EXISTS `inventory_purchase_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_id` int NOT NULL COMMENT 'FK to inventory_purchases',
  `product_id` int NOT NULL COMMENT 'FK to inventory_products',
  `quantity` int NOT NULL,
  `cost_price` decimal(10,2) NOT NULL COMMENT 'Cost per unit',
  `total_cost` decimal(10,2) NOT NULL COMMENT 'cost_price * quantity',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_purchase` (`purchase_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_purchase_payments`
--

DROP TABLE IF EXISTS `inventory_purchase_payments`;
CREATE TABLE IF NOT EXISTS `inventory_purchase_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_id` int NOT NULL COMMENT 'FK to inventory_purchases',
  `payment_date` date NOT NULL COMMENT 'Date payment was made',
  `amount` decimal(10,2) NOT NULL COMMENT 'Payment amount',
  `payment_method_id` int NOT NULL COMMENT 'FK to payment_methods',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Transaction/cheque reference',
  `notes` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Payment notes',
  `recorded_by` int NOT NULL COMMENT 'FK to admin who recorded payment',
  `expenditure_payment_id` int DEFAULT NULL COMMENT 'FK to payment table entry',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_purchase` (`purchase_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_method` (`payment_method_id`),
  KEY `idx_recorded_by` (`recorded_by`),
  KEY `idx_expenditure_payment` (`expenditure_payment_id`),
  KEY `idx_purchase_payment_date` (`purchase_id`,`payment_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_returns`
--

DROP TABLE IF EXISTS `inventory_returns`;
CREATE TABLE IF NOT EXISTS `inventory_returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_sale_id` int NOT NULL COMMENT 'FK to inventory_sales',
  `return_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `return_reason` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Defective, Wrong item, Changed mind, Expired, Other',
  `return_notes` text COLLATE utf8mb4_unicode_ci,
  `refund_method` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Cash, 2=Account Credit, 3=Original Method',
  `processed_by` int NOT NULL COMMENT 'FK to admin',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_original_sale` (`original_sale_id`),
  KEY `idx_return_date` (`return_date`),
  KEY `idx_processed_by` (`processed_by`),
  KEY `idx_return_reason` (`return_reason`),
  KEY `idx_return_date_reason` (`return_date`,`return_reason`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_return_items`
--

DROP TABLE IF EXISTS `inventory_return_items`;
CREATE TABLE IF NOT EXISTS `inventory_return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL COMMENT 'FK to inventory_returns',
  `sale_item_id` int NOT NULL COMMENT 'FK to inventory_sale_items',
  `product_id` int NOT NULL COMMENT 'FK to inventory_products',
  `quantity_returned` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL COMMENT 'Original unit price',
  `refund_amount` decimal(10,2) NOT NULL COMMENT 'unit_price * quantity_returned',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_return` (`return_id`),
  KEY `idx_sale_item` (`sale_item_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sales`
--

DROP TABLE IF EXISTS `inventory_sales`;
CREATE TABLE IF NOT EXISTS `inventory_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` tinyint(1) DEFAULT '1' COMMENT '1=Cash, 2=Bank, 3=Mobile Money',
  `served_by` int DEFAULT NULL,
  `sale_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_served_by` (`served_by`),
  KEY `idx_date` (`sale_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sale_items`
--

DROP TABLE IF EXISTS `inventory_sale_items`;
CREATE TABLE IF NOT EXISTS `inventory_sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sale` (`sale_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock_movements`
--

DROP TABLE IF EXISTS `inventory_stock_movements`;
CREATE TABLE IF NOT EXISTS `inventory_stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `movement_type` enum('in','out','adjustment','transfer') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `performed_by` int NOT NULL,
  `movement_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_date` (`movement_date`),
  KEY `idx_type` (`movement_type`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_suppliers`
--

DROP TABLE IF EXISTS `inventory_suppliers`;
CREATE TABLE IF NOT EXISTS `inventory_suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

DROP TABLE IF EXISTS `invoice`;
CREATE TABLE IF NOT EXISTS `invoice` (
  `invoice_id` int NOT NULL AUTO_INCREMENT,
  `invoice_code` bigint DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `description` varchar(225) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `discount` double NOT NULL DEFAULT '0',
  `amount_paid` double DEFAULT NULL,
  `credit_applied` decimal(10,2) DEFAULT '0.00',
  `original_due` decimal(10,2) GENERATED ALWAYS AS ((`amount` - `amount_paid`)) STORED,
  `net_due` decimal(10,2) GENERATED ALWAYS AS (((`amount` - `amount_paid`) - `credit_applied`)) STORED,
  `due` double DEFAULT NULL,
  `due_date` date DEFAULT NULL COMMENT 'Payment due date',
  `discount_applied` tinyint(1) DEFAULT '0',
  `benefit_category_id` int DEFAULT NULL,
  `benefit_category_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `benefit_discount_type` enum('percentage','fixed') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `benefit_feeding_amount` decimal(10,2) DEFAULT '0.00',
  `benefit_classes_amount` decimal(10,2) DEFAULT '0.00',
  `benefit_tuition_amount` decimal(10,2) DEFAULT '0.00',
  `benefit_total_discount` decimal(10,2) DEFAULT '0.00',
  `creation_timestamp` int DEFAULT NULL,
  `payment_timestamp` int DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `payment_details` varchar(225) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `residence_type` enum('Day','Boarding') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Day',
  `year` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` int DEFAULT NULL,
  `sem` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `mute` enum('0','1') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `can_delete` enum('default','request','approved','declined','trash') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `can_edit` enum('default','request','approved','declined') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `delete_request_issuer_id` int DEFAULT NULL,
  `edit_request_issuer_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `locked_at` timestamp NULL DEFAULT NULL COMMENT 'When the record was locked',
  `locked_reason` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Why the record was locked',
  `approval_status` enum('none','pending','approved','rejected') COLLATE utf8mb4_unicode_520_ci DEFAULT 'none',
  `approval_requested_by` int DEFAULT NULL,
  `approval_requested_at` timestamp NULL DEFAULT NULL,
  `approval_handled_by` int DEFAULT NULL,
  `approval_handled_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_520_ci,
  `synced_to_ledger` tinyint(1) DEFAULT '0',
  `ledger_entry_id` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`invoice_id`),
  KEY `idx_student_term` (`student_id`,`year`,`term`),
  KEY `idx_invoice_code` (`invoice_code`),
  KEY `idx_status_year_term` (`status`,`year`,`term`),
  KEY `idx_student_invoice_code` (`student_id`,`invoice_code`),
  KEY `idx_benefit_category` (`benefit_category_id`),
  KEY `idx_invoice_credit` (`credit_applied`),
  KEY `idx_invoice_net_due` (`net_due`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_invoice_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_access_tokens`
--

DROP TABLE IF EXISTS `invoice_access_tokens`;
CREATE TABLE IF NOT EXISTS `invoice_access_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_code` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb3_unicode_ci NOT NULL,
  `expires_at` int NOT NULL,
  `created_at` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb3_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb3_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `invoice_code` (`invoice_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_discounts`
--

DROP TABLE IF EXISTS `invoice_discounts`;
CREATE TABLE IF NOT EXISTS `invoice_discounts` (
  `discount_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `invoice_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `discount_category` enum('invoice','daily_fees') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'invoice',
  `profile_id` int DEFAULT NULL,
  `discount_method` enum('percentage','fixed') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `reason` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `applied_by` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','pending_removal') COLLATE utf8mb4_unicode_520_ci DEFAULT 'approved',
  `rejection_reason` text COLLATE utf8mb4_unicode_520_ci,
  `year` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`discount_id`),
  KEY `idx_invoice` (`invoice_code`),
  KEY `idx_student` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_student_category_status` (`student_id`,`status`),
  KEY `idx_category_status` (`status`),
  KEY `idx_type_status` (`status`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_status_post` (`status`),
  KEY `idx_created_by_post` (`created_by`),
  KEY `idx_discount_category` (`discount_category`,`status`),
  KEY `idx_profile_category` (`profile_id`,`discount_category`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_discount_items`
--

DROP TABLE IF EXISTS `invoice_discount_items`;
CREATE TABLE IF NOT EXISTS `invoice_discount_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `discount_id` int NOT NULL COMMENT 'FK to invoice_discounts.id',
  `invoice_id` int NOT NULL COMMENT 'FK to invoice.invoice_id',
  `invoice_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int NOT NULL,
  `item_title` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `original_amount` decimal(10,2) NOT NULL COMMENT 'Original item amount before discount',
  `discount_amount` decimal(10,2) NOT NULL COMMENT 'Exact discount applied to this item',
  `discounted_amount` decimal(10,2) NOT NULL COMMENT 'Final amount after discount',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_discount_id` (`discount_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_invoice_code` (`invoice_code`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_modification_requests`
--

DROP TABLE IF EXISTS `invoice_modification_requests`;
CREATE TABLE IF NOT EXISTS `invoice_modification_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `invoice_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `request_type` enum('edit','delete') COLLATE utf8mb4_general_ci NOT NULL,
  `requested_by` int NOT NULL,
  `request_reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `old_data` text COLLATE utf8mb4_general_ci NOT NULL COMMENT 'JSON of original invoice items',
  `new_data` text COLLATE utf8mb4_general_ci COMMENT 'JSON of new invoice items for edit',
  `status` enum('pending','approved','declined') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `review_comment` text COLLATE utf8mb4_general_ci,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`request_id`),
  KEY `invoice_code` (`invoice_code`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payment_audit_log`
--

DROP TABLE IF EXISTS `invoice_payment_audit_log`;
CREATE TABLE IF NOT EXISTS `invoice_payment_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_type` enum('invoice','payment') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `record_id` int NOT NULL,
  `action` enum('create','edit','delete','lock','unlock','approve','reject') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `performed_by` int NOT NULL,
  `performed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_type`,`record_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payment_links`
--

DROP TABLE IF EXISTS `invoice_payment_links`;
CREATE TABLE IF NOT EXISTS `invoice_payment_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `linked_at` datetime NOT NULL,
  `linked_by` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `payment_id` (`payment_id`),
  KEY `linked_by` (`linked_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_sms_log`
--

DROP TABLE IF EXISTS `invoice_sms_log`;
CREATE TABLE IF NOT EXISTS `invoice_sms_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `reference_id` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sent_at` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb3_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb3_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `invoice_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `invoice_summary`;
CREATE TABLE IF NOT EXISTS `invoice_summary` (
`student_id` int
,`invoice_code` bigint
,`year` varchar(20)
,`term` int
,`student_name` longtext
,`student_code` longtext
,`class_name` varchar(11)
,`total_amount` double
,`total_paid` double
,`total_due` double
,`total_discount` decimal(10,2)
,`item_count` bigint
,`invoice_date` int
,`last_payment_date` int
,`payment_status` varchar(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
CREATE TABLE IF NOT EXISTS `journal_entries` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `entry_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('general','adjustment','closing','opening','reversal') COLLATE utf8mb4_unicode_520_ci DEFAULT 'general',
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'invoice, payment, expense, etc',
  `reference_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT '0.00',
  `total_credit` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','posted','void') COLLATE utf8mb4_unicode_520_ci DEFAULT 'draft',
  `posted_by` int DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `fiscal_year` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `fiscal_period` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `entry_number` (`entry_number`),
  KEY `entry_date` (`entry_date`),
  KEY `status` (`status`),
  KEY `reference` (`reference_type`,`reference_id`),
  KEY `idx_source` (`source_type`,`source_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

DROP TABLE IF EXISTS `journal_entry_lines`;
CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
  `line_id` int NOT NULL AUTO_INCREMENT,
  `entry_id` int NOT NULL,
  `account_id` int NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`line_id`),
  KEY `entry_id` (`entry_id`),
  KEY `account_id` (`account_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
CREATE TABLE IF NOT EXISTS `language` (
  `phrase_id` int NOT NULL AUTO_INCREMENT,
  `phrase` longtext COLLATE utf8mb4_unicode_520_ci,
  `english` longtext COLLATE utf8mb4_unicode_520_ci,
  `bengali` longtext COLLATE utf8mb4_unicode_520_ci,
  `spanish` longtext COLLATE utf8mb4_unicode_520_ci,
  `arabic` longtext COLLATE utf8mb4_unicode_520_ci,
  `dutch` longtext COLLATE utf8mb4_unicode_520_ci,
  `russian` longtext COLLATE utf8mb4_unicode_520_ci,
  `chinese` longtext COLLATE utf8mb4_unicode_520_ci,
  `turkish` longtext COLLATE utf8mb4_unicode_520_ci,
  `portuguese` longtext COLLATE utf8mb4_unicode_520_ci,
  `hungarian` longtext COLLATE utf8mb4_unicode_520_ci,
  `french` longtext COLLATE utf8mb4_unicode_520_ci,
  `greek` longtext COLLATE utf8mb4_unicode_520_ci,
  `german` longtext COLLATE utf8mb4_unicode_520_ci,
  `italian` longtext COLLATE utf8mb4_unicode_520_ci,
  `thai` longtext COLLATE utf8mb4_unicode_520_ci,
  `urdu` longtext COLLATE utf8mb4_unicode_520_ci,
  `hindi` longtext COLLATE utf8mb4_unicode_520_ci,
  `latin` longtext COLLATE utf8mb4_unicode_520_ci,
  `indonesian` longtext COLLATE utf8mb4_unicode_520_ci,
  `japanese` longtext COLLATE utf8mb4_unicode_520_ci,
  `korean` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`phrase_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_payment_settings`
--

DROP TABLE IF EXISTS `late_payment_settings`;
CREATE TABLE IF NOT EXISTS `late_payment_settings` (
  `setting_id` int NOT NULL AUTO_INCREMENT,
  `grace_period_days` int DEFAULT '7',
  `late_fee_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_520_ci DEFAULT 'fixed',
  `late_fee_value` decimal(10,2) DEFAULT '0.00',
  `reminder_days` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT '7,14,21',
  `restrict_exams` tinyint(1) DEFAULT '0',
  `restrict_reports` tinyint(1) DEFAULT '0',
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`setting_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `type_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `leave_day` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`type_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_notes`
--

DROP TABLE IF EXISTS `lesson_notes`;
CREATE TABLE IF NOT EXISTS `lesson_notes` (
  `lesson_note_id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL COMMENT 'References teacher.teacher_id',
  `class_id` int NOT NULL COMMENT 'References class.class_id',
  `subject_id` int NOT NULL COMMENT 'References subject.subject_id',
  `strand_id` int DEFAULT NULL COMMENT 'References curriculum_strands.strand_id',
  `sub_strand_id` int DEFAULT NULL COMMENT 'References curriculum_sub_strands.sub_strand_id',
  `content_standard_id` int DEFAULT NULL COMMENT 'References curriculum_content_standards.content_standard_id',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `week_number` tinyint NOT NULL COMMENT '1-12',
  `term` tinyint NOT NULL COMMENT '1-3',
  `lesson_date` date NOT NULL,
  `lesson_objectives` text COLLATE utf8mb4_unicode_ci,
  `lesson_activities` text COLLATE utf8mb4_unicode_ci,
  `lesson_content` longtext COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','pending','hod_reviewed','revision_requested','approved','declined') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `feedback` text COLLATE utf8mb4_unicode_ci COMMENT 'Feedback from admin/HOD',
  `hod_id` int DEFAULT NULL COMMENT 'References teacher.teacher_id (HOD)',
  `hod_review_date` datetime DEFAULT NULL,
  `hod_feedback` text COLLATE utf8mb4_unicode_ci,
  `admin_id` int DEFAULT NULL COMMENT 'References admin.admin_id',
  `admin_review_date` datetime DEFAULT NULL,
  `source_lesson_note_id` int DEFAULT NULL COMMENT 'Reference to original if copied',
  `version` int DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lesson_note_id`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_class_subject` (`class_id`,`subject_id`),
  KEY `idx_status` (`status`),
  KEY `idx_week_term` (`week_number`,`term`),
  KEY `idx_date` (`lesson_date`),
  KEY `idx_strand` (`strand_id`),
  KEY `idx_sub_strand` (`sub_strand_id`),
  KEY `idx_content_standard` (`content_standard_id`),
  KEY `idx_hod` (`hod_id`),
  KEY `idx_admin` (`admin_id`),
  KEY `fk_ln_subject` (`subject_id`),
  KEY `fk_ln_source` (`source_lesson_note_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_assessments`
--

DROP TABLE IF EXISTS `lesson_note_assessments`;
CREATE TABLE IF NOT EXISTS `lesson_note_assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `method_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_custom` tinyint DEFAULT '0' COMMENT '1 if custom entry, 0 if from predefined list',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_competencies`
--

DROP TABLE IF EXISTS `lesson_note_competencies`;
CREATE TABLE IF NOT EXISTS `lesson_note_competencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `competency_id` int NOT NULL COMMENT 'References core_competencies.competency_id',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_note_competency` (`lesson_note_id`,`competency_id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_competency` (`competency_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_indicators`
--

DROP TABLE IF EXISTS `lesson_note_indicators`;
CREATE TABLE IF NOT EXISTS `lesson_note_indicators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `indicator_id` int NOT NULL COMMENT 'References curriculum_learning_indicators.indicator_id',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_note_indicator` (`lesson_note_id`,`indicator_id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_indicator` (`indicator_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_notifications`
--

DROP TABLE IF EXISTS `lesson_note_notifications`;
CREATE TABLE IF NOT EXISTS `lesson_note_notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'References teacher.teacher_id or admin.admin_id based on user_type',
  `user_type` enum('teacher','hod','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'lesson_note',
  `reference_id` int NOT NULL COMMENT 'lesson_note_id',
  `is_read` tinyint DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_references`
--

DROP TABLE IF EXISTS `lesson_note_references`;
CREATE TABLE IF NOT EXISTS `lesson_note_references` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `page_numbers` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` enum('primary','supplementary') COLLATE utf8mb4_unicode_ci DEFAULT 'supplementary',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_resources`
--

DROP TABLE IF EXISTS `lesson_note_resources`;
CREATE TABLE IF NOT EXISTS `lesson_note_resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `resource_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_details` text COLLATE utf8mb4_unicode_ci,
  `quantity` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_custom` tinyint DEFAULT '0' COMMENT '1 if custom entry, 0 if from predefined list',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lesson_note_revisions`
--

DROP TABLE IF EXISTS `lesson_note_revisions`;
CREATE TABLE IF NOT EXISTS `lesson_note_revisions` (
  `revision_id` int NOT NULL AUTO_INCREMENT,
  `lesson_note_id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'References teacher.teacher_id or admin.admin_id based on user_type',
  `user_type` enum('teacher','hod','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'create, update, submit, endorse, approve, decline, request_revision',
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'JSON object of changed fields',
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `previous_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`revision_id`),
  KEY `idx_lesson_note` (`lesson_note_id`),
  KEY `idx_user` (`user_id`,`user_type`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `librarian`
--

DROP TABLE IF EXISTS `librarian`;
CREATE TABLE IF NOT EXISTS `librarian` (
  `librarian_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`librarian_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

DROP TABLE IF EXISTS `loan`;
CREATE TABLE IF NOT EXISTS `loan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `amount` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `interest_percentage` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `total_amount` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `total_pay` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `total_due` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `installment` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `loan_number` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `loan_details` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `approve_date` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `install_period` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` enum('Granted','Deny','Pause','Done') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Pause',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_installment`
--

DROP TABLE IF EXISTS `loan_installment`;
CREATE TABLE IF NOT EXISTS `loan_installment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `loan_number` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `install_amount` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pay_amount` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `app_date` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `receiver` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `install_no` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_registry`
--

DROP TABLE IF EXISTS `location_registry`;
CREATE TABLE IF NOT EXISTS `location_registry` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `priority` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_sync_status` enum('success','failed','partial','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'UTC',
  `sync_enabled` tinyint(1) DEFAULT '1',
  `sync_interval` int DEFAULT '15' COMMENT 'Sync interval in minutes',
  `description` text COLLATE utf8mb4_unicode_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_sync` (`last_sync_at`),
  KEY `idx_priority` (`priority`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistic_asset`
--

DROP TABLE IF EXISTS `logistic_asset`;
CREATE TABLE IF NOT EXISTS `logistic_asset` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `qty` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `entry_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistic_assign`
--

DROP TABLE IF EXISTS `logistic_assign`;
CREATE TABLE IF NOT EXISTS `logistic_assign` (
  `ass_id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int NOT NULL,
  `assign_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `project_id` int NOT NULL,
  `task_id` int NOT NULL,
  `log_qty` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `start_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `end_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `back_date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `back_qty` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `remarks` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`ass_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mark`
--

DROP TABLE IF EXISTS `mark`;
CREATE TABLE IF NOT EXISTS `mark` (
  `mark_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `exam_id` int DEFAULT NULL,
  `test1` double DEFAULT NULL,
  `group_work` double DEFAULT NULL,
  `test2` double DEFAULT NULL,
  `project` double DEFAULT NULL,
  `sub_total` double DEFAULT NULL,
  `term_exam` double DEFAULT NULL,
  `class_score` double DEFAULT NULL,
  `exam_score` double DEFAULT NULL,
  `mark_obtained` double DEFAULT NULL,
  `status` enum('1','0') COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `mark_total` double DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `mute` enum('0','1') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`mark_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `message_thread_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `message` longtext COLLATE utf8mb4_unicode_520_ci,
  `sender` longtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_status` int DEFAULT NULL,
  `attached_file_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`message_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_thread`
--

DROP TABLE IF EXISTS `message_thread`;
CREATE TABLE IF NOT EXISTS `message_thread` (
  `message_thread_id` int NOT NULL AUTO_INCREMENT,
  `message_thread_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `sender` longtext COLLATE utf8mb4_unicode_520_ci,
  `reciever` longtext COLLATE utf8mb4_unicode_520_ci,
  `last_message_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`message_thread_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mobile_money_payment`
--

DROP TABLE IF EXISTS `mobile_money_payment`;
CREATE TABLE IF NOT EXISTS `mobile_money_payment` (
  `mo_id` int NOT NULL AUTO_INCREMENT,
  `expense_category_id` int DEFAULT NULL,
  `title` longtext COLLATE utf8mb4_unicode_520_ci,
  `payment_type` longtext COLLATE utf8mb4_unicode_520_ci,
  `invoice_id` int DEFAULT NULL,
  `invoice_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `student_id` int DEFAULT NULL,
  `method` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `mo_a_paid` double DEFAULT NULL,
  `t_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `date_time` longtext COLLATE utf8mb4_unicode_520_ci,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`mo_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `non_teaching_staff`
--

DROP TABLE IF EXISTS `non_teaching_staff`;
CREATE TABLE IF NOT EXISTS `non_teaching_staff` (
  `staff_id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key for non-teaching staff',
  `staff_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Unique staff code for identification',
  `name` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Full name (legacy field)',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'First name',
  `other_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Middle name',
  `last_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Last name',
  `birthday` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Date of birth',
  `sex` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Gender',
  `religion` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Religion',
  `blood_group` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Blood group',
  `address` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Physical address',
  `phone` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Phone number',
  `email` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Email address',
  `password` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Hashed password for portal access',
  `ssnit_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'SSNIT number for pension',
  `ghana_card_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Ghana Card ID',
  `petra_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'PETRA ID',
  `tin` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Tax Identification Number',
  `account_number` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Bank account number',
  `tier2_provider_id` int DEFAULT NULL COMMENT 'FK to pension_tier2_providers',
  `tier2_member_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Employee ID with Tier 2 provider',
  `account_details` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Bank account details (JSON)',
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Authentication key for sessions',
  `designation` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Job title/designation',
  `position` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Department (e.g., Maintenance, Kitchen, Security)',
  `employment_date` date DEFAULT NULL COMMENT 'Date of employment',
  `employment_type` enum('permanent','contract','casual') COLLATE utf8mb4_unicode_520_ci DEFAULT 'permanent' COMMENT 'Type of employment',
  `social_links` mediumtext COLLATE utf8mb4_unicode_520_ci COMMENT 'Social media links (JSON)',
  `show_on_website` int DEFAULT '0' COMMENT 'Show on school website',
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Read notice IDs',
  `block_limit` int DEFAULT '0' COMMENT 'Block limit for portal access',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci DEFAULT '1' COMMENT 'Active status',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci DEFAULT '0' COMMENT 'Online status',
  `employment_category` enum('teacher','administrator','non_teaching_staff') COLLATE utf8mb4_unicode_520_ci DEFAULT 'non_teaching_staff' COMMENT 'Employment category - always non_teaching_staff for this table',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci DEFAULT 'yes' COMMENT 'Include in sync',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'SYNCED' COMMENT 'Sync status',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT NULL COMMENT 'Last modification timestamp',
  `last_modified_by` int DEFAULT NULL COMMENT 'User ID who last modified',
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Device ID for sync tracking',
  `version` int DEFAULT '0' COMMENT 'Version number for conflict resolution',
  `retry_count` int DEFAULT '0' COMMENT 'Sync retry count',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Last sync error message',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
  `qualification` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Educational qualifications',
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `staff_code` (`staff_code`),
  KEY `idx_staff_code` (`staff_code`),
  KEY `idx_active_status` (`active_status`),
  KEY `idx_employment_category` (`employment_category`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_department` (`department`),
  KEY `tier2_provider_id` (`tier2_provider_id`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Non-teaching staff records for payroll and HR management';

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

DROP TABLE IF EXISTS `notice`;
CREATE TABLE IF NOT EXISTS `notice` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `file_url` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `date` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `noticeboard`
--

DROP TABLE IF EXISTS `noticeboard`;
CREATE TABLE IF NOT EXISTS `noticeboard` (
  `notice_id` int NOT NULL AUTO_INCREMENT,
  `notice_title` longtext COLLATE utf8mb4_unicode_520_ci,
  `notice` longtext COLLATE utf8mb4_unicode_520_ci,
  `create_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `created_on` longtext COLLATE utf8mb4_unicode_520_ci,
  `status` int DEFAULT '1',
  `show_on_website` int DEFAULT '0',
  `image` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`notice_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'Admin ID who will receive the notification',
  `user_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'admin, teacher, student, parent',
  `type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Type: admission, payment, etc',
  `title` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON data with additional details',
  `icon` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `user_type` (`user_type`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_delivery_log`
--

DROP TABLE IF EXISTS `notification_delivery_log`;
CREATE TABLE IF NOT EXISTS `notification_delivery_log` (
  `log_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL COMMENT 'Related notification ID (references notifications.notification_id)',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Delivery channel (sms, email, in_app)',
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Recipient identifier (phone number, email, or user_id)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Delivery status (sent, failed, pending)',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Delivery attempt timestamp',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Error details if delivery failed',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`log_id`),
  KEY `idx_notification_id` (`notification_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for notification delivery attempts across all channels';

-- --------------------------------------------------------

--
-- Table structure for table `online_exam`
--

DROP TABLE IF EXISTS `online_exam`;
CREATE TABLE IF NOT EXISTS `online_exam` (
  `online_exam_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `title` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `exam_date` int DEFAULT NULL,
  `time_start` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `time_end` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `duration` mediumtext COLLATE utf8mb4_unicode_520_ci COMMENT 'duration in second',
  `minimum_percentage` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `instruction` longtext COLLATE utf8mb4_unicode_520_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `running_year` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `term` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`online_exam_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `online_exam_result`
--

DROP TABLE IF EXISTS `online_exam_result`;
CREATE TABLE IF NOT EXISTS `online_exam_result` (
  `online_exam_result_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `online_exam_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `answer_script` longtext COLLATE utf8mb4_unicode_520_ci,
  `obtained_mark` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `status` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `exam_started_timestamp` longtext COLLATE utf8mb4_unicode_520_ci,
  `result` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`online_exam_result_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

DROP TABLE IF EXISTS `owner`;
CREATE TABLE IF NOT EXISTS `owner` (
  `id` int NOT NULL,
  `owner_name` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `owner_position` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `note` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

DROP TABLE IF EXISTS `parent`;
CREATE TABLE IF NOT EXISTS `parent` (
  `parent_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `guardian_gender` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `guardian_is_the` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `address` longtext COLLATE utf8mb4_unicode_520_ci,
  `profession` longtext COLLATE utf8mb4_unicode_520_ci,
  `designation` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `father_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `father_phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `father_occupation` longtext COLLATE utf8mb4_unicode_520_ci,
  `mother_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `mother_phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `mother_occupation` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`parent_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `expense_category_id` int DEFAULT NULL,
  `title` longtext COLLATE utf8mb4_unicode_520_ci,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `account_id` int DEFAULT NULL,
  `journal_id` int DEFAULT NULL,
  `invoice_code` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `receipt_code` bigint DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `payment_method` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `is_printed` tinyint(1) DEFAULT '0' COMMENT 'Receipt printed status',
  `is_emailed` tinyint(1) DEFAULT '0' COMMENT 'Receipt emailed status',
  `printed_at` int DEFAULT NULL COMMENT 'Timestamp when printed',
  `emailed_at` int DEFAULT NULL COMMENT 'Timestamp when emailed',
  `synced_to_accounts` tinyint(1) DEFAULT '0',
  `journal_entry_id` int DEFAULT NULL,
  `synced_at` int DEFAULT NULL,
  `receipt_id` int DEFAULT NULL,
  `installment_id` int DEFAULT NULL,
  `transaction_id` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `bank_name` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `cheque_number` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `amount` double DEFAULT NULL,
  `issuer_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `account_type` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `due` double NOT NULL DEFAULT '0',
  `timestamp` int DEFAULT NULL,
  `day_timestamp` int DEFAULT NULL,
  `residence_type` enum('Day','Boarding') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Day',
  `year` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `term` int DEFAULT NULL,
  `sem` int DEFAULT NULL,
  `can_delete` enum('default','request','approved','declined','trash') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `can_edit` enum('default','request','approved','declined') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `delete_request_issuer_id` int DEFAULT NULL,
  `edit_request_issuer_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `locked_at` timestamp NULL DEFAULT NULL COMMENT 'When the record was locked',
  `locked_reason` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Why the record was locked',
  `approval_status` enum('none','pending','approved','rejected') COLLATE utf8mb4_unicode_520_ci DEFAULT 'none',
  `approval_requested_by` int DEFAULT NULL,
  `approval_requested_at` timestamp NULL DEFAULT NULL,
  `approval_handled_by` int DEFAULT NULL,
  `approval_handled_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`payment_id`),
  KEY `idx_student_term` (`student_id`,`year`,`term`),
  KEY `idx_student_day_timestamp` (`student_id`,`day_timestamp`),
  KEY `idx_receipt_id` (`receipt_id`),
  KEY `idx_installment_id` (`installment_id`),
  KEY `idx_printed` (`is_printed`),
  KEY `idx_emailed` (`is_emailed`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_payment_date_year` (`day_timestamp`,`year`),
  KEY `idx_invoice_lookup` (`invoice_id`,`invoice_code`),
  KEY `idx_payment_can_delete` (`can_delete`),
  KEY `idx_payment_report_composite` (`year`,`day_timestamp`,`can_delete`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_installments`
--

DROP TABLE IF EXISTS `payment_installments`;
CREATE TABLE IF NOT EXISTS `payment_installments` (
  `installment_id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NOT NULL,
  `installment_number` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `paid_date` datetime DEFAULT NULL,
  `status` enum('pending','paid','overdue','partial') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `late_fee` decimal(10,2) DEFAULT '0.00',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`installment_id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_plans`
--

DROP TABLE IF EXISTS `payment_plans`;
CREATE TABLE IF NOT EXISTS `payment_plans` (
  `plan_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `invoice_code` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `installments` int NOT NULL,
  `paid_installments` int DEFAULT '0',
  `frequency` enum('weekly','monthly','quarterly') COLLATE utf8mb4_unicode_520_ci DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `status` enum('active','completed','cancelled') COLLATE utf8mb4_unicode_520_ci DEFAULT 'active',
  `created_at` int NOT NULL,
  `created_by` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`plan_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_invoice` (`invoice_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_code` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` enum('hubtel','mtn_momo','vodafone_cash','airteltigo_money','paystack','stripe','flutterwave') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `gateway_ref` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `network` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` enum('pending','success','failed','cancelled') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `response_data` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `transaction_ref` (`transaction_ref`),
  KEY `student_id` (`student_id`),
  KEY `gateway_ref` (`gateway_ref`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_approvals`
--

DROP TABLE IF EXISTS `payroll_approvals`;
CREATE TABLE IF NOT EXISTS `payroll_approvals` (
  `approval_id` int NOT NULL AUTO_INCREMENT,
  `pay_id` int NOT NULL,
  `approver_user_id` int NOT NULL,
  `approver_role` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `action` enum('approved','rejected','submitted') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_520_ci,
  `action_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`approval_id`),
  KEY `idx_pay_id` (`pay_id`),
  KEY `idx_action_date` (`action_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Tracks approval workflow for payroll records - HR submission, manager approval/rejection, and finance payment confirmation';

-- --------------------------------------------------------

--
-- Table structure for table `payroll_audit_enhanced`
--

DROP TABLE IF EXISTS `payroll_audit_enhanced`;
CREATE TABLE IF NOT EXISTS `payroll_audit_enhanced` (
  `audit_id` bigint NOT NULL AUTO_INCREMENT,
  `pay_id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` enum('create','update','delete','approve','reject','submit','reopen') COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_changed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Field name that was changed (null for whole record actions)',
  `old_value` text COLLATE utf8mb4_unicode_ci COMMENT 'Previous value before change',
  `new_value` text COLLATE utf8mb4_unicode_ci COMMENT 'New value after change',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address of user making the change',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Browser user agent string',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_pay_id` (`pay_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_field_changed` (`field_changed`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Enhanced audit log for all payroll changes with field-level tracking';

--
-- Triggers `payroll_audit_enhanced`
--
DROP TRIGGER IF EXISTS `prevent_audit_log_delete`;
DELIMITER $$
CREATE TRIGGER `prevent_audit_log_delete` BEFORE DELETE ON `payroll_audit_enhanced` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Audit log records cannot be deleted. This is a security violation.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_form_field_preferences`
--

DROP TABLE IF EXISTS `payroll_form_field_preferences`;
CREATE TABLE IF NOT EXISTS `payroll_form_field_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `field_name` varchar(100) NOT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_field` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay_salary`
--

DROP TABLE IF EXISTS `pay_salary`;
CREATE TABLE IF NOT EXISTS `pay_salary` (
  `pay_id` int NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `month` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Payroll month name',
  `year` int NOT NULL COMMENT 'Payroll year',
  `paid_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_days` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `basic_salary` double NOT NULL COMMENT 'Basic monthly salary (base for SSNIT calculations)',
  `market_premium_allowance` double NOT NULL DEFAULT '0',
  `teaching_allowance` double NOT NULL DEFAULT '0',
  `responsibility_allowance` double NOT NULL DEFAULT '0',
  `rural_allowance` double NOT NULL DEFAULT '0',
  `extra_class_allowance` double NOT NULL DEFAULT '0',
  `other_allowances` double NOT NULL DEFAULT '0',
  `total_allowances` double DEFAULT '0',
  `gross_salary` double DEFAULT '0' COMMENT 'Gross Salary = Basic Salary + All Allowances',
  `ssnit` double DEFAULT '0' COMMENT 'SSNIT Tier 1 (13.5% of basic salary) - Employer contribution, NOT deducted from employee',
  `tier2_contribution` double DEFAULT '0' COMMENT 'SSNIT Tier 2 (5% of basic salary) - Employee contribution, deducted from gross salary',
  `tier2_provider_id` int DEFAULT NULL COMMENT 'FK to pension_tier2_providers - Tracks which pension provider receives Tier 2 contribution',
  `loan` double DEFAULT '0',
  `income_tax` double NOT NULL DEFAULT '0',
  `get_fund` double NOT NULL DEFAULT '0',
  `gnat_dues` double NOT NULL DEFAULT '0',
  `salary_advance` double NOT NULL DEFAULT '0',
  `nhil` double NOT NULL DEFAULT '0',
  `welfare_dues` double NOT NULL DEFAULT '0',
  `other_deductions` double DEFAULT '0' COMMENT 'Other deductions amount',
  `total_deductions` double NOT NULL DEFAULT '0',
  `total_deductions_without_ssnit` double NOT NULL DEFAULT '0',
  `net_salary` double DEFAULT '0' COMMENT 'Net Salary = Gross - (Tier 2 + Other Deductions). Tier 1 is NOT subtracted',
  `working_days` int NOT NULL DEFAULT '0',
  `days_present` int NOT NULL DEFAULT '0',
  `days_absent` int NOT NULL DEFAULT '0',
  `status` enum('Paid','Process') COLLATE utf8mb4_unicode_520_ci DEFAULT 'Paid',
  `approval_status` enum('draft','pending_approval','approved','rejected','paid') COLLATE utf8mb4_unicode_520_ci DEFAULT 'paid' COMMENT 'Approval workflow status for payroll records',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record last update timestamp',
  `paid_type` enum('Hand Cash','Bank') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Bank',
  `employment_category` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `paid_by` int NOT NULL,
  `reference` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `uk_employee_month_year` (`employee_code`,`month`,`year`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_employment_category` (`employment_category`),
  KEY `idx_employee_month_year` (`employee_code`,`month`,`year`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_created_at` (`created_at`),
  KEY `tier2_provider_id` (`tier2_provider_id`),
  KEY `idx_tier2_provider` (`tier2_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

DROP TABLE IF EXISTS `penalty`;
CREATE TABLE IF NOT EXISTS `penalty` (
  `id` int NOT NULL,
  `penalty_name` varchar(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_approvals_view`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `pending_approvals_view`;
CREATE TABLE IF NOT EXISTS `pending_approvals_view` (
`id` int
,`request_type` enum('invoice_edit','invoice_delete','payment_edit','payment_delete')
,`record_type` enum('invoice','payment')
,`record_id` int
,`requested_at` timestamp
,`reason` text
,`status` enum('pending','approved','rejected')
,`requested_by_name` longtext
,`record_code` bigint
,`student_name` longtext
);

-- --------------------------------------------------------

--
-- Table structure for table `pension_tier2_providers`
--

DROP TABLE IF EXISTS `pension_tier2_providers`;
CREATE TABLE IF NOT EXISTS `pension_tier2_providers` (
  `provider_id` int NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of Tier 2 pension provider',
  `provider_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Short code for provider',
  `description` text COLLATE utf8mb4_unicode_ci,
  `provider_address` text COLLATE utf8mb4_unicode_ci,
  `provider_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`provider_id`),
  UNIQUE KEY `provider_code` (`provider_code`),
  KEY `is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tier 2 pension provider companies';

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_aggregates`
--

DROP TABLE IF EXISTS `portfolio_aggregates`;
CREATE TABLE IF NOT EXISTS `portfolio_aggregates` (
  `aggregate_id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_general_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weekly_average` decimal(5,2) DEFAULT NULL COMMENT 'Average for specific week',
  `term_average` decimal(5,2) DEFAULT NULL COMMENT 'Overall term portfolio average',
  `total_assessments` int DEFAULT '0',
  `computed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`aggregate_id`),
  UNIQUE KEY `unique_student_subject_term` (`student_id`,`subject_id`,`year`,`term`,`semester`),
  KEY `idx_student_term` (`student_id`,`year`,`term`),
  KEY `idx_subject` (`subject_id`),
  KEY `idx_aggregate_class_year_term` (`class_id`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_assessment`
--

DROP TABLE IF EXISTS `portfolio_assessment`;
CREATE TABLE IF NOT EXISTS `portfolio_assessment` (
  `assessment_id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int DEFAULT NULL,
  `code` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `subject_id` int NOT NULL,
  `strand_score` double DEFAULT NULL,
  `week` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `timestamp` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `year` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `term` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`assessment_id`),
  UNIQUE KEY `assessment_id` (`assessment_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_audit_trail`
--

DROP TABLE IF EXISTS `portfolio_audit_trail`;
CREATE TABLE IF NOT EXISTS `portfolio_audit_trail` (
  `audit_id` bigint NOT NULL AUTO_INCREMENT,
  `action_type` enum('create','update','delete','compute','sync_sba') COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` enum('header','score','aggregate','sba') COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` bigint NOT NULL,
  `old_value` text COLLATE utf8mb4_general_ci,
  `new_value` text COLLATE utf8mb4_general_ci,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action_type`,`created_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_headers`
--

DROP TABLE IF EXISTS `portfolio_headers`;
CREATE TABLE IF NOT EXISTS `portfolio_headers` (
  `header_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_general_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `week_number` tinyint NOT NULL,
  `strand_topic` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `strand_id` int DEFAULT NULL,
  `sub_strand_id` int DEFAULT NULL,
  `indicator_id` int DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `max_score` decimal(5,2) DEFAULT '10.00',
  `status` enum('draft','published','locked') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`header_id`),
  KEY `idx_class_subject` (`class_id`,`subject_id`,`year`,`term`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_week` (`week_number`,`term`,`year`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_strand` (`strand_id`),
  KEY `idx_sub_strand` (`sub_strand_id`),
  KEY `idx_indicator` (`indicator_id`),
  KEY `idx_portfolio_class_year_term` (`class_id`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `portfolio_headers_with_curriculum`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `portfolio_headers_with_curriculum`;
CREATE TABLE IF NOT EXISTS `portfolio_headers_with_curriculum` (
`header_id` int
,`class_id` int
,`subject_id` int
,`teacher_id` int
,`year` varchar(10)
,`term` enum('1','2','3')
,`semester` enum('1','2')
,`week_number` tinyint
,`strand_topic` varchar(200)
,`strand_id` int
,`sub_strand_id` int
,`indicator_id` int
,`assessment_date` date
,`max_score` decimal(5,2)
,`status` enum('draft','published','locked')
,`created_by` int
,`created_at` timestamp
,`updated_at` timestamp
,`deleted_at` timestamp
,`strand_name` varchar(255)
,`sub_strand_name` varchar(255)
,`indicator_text` text
,`full_curriculum_path` text
);

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_scores`
--

DROP TABLE IF EXISTS `portfolio_scores`;
CREATE TABLE IF NOT EXISTS `portfolio_scores` (
  `score_id` bigint NOT NULL AUTO_INCREMENT,
  `header_id` int NOT NULL,
  `student_id` int NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recorded_by` int NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`score_id`),
  UNIQUE KEY `unique_student_header` (`header_id`,`student_id`,`deleted_at`),
  KEY `idx_student` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

DROP TABLE IF EXISTS `project`;
CREATE TABLE IF NOT EXISTS `project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pro_name` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_start_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_end_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_description` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_summary` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_status` enum('upcoming','complete','running') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'running',
  `progress` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_file`
--

DROP TABLE IF EXISTS `project_file`;
CREATE TABLE IF NOT EXISTS `project_file` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `file_details` varchar(1028) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `file_url` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `assigned_to` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pro_expenses`
--

DROP TABLE IF EXISTS `pro_expenses`;
CREATE TABLE IF NOT EXISTS `pro_expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `assign_to` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `details` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `amount` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pro_notes`
--

DROP TABLE IF EXISTS `pro_notes`;
CREATE TABLE IF NOT EXISTS `pro_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assign_to` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `pro_id` int NOT NULL,
  `details` varchar(1024) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pro_task`
--

DROP TABLE IF EXISTS `pro_task`;
CREATE TABLE IF NOT EXISTS `pro_task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `task_title` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `start_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `end_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `image` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `description` varchar(2048) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `task_type` enum('Office','Field') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Office',
  `status` enum('running','complete','cancel') COLLATE utf8mb4_unicode_520_ci DEFAULT 'running',
  `location` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `return_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `total_days` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `create_date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `approve_status` enum('Approved','Not Approve','Rejected') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Not Approve',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pro_task_assets`
--

DROP TABLE IF EXISTS `pro_task_assets`;
CREATE TABLE IF NOT EXISTS `pro_task_assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pro_task_id` int NOT NULL,
  `assign_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

DROP TABLE IF EXISTS `question_bank`;
CREATE TABLE IF NOT EXISTS `question_bank` (
  `question_bank_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `online_exam_id` int DEFAULT NULL,
  `question_title` longtext COLLATE utf8mb4_unicode_520_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `number_of_options` int DEFAULT NULL,
  `options` longtext COLLATE utf8mb4_unicode_520_ci,
  `correct_answers` longtext COLLATE utf8mb4_unicode_520_ci,
  `mark` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`question_bank_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_paper`
--

DROP TABLE IF EXISTS `question_paper`;
CREATE TABLE IF NOT EXISTS `question_paper` (
  `question_paper_id` int NOT NULL AUTO_INCREMENT,
  `title` longtext COLLATE utf8mb4_unicode_520_ci,
  `question_paper` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int DEFAULT NULL,
  `exam_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`question_paper_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `raw_score_grade`
--

DROP TABLE IF EXISTS `raw_score_grade`;
CREATE TABLE IF NOT EXISTS `raw_score_grade` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `grade_point` longtext COLLATE utf8mb4_unicode_520_ci,
  `grade_point_numeric` double NOT NULL DEFAULT '0',
  `mark_from` double DEFAULT NULL,
  `mark_upto` double DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`grade_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
CREATE TABLE IF NOT EXISTS `receipts` (
  `receipt_id` int NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_code` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','mobile_money','card','cheque') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `received_by` int NOT NULL,
  `received_date` datetime NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `is_printed` tinyint(1) DEFAULT '0',
  `is_emailed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_modification_audit`
--

DROP TABLE IF EXISTS `receipt_modification_audit`;
CREATE TABLE IF NOT EXISTS `receipt_modification_audit` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `request_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `action` enum('edit','delete') COLLATE utf8mb4_general_ci NOT NULL,
  `performed_by` int NOT NULL,
  `performed_at` int NOT NULL,
  `before_data` text COLLATE utf8mb4_general_ci,
  `after_data` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`audit_id`),
  KEY `idx_request` (`request_id`),
  KEY `idx_payment` (`payment_id`),
  KEY `idx_audit_date` (`performed_at`),
  KEY `idx_audit_receipt_code` (`receipt_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_modification_requests`
--

DROP TABLE IF EXISTS `receipt_modification_requests`;
CREATE TABLE IF NOT EXISTS `receipt_modification_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_id` int NOT NULL COMMENT 'FK to payment table',
  `request_type` enum('edit','delete') COLLATE utf8mb4_general_ci NOT NULL,
  `requested_by` int NOT NULL COMMENT 'User who requested',
  `requested_at` int NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected','revoked') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` int DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `revoked_by` int DEFAULT NULL,
  `revoked_at` int DEFAULT NULL,
  `original_data` text COLLATE utf8mb4_general_ci COMMENT 'JSON of original payment data',
  `new_data` text COLLATE utf8mb4_general_ci COMMENT 'JSON of new payment data (for edits)',
  `notification_sent` tinyint(1) DEFAULT '0',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`request_id`),
  KEY `idx_payment` (`payment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_pending_requests` (`status`,`requested_at`),
  KEY `idx_status_revoked` (`status`,`revoked_at`),
  KEY `idx_receipt_code` (`receipt_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reconciliation_items`
--

DROP TABLE IF EXISTS `reconciliation_items`;
CREATE TABLE IF NOT EXISTS `reconciliation_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reconciliation_id` int NOT NULL COMMENT 'Reference to reconciliation',
  `transaction_id` int NOT NULL COMMENT 'Transaction ID',
  `transaction_type` enum('book','bank') COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Source of transaction',
  `transaction_date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `debit` decimal(10,2) DEFAULT '0.00',
  `credit` decimal(10,2) DEFAULT '0.00',
  `amount` decimal(10,2) NOT NULL COMMENT 'Transaction amount',
  `matched` tinyint(1) DEFAULT '0' COMMENT 'Whether matched with counterpart',
  `matched_with_id` int DEFAULT NULL COMMENT 'ID of matched item',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `reconciliation_id` (`reconciliation_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `matched` (`matched`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='Reconciliation line items';

-- --------------------------------------------------------

--
-- Table structure for table `religion`
--

DROP TABLE IF EXISTS `religion`;
CREATE TABLE IF NOT EXISTS `religion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `religion` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT '',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

DROP TABLE IF EXISTS `request`;
CREATE TABLE IF NOT EXISTS `request` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `request_description` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `request_issuer_id` int NOT NULL,
  `request_table` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `request_ids` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'this should be string of ids since we can have more than one id',
  `request_created_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_modified_timestamp` datetime DEFAULT NULL,
  `response_timestamp` datetime DEFAULT NULL,
  `approved_by_id` int DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Declined') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Pending',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`request_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_approval_audit`
--

DROP TABLE IF EXISTS `result_approval_audit`;
CREATE TABLE IF NOT EXISTS `result_approval_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `approval_status_id` int NOT NULL,
  `action` enum('submit','approve','lock','unlock','reject') COLLATE utf8mb4_general_ci NOT NULL,
  `previous_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `new_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `performed_by` int NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_approval_status` (`approval_status_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_approval_status`
--

DROP TABLE IF EXISTS `result_approval_status`;
CREATE TABLE IF NOT EXISTS `result_approval_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `academic_year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('draft','submitted','approved','locked') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `submitted_by` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `locked_by` int DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_year_term` (`class_id`,`academic_year`,`term`),
  KEY `idx_status` (`status`),
  KEY `idx_class_year_term` (`class_id`,`academic_year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_type`
--

DROP TABLE IF EXISTS `salary_type`;
CREATE TABLE IF NOT EXISTS `salary_type` (
  `id` int NOT NULL AUTO_INCREMENT,
  `salary_type` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `create_date` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sba_components`
--

DROP TABLE IF EXISTS `sba_components`;
CREATE TABLE IF NOT EXISTS `sba_components` (
  `component_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_general_ci NOT NULL,
  `semester` enum('1','2') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `class_test` decimal(5,2) DEFAULT NULL COMMENT 'Auto-filled from portfolio',
  `applied_class_test_weight` decimal(5,2) DEFAULT NULL,
  `project_work` decimal(5,2) DEFAULT NULL,
  `applied_project_weight` decimal(5,2) DEFAULT NULL,
  `homework` decimal(5,2) DEFAULT NULL,
  `group_work` decimal(5,2) DEFAULT NULL,
  `applied_exam_weight` decimal(5,2) DEFAULT NULL,
  `total_sba` decimal(5,2) GENERATED ALWAYS AS ((((coalesce(`class_test`,0) + coalesce(`project_work`,0)) + coalesce(`homework`,0)) + coalesce(`group_work`,0))) STORED,
  `auto_filled` tinyint(1) DEFAULT '0' COMMENT 'True if class_test auto-filled from portfolio',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `weight_config_id` int DEFAULT NULL COMMENT 'Reference to sba_weight_config used',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`component_id`),
  UNIQUE KEY `unique_student_subject_sba` (`student_id`,`subject_id`,`year`,`term`,`semester`),
  KEY `idx_student_sba` (`student_id`,`year`,`term`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_weight_config` (`weight_config_id`),
  KEY `idx_sba_class_year_term` (`class_id`,`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sba_score_sources`
--

DROP TABLE IF EXISTS `sba_score_sources`;
CREATE TABLE IF NOT EXISTS `sba_score_sources` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `sba_component_id` int NOT NULL,
  `component_type` enum('class_test','project_work','homework','group_work') COLLATE utf8mb4_general_ci NOT NULL,
  `source_type` enum('portfolio','manual_entry','import','migration') COLLATE utf8mb4_general_ci NOT NULL,
  `source_reference_id` bigint DEFAULT NULL COMMENT 'portfolio_aggregates.id or import batch id',
  `original_value` decimal(5,2) DEFAULT NULL,
  `computed_value` decimal(5,2) NOT NULL,
  `computation_formula` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `computed_by` int NOT NULL,
  `computed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sba_component` (`sba_component_id`),
  KEY `idx_source_type` (`source_type`),
  KEY `idx_computed_by` (`computed_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sba_weight_config`
--

DROP TABLE IF EXISTS `sba_weight_config`;
CREATE TABLE IF NOT EXISTS `sba_weight_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` enum('1','2','3') COLLATE utf8mb4_general_ci NOT NULL,
  `class_category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'JHS, Upper Primary, SHS, etc',
  `class_test_weight` decimal(5,2) NOT NULL DEFAULT '30.00',
  `project_weight` decimal(5,2) NOT NULL DEFAULT '10.00',
  `exam_weight` decimal(5,2) NOT NULL DEFAULT '60.00',
  `portfolio_as_class_test` tinyint(1) DEFAULT '1' COMMENT 'Use portfolio for class test',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config` (`academic_year`,`term`,`class_category`,`deleted_at`),
  KEY `idx_year_term` (`academic_year`,`term`),
  KEY `idx_category` (`class_category`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
CREATE TABLE IF NOT EXISTS `section` (
  `section_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `nick_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`section_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sems`
--

DROP TABLE IF EXISTS `sems`;
CREATE TABLE IF NOT EXISTS `sems` (
  `sem_id` int NOT NULL,
  `sem_ending` longtext COLLATE utf8mb4_unicode_520_ci,
  `next_sem_begins` longtext COLLATE utf8mb4_unicode_520_ci,
  `full_payment_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`sem_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `settings_id` int NOT NULL AUTO_INCREMENT,
  `type` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`settings_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Triggers `settings`
--
DROP TRIGGER IF EXISTS `audit_settings_changes`;
DELIMITER $$
CREATE TRIGGER `audit_settings_changes` AFTER UPDATE ON `settings` FOR EACH ROW BEGIN
IF OLD.description != NEW.description THEN
INSERT INTO settings_audit (
setting_type,
old_value,
new_value,
changed_by,
sync_status
)
VALUES (
NEW.type,
OLD.description,
NEW.description,
USER(),
CASE
WHEN NEW.type LIKE 'last_sync%' THEN 'SYNCED'
WHEN NEW.type LIKE 'last_push_sync%' THEN 'SYNCED'
WHEN NEW.type LIKE 'last_pull_sync%' THEN 'SYNCED'
WHEN NEW.type = 'sync_in_progress' THEN 'SYNCED'
WHEN NEW.type = 'last_sync_status' THEN 'SYNCED'
WHEN NEW.type = 'last_sync_message' THEN 'SYNCED'
WHEN NEW.type LIKE 'sync_error_%' THEN 'SYNCED'
WHEN NEW.type LIKE '%_sync_time' THEN 'SYNCED'
ELSE 'PENDING'  -- All other settings remain PENDING for sync
END
);
END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `backup_password_on_change`;
DELIMITER $$
CREATE TRIGGER `backup_password_on_change` AFTER UPDATE ON `settings` FOR EACH ROW BEGIN
    IF NEW.type = 'remote_db_pass' AND OLD.description != NEW.description AND NEW.description != '' THEN
        INSERT INTO settings_backup (setting_type, setting_value)
        VALUES ('remote_db_pass', NEW.description);
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `prevent_empty_remote_password`;
DELIMITER $$
CREATE TRIGGER `prevent_empty_remote_password` BEFORE UPDATE ON `settings` FOR EACH ROW BEGIN
    IF NEW.type = 'remote_db_pass' AND (NEW.description IS NULL OR NEW.description = '') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot set remote_db_pass to empty value. Use Setup Wizard to update password.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `settings_audit`
--

DROP TABLE IF EXISTS `settings_audit`;
CREATE TABLE IF NOT EXISTS `settings_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `changed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_setting_type` (`setting_type`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_automations`
--

DROP TABLE IF EXISTS `sms_automations`;
CREATE TABLE IF NOT EXISTS `sms_automations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Automation name',
  `trigger_event` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Event that triggers SMS',
  `recipients` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Target recipients (parents, students, teachers, all)',
  `message_template` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'SMS template with placeholders',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_by` int DEFAULT NULL COMMENT 'Admin who created',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `trigger_days` int DEFAULT '0' COMMENT 'Days before/after event (negative for after)',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `trigger_event` (`trigger_event`),
  KEY `is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='SMS automation rules';

-- --------------------------------------------------------

--
-- Table structure for table `sms_automation_logs`
--

DROP TABLE IF EXISTS `sms_automation_logs`;
CREATE TABLE IF NOT EXISTS `sms_automation_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `automation_id` int NOT NULL COMMENT 'Reference to automation',
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Phone number',
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Recipient name',
  `message` text COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'Actual message sent',
  `status` enum('sent','failed','pending') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Error details if failed',
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `automation_id` (`automation_id`),
  KEY `status` (`status`),
  KEY `sent_at` (`sent_at`),
  KEY `idx_automation_date` (`automation_id`,`sent_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='SMS automation execution logs';

-- --------------------------------------------------------

--
-- Table structure for table `sms_log`
--

DROP TABLE IF EXISTS `sms_log`;
CREATE TABLE IF NOT EXISTS `sms_log` (
  `sms_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` enum('payment_reminder','receipt','statement','general') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `sent_at` int NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `response` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`sms_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `status` enum('sent','failed') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'sent',
  `sent_at` datetime NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `sent_at` (`sent_at`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_schedules`
--

DROP TABLE IF EXISTS `sms_schedules`;
CREATE TABLE IF NOT EXISTS `sms_schedules` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `schedule_type` enum('daily','weekly','monthly','once') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `schedule_time` time NOT NULL,
  `schedule_day` int DEFAULT NULL COMMENT 'Day of week (1-7) or day of month (1-31)',
  `target_criteria` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON criteria for selecting recipients',
  `is_active` tinyint(1) DEFAULT '1',
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`schedule_id`),
  KEY `idx_next_run` (`next_run`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
CREATE TABLE IF NOT EXISTS `sms_templates` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `variables` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON array of available variables',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social_media`
--

DROP TABLE IF EXISTS `social_media`;
CREATE TABLE IF NOT EXISTS `social_media` (
  `id` int NOT NULL,
  `emp_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `facebook` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `twitter` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `google_plus` varchar(512) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `skype_id` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
CREATE TABLE IF NOT EXISTS `student` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `student_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `name` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `birthday` longtext COLLATE utf8mb4_unicode_520_ci,
  `sex` longtext COLLATE utf8mb4_unicode_520_ci,
  `religion` longtext COLLATE utf8mb4_unicode_520_ci,
  `blood_group` longtext COLLATE utf8mb4_unicode_520_ci,
  `nationality` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'Ghanaian',
  `ghana_card_id` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_520_ci,
  `admission_date` date DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_520_ci,
  `medical_conditions` text COLLATE utf8mb4_unicode_520_ci,
  `emergency_contact` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `parent_email` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `student_phone` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `username` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `parent_id` int DEFAULT NULL,
  `dormitory_room_number` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `mute` enum('0','1') COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `special_diet` enum('0','1') COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `tribe` longtext COLLATE utf8mb4_unicode_520_ci,
  `former_school` longtext COLLATE utf8mb4_unicode_520_ci,
  `student_health` longtext COLLATE utf8mb4_unicode_520_ci,
  `student_special_diet_details` longtext COLLATE utf8mb4_unicode_520_ci,
  `class_reached` longtext COLLATE utf8mb4_unicode_520_ci,
  `can_delete` enum('default','request','approved','declined','trash') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `can_edit` enum('default','request','approved','declined') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'default',
  `delete_request_issuer_id` int DEFAULT NULL,
  `edit_request_issuer_id` int DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `place_of_birth` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `hometown` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `nhis_number` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `nhis_status` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `disability_status` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `learning_support` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `digital_literacy` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `home_technology_access` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `special_needs` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync_status` enum('SYNCED','PENDING','CONFLICT') COLLATE utf8mb4_unicode_520_ci DEFAULT 'SYNCED',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`student_id`),
  KEY `idx_first_name` (`first_name`),
  KEY `idx_last_name` (`last_name`),
  KEY `idx_ghana_card` (`ghana_card_id`),
  KEY `idx_admission_date` (`admission_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_account_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `student_account_summary`;
CREATE TABLE IF NOT EXISTS `student_account_summary` (
`student_id` int
,`student_name` longtext
,`parent_id` int
,`class_name` varchar(11)
,`total_billed` double
,`total_paid` double
,`total_outstanding` double
,`fully_paid_amount` double
,`unpaid_amount` double
,`total_invoices` bigint
,`year` varchar(20)
,`term` int
);

-- --------------------------------------------------------

--
-- Table structure for table `student_credits`
--

DROP TABLE IF EXISTS `student_credits`;
CREATE TABLE IF NOT EXISTS `student_credits` (
  `credit_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `credit_amount` decimal(10,2) NOT NULL,
  `source_payment_id` int DEFAULT NULL,
  `source_receipt_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_invoice_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `applied_amount` decimal(10,2) DEFAULT '0.00',
  `remaining_amount` decimal(10,2) GENERATED ALWAYS AS ((`credit_amount` - `applied_amount`)) STORED,
  `status` enum('active','fully_applied','expired','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`credit_id`),
  KEY `idx_student_active` (`student_id`,`status`),
  KEY `idx_remaining` (`remaining_amount`),
  KEY `idx_source_receipt` (`source_receipt_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_credit_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `student_credit_summary`;
CREATE TABLE IF NOT EXISTS `student_credit_summary` (
`student_id` int
,`student_name` longtext
,`total_available_credit` decimal(32,2)
,`total_credits_earned` decimal(32,2)
,`total_credits_used` decimal(32,2)
,`total_credit_records` bigint
,`last_credit_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `student_daily_fee_preferences`
--

DROP TABLE IF EXISTS `student_daily_fee_preferences`;
CREATE TABLE IF NOT EXISTS `student_daily_fee_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `breakfast_subscribed` tinyint(1) DEFAULT '0' COMMENT '1=parent subscribed to breakfast, 0=not subscribed',
  `water_subscribed` tinyint(1) DEFAULT '1' COMMENT '1=subscribed to water, 0=not subscribed',
  `auto_deduct_enabled` tinyint(1) DEFAULT '1' COMMENT '1=auto-deduct from balance, 0=manual payment',
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `updated_at` int DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discount_assignments`
--

DROP TABLE IF EXISTS `student_discount_assignments`;
CREATE TABLE IF NOT EXISTS `student_discount_assignments` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `profile_id` int NOT NULL,
  `discount_category` enum('invoice','daily_fees') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `discount_method` enum('percentage','fixed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'percentage',
  `discount_value` decimal(10,2) DEFAULT '0.00',
  `discount_type` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'For daily_fees: feeding,classes,water,etc',
  `bill_item_ids` text COLLATE utf8mb4_unicode_520_ci COMMENT 'For invoice: comma-separated IDs or *',
  `year` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `term` int DEFAULT NULL,
  `sem` int DEFAULT NULL,
  `assigned_by` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected','pending_removal') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `deactivated_at` int DEFAULT NULL,
  `deactivated_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`assignment_id`),
  KEY `student_id` (`student_id`),
  KEY `profile_id` (`profile_id`),
  KEY `year_term` (`year`,`term`),
  KEY `idx_student_year_term` (`student_id`,`year`,`term`,`is_active`),
  KEY `idx_discount_method` (`discount_method`),
  KEY `idx_status_pre` (`status`),
  KEY `idx_created_by_pre` (`created_by`),
  KEY `idx_profile_year_term` (`profile_id`,`year`,`term`,`is_active`),
  KEY `idx_category_status` (`discount_category`,`status`,`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_ledger`
--

DROP TABLE IF EXISTS `student_ledger`;
CREATE TABLE IF NOT EXISTS `student_ledger` (
  `ledger_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('invoice','payment','discount','credit_note','adjustment','refund') COLLATE utf8mb4_general_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reference_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `debit_amount` decimal(10,2) DEFAULT '0.00',
  `credit_amount` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`ledger_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

DROP TABLE IF EXISTS `subject`;
CREATE TABLE IF NOT EXISTS `subject` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `subject_type` enum('core','elective') COLLATE utf8mb4_unicode_520_ci DEFAULT 'elective',
  `status` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sem` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`subject_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject_category_creche`
--

DROP TABLE IF EXISTS `subject_category_creche`;
CREATE TABLE IF NOT EXISTS `subject_category_creche` (
  `category_id` int NOT NULL,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`category_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject_creche`
--

DROP TABLE IF EXISTS `subject_creche`;
CREATE TABLE IF NOT EXISTS `subject_creche` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `category_id` int DEFAULT NULL,
  `status` int DEFAULT NULL,
  `class_id` int DEFAULT '0',
  `teacher_id` int DEFAULT NULL,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`subject_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_audit_log`
--

DROP TABLE IF EXISTS `sync_audit_log`;
CREATE TABLE IF NOT EXISTS `sync_audit_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `table_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `operation` enum('INSERT','UPDATE','DELETE','CONFLICT','REVERT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_device_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `sync_direction` enum('push','pull') COLLATE utf8mb4_unicode_ci NOT NULL,
  `synced_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `synced_by` int DEFAULT NULL,
  `duration_ms` int DEFAULT NULL,
  `status` enum('success','failed','conflict') COLLATE utf8mb4_unicode_ci DEFAULT 'success',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `config_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Configuration key identifier',
  `config_value` text COLLATE utf8mb4_unicode_ci COMMENT 'Configuration value content',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Description of the change',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Auto-updating timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_device` (`source_device_id`),
  KEY `idx_synced_at` (`synced_at`),
  KEY `idx_operation` (`operation`),
  KEY `idx_status` (`status`),
  KEY `idx_direction` (`sync_direction`),
  KEY `idx_config_key` (`config_key`),
  KEY `idx_updated_at_audit` (`updated_at`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sync_config`
--

DROP TABLE IF EXISTS `sync_config`;
CREATE TABLE IF NOT EXISTS `sync_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_general_ci,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_conflicts`
--

DROP TABLE IF EXISTS `sync_conflicts`;
CREATE TABLE IF NOT EXISTS `sync_conflicts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `local_device_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remote_device_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_version` int NOT NULL,
  `remote_version` int NOT NULL,
  `local_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `remote_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `local_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remote_modified_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `conflict_strategy` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','resolved','ignored') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `resolution` enum('local_wins','remote_wins','merged','ignored') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int DEFAULT NULL,
  `merged_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  KEY `idx_local_device` (`local_device_id`),
  KEY `idx_remote_device` (`remote_device_id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sync_deletions`
--

DROP TABLE IF EXISTS `sync_deletions`;
CREATE TABLE IF NOT EXISTS `sync_deletions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the table where deletion occurred',
  `record_id` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON-encoded primary key(s) of deleted record',
  `deleted_at` datetime NOT NULL COMMENT 'Timestamp when record was deleted',
  `deleted_by` int UNSIGNED DEFAULT NULL COMMENT 'User ID who performed deletion (NULL for system)',
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Device that performed the deletion',
  `sync_status` enum('PENDING','SYNCED','FAILED','FAILED_PERMANENT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'Sync status of this deletion',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` datetime NOT NULL COMMENT 'Last modification timestamp',
  `version` int NOT NULL DEFAULT '1' COMMENT 'Version for optimistic locking',
  `retry_count` int NOT NULL DEFAULT '0' COMMENT 'Number of sync retry attempts',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Error message if sync failed',
  PRIMARY KEY (`id`),
  KEY `idx_sync_status_table` (`sync_status`,`table_name`),
  KEY `idx_table_deleted_at` (`table_name`,`deleted_at`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks DELETE operations for synchronization';

-- --------------------------------------------------------

--
-- Table structure for table `sync_devices`
--

DROP TABLE IF EXISTS `sync_devices`;
CREATE TABLE IF NOT EXISTS `sync_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `device_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `school_id` int DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','BLOCKED') COLLATE utf8mb4_general_ci DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_failures`
--

DROP TABLE IF EXISTS `sync_failures`;
CREATE TABLE IF NOT EXISTS `sync_failures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `record_id` int NOT NULL COMMENT 'ID of the record that failed to sync',
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the table containing the failed record',
  `operation` enum('push','pull') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Sync operation type that failed',
  `error_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Category of error (foreign_key, duplicate, constraint, network, etc.)',
  `error_message` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Detailed error message from the database or sync process',
  `error_details` json DEFAULT NULL COMMENT 'Additional error context in JSON format (e.g., constraint names, field values)',
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When the failure occurred',
  `retry_count` int DEFAULT '0' COMMENT 'Number of times retry has been attempted for this record',
  `resolved` tinyint(1) DEFAULT '0' COMMENT 'Whether the failure has been resolved (0=unresolved, 1=resolved)',
  `verification_status` enum('not_verified','verified_exists','verified_not_exists','verification_failed') COLLATE utf8mb4_unicode_ci DEFAULT 'not_verified' COMMENT 'Remote verification status for Task 6.4',
  PRIMARY KEY (`id`),
  KEY `idx_table_operation` (`table_name`,`operation`) COMMENT 'Query failures by table and operation type',
  KEY `idx_timestamp` (`timestamp`) COMMENT 'Query recent failures by time',
  KEY `idx_resolved` (`resolved`) COMMENT 'Filter unresolved failures for retry',
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_resolved_verification` (`resolved`,`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks individual record-level sync failures for analysis and retry';

-- --------------------------------------------------------

--
-- Table structure for table `sync_log`
--

DROP TABLE IF EXISTS `sync_log`;
CREATE TABLE IF NOT EXISTS `sync_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `sync_type` enum('PUSH','PULL') COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `records_count` int DEFAULT '0',
  `status` enum('SUCCESS','FAILED','PARTIAL') COLLATE utf8mb4_general_ci NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_details` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_type` (`sync_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_metadata`
--

DROP TABLE IF EXISTS `sync_metadata`;
CREATE TABLE IF NOT EXISTS `sync_metadata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sync_order` int DEFAULT '999',
  `last_pull_at` timestamp NULL DEFAULT NULL,
  `last_push_at` timestamp NULL DEFAULT NULL,
  `last_record_id` bigint DEFAULT '0',
  `last_synced_id` bigint UNSIGNED DEFAULT NULL COMMENT 'Highest auto-increment ID synced for audit tables (watermark)',
  `sync_enabled` tinyint(1) DEFAULT '1',
  `conflict_strategy` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'timestamp',
  `real_time_sync` tinyint(1) DEFAULT '0',
  `data_scope` enum('GLOBAL','LOCATION_LOCAL','LOCATION_SHARED') COLLATE utf8mb4_general_ci DEFAULT 'GLOBAL',
  `sync_targets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `priority` int DEFAULT '0',
  `batch_size` int DEFAULT '100',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pending_count` int UNSIGNED DEFAULT '0' COMMENT 'Cached count of PENDING records (updated during sync)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_name` (`table_name`),
  KEY `idx_sync_order` (`sync_order`),
  KEY `idx_last_synced_id` (`last_synced_id`),
  KEY `idx_sync_metadata_pending_count` (`sync_enabled`,`pending_count`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sync_metrics`
--

DROP TABLE IF EXISTS `sync_metrics`;
CREATE TABLE IF NOT EXISTS `sync_metrics` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `records_synced` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Number of records synced in this metric entry',
  `location_id` int DEFAULT NULL,
  `table_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  KEY `idx_metric_name` (`metric_name`),
  KEY `idx_recorded_at` (`recorded_at`),
  KEY `idx_location` (`location_id`),
  KEY `idx_table` (`table_name`),
  KEY `idx_records_synced` (`records_synced`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sync_notifications`
--

DROP TABLE IF EXISTS `sync_notifications`;
CREATE TABLE IF NOT EXISTS `sync_notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` enum('conflict','location_offline','sync_failure','sync_success') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sync_queue`
--

DROP TABLE IF EXISTS `sync_queue`;
CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `record_id` int DEFAULT NULL COMMENT 'Primary key value of the record',
  `operation` enum('insert','update','delete') COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `record_data` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_520_ci COMMENT 'JSON encoded record data (new format)',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `synced` tinyint(1) NOT NULL DEFAULT '0',
  `synced_at` timestamp NULL DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_520_ci COMMENT 'Error message if sync failed',
  `retry_count` int DEFAULT '0' COMMENT 'Number of retry attempts',
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'When the queue entry was created',
  `error_type` enum('remote_failure','local_status_failure','genuine_failure','verification_error','unknown') COLLATE utf8mb4_unicode_520_ci DEFAULT 'unknown' COMMENT 'Classification of error',
  `verification_status` enum('not_verified','verified_exists','verified_not_exists','verification_failed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'not_verified' COMMENT 'Remote verification status',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `synced` (`synced`),
  KEY `timestamp` (`timestamp`),
  KEY `idx_error_type` (`error_type`),
  KEY `idx_verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sync_settings`
--

DROP TABLE IF EXISTS `sync_settings`;
CREATE TABLE IF NOT EXISTS `sync_settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_brackets`
--

DROP TABLE IF EXISTS `tax_brackets`;
CREATE TABLE IF NOT EXISTS `tax_brackets` (
  `bracket_id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary key for tax bracket records',
  `country` varchar(50) DEFAULT 'Ghana' COMMENT 'Country for which tax bracket applies',
  `bracket_name` varchar(100) NOT NULL COMMENT 'Descriptive name for the tax bracket',
  `min_income` decimal(15,2) NOT NULL COMMENT 'Minimum annual income for this bracket',
  `max_income` decimal(15,2) DEFAULT NULL COMMENT 'Maximum annual income for this bracket (NULL = no upper limit)',
  `tax_rate` decimal(5,2) NOT NULL COMMENT 'Tax rate as percentage (e.g., 17.5 for 17.5%)',
  `fixed_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Fixed tax amount from previous brackets',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Whether this bracket is currently active',
  `effective_from` date NOT NULL COMMENT 'Date when this bracket becomes effective',
  `effective_to` date DEFAULT NULL COMMENT 'Date when this bracket expires (NULL = no expiry)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text,
  PRIMARY KEY (`bracket_id`),
  KEY `idx_effective_dates` (`effective_from`,`effective_to`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Progressive tax brackets for automated PAYE calculation';

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
CREATE TABLE IF NOT EXISTS `teacher` (
  `teacher_id` int NOT NULL AUTO_INCREMENT,
  `name` longtext COLLATE utf8mb4_unicode_520_ci,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `other_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `teacher_code` longtext COLLATE utf8mb4_unicode_520_ci,
  `birthday` longtext COLLATE utf8mb4_unicode_520_ci,
  `sex` longtext COLLATE utf8mb4_unicode_520_ci,
  `religion` longtext COLLATE utf8mb4_unicode_520_ci,
  `blood_group` longtext COLLATE utf8mb4_unicode_520_ci,
  `address` longtext COLLATE utf8mb4_unicode_520_ci,
  `phone` longtext COLLATE utf8mb4_unicode_520_ci,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `password` longtext COLLATE utf8mb4_unicode_520_ci,
  `ssnit_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `ghana_card_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `account_number` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `tier2_provider_id` int DEFAULT NULL COMMENT 'FK to pension_tier2_providers',
  `tier2_member_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL COMMENT 'Employee ID with Tier 2 provider',
  `account_details` longtext COLLATE utf8mb4_unicode_520_ci,
  `authentication_key` longtext COLLATE utf8mb4_unicode_520_ci,
  `designation` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `social_links` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `show_on_website` int DEFAULT '0',
  `read_notice_ids` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT '0',
  `block_limit` int DEFAULT '0',
  `active_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `online_status` enum('1','0') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `can_collect_daily_fees` tinyint(1) DEFAULT '0' COMMENT '1=can collect fees, 0=cannot',
  `collection_point` enum('classroom','office','bus') COLLATE utf8mb4_unicode_520_ci DEFAULT 'classroom',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`teacher_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `tier2_provider_id` (`tier2_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_privileges`
--

DROP TABLE IF EXISTS `teacher_privileges`;
CREATE TABLE IF NOT EXISTS `teacher_privileges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL COMMENT 'Foreign key to teacher table',
  `privilege_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'attendance_monitoring' COMMENT 'Type of privilege granted',
  `granted_by` int NOT NULL COMMENT 'Admin ID who granted the privilege',
  `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when privilege was granted',
  `status` enum('active','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Current status of the privilege',
  `revoked_by` int DEFAULT NULL COMMENT 'Admin ID who revoked the privilege',
  `revoked_at` datetime DEFAULT NULL COMMENT 'Timestamp when privilege was revoked',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Additional notes about the privilege',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_privilege` (`teacher_id`,`privilege_type`,`status`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_privilege_type` (`privilege_type`),
  KEY `idx_status` (`status`),
  KEY `idx_granted_at` (`granted_at`),
  KEY `fk_teacher_privileges_granted_by` (`granted_by`),
  KEY `fk_teacher_privileges_revoked_by` (`revoked_by`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores teacher privileges for various system features';

-- --------------------------------------------------------

--
-- Table structure for table `teaching_resources_master`
--

DROP TABLE IF EXISTS `teaching_resources_master`;
CREATE TABLE IF NOT EXISTS `teaching_resources_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'visual, audio, manipulative, digital, etc.',
  `display_order` int DEFAULT '0',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terminal_reports`
--

DROP TABLE IF EXISTS `terminal_reports`;
CREATE TABLE IF NOT EXISTS `terminal_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `year` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `term` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `attendance_present` int DEFAULT '0',
  `attendance_total` int DEFAULT '0',
  `conduct` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attitude` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interest` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `teacher_remark` text COLLATE utf8mb4_general_ci,
  `headmaster_remark` text COLLATE utf8mb4_general_ci,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `unique_student_year_term` (`student_id`,`year`,`term`),
  KEY `idx_student` (`student_id`),
  KEY `idx_year_term` (`year`,`term`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
CREATE TABLE IF NOT EXISTS `terms` (
  `term_id` int NOT NULL AUTO_INCREMENT,
  `term_ending` longtext COLLATE utf8mb4_unicode_520_ci,
  `next_term_begins` longtext COLLATE utf8mb4_unicode_520_ci,
  `full_payment_date` longtext COLLATE utf8mb4_unicode_520_ci,
  `year` longtext COLLATE utf8mb4_unicode_520_ci,
  `term` longtext COLLATE utf8mb4_unicode_520_ci,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`term_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tier2_providers`
--

DROP TABLE IF EXISTS `tier2_providers`;
CREATE TABLE IF NOT EXISTS `tier2_providers` (
  `provider_id` int NOT NULL AUTO_INCREMENT,
  `provider_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`provider_id`),
  UNIQUE KEY `provider_code` (`provider_code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_provider_code` (`provider_code`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSNIT Tier 2 pension providers for employee contribution tracking';

-- --------------------------------------------------------

--
-- Table structure for table `to_do_list`
--

DROP TABLE IF EXISTS `to_do_list`;
CREATE TABLE IF NOT EXISTS `to_do_list` (
  `id` int NOT NULL,
  `user_id` varchar(64) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `to_dodata` varchar(256) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `date` varchar(128) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `value` varchar(14) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport`
--

DROP TABLE IF EXISTS `transport`;
CREATE TABLE IF NOT EXISTS `transport` (
  `transport_id` int NOT NULL AUTO_INCREMENT,
  `route_name` longtext COLLATE utf8mb4_unicode_520_ci,
  `number_of_vehicle` longtext COLLATE utf8mb4_unicode_520_ci,
  `description` longtext COLLATE utf8mb4_unicode_520_ci,
  `route_fare` longtext COLLATE utf8mb4_unicode_520_ci,
  `driver_id` int DEFAULT NULL COMMENT 'Foreign key to non_teaching_staff.staff_id for driver assignment',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`transport_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`),
  KEY `idx_driver_id` (`driver_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_auto_billing`
--

DROP TABLE IF EXISTS `transport_auto_billing`;
CREATE TABLE IF NOT EXISTS `transport_auto_billing` (
  `billing_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `transport_id` int NOT NULL,
  `billing_date` date NOT NULL,
  `fare_amount` decimal(10,2) NOT NULL,
  `was_present` enum('yes','no') COLLATE utf8mb4_unicode_520_ci DEFAULT 'no',
  `boarded_bus` enum('yes','no') COLLATE utf8mb4_unicode_520_ci DEFAULT 'no',
  `billing_status` enum('pending','confirmed','reversed') COLLATE utf8mb4_unicode_520_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`billing_id`),
  KEY `student_id` (`student_id`),
  KEY `billing_date` (`billing_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_daily_log`
--

DROP TABLE IF EXISTS `transport_daily_log`;
CREATE TABLE IF NOT EXISTS `transport_daily_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `transport_id` int NOT NULL,
  `log_date` date NOT NULL,
  `boarded_bus` enum('yes','no') COLLATE utf8mb4_unicode_520_ci DEFAULT 'no',
  `paid_fare` enum('yes','no') COLLATE utf8mb4_unicode_520_ci DEFAULT 'no',
  `conductor_id` int DEFAULT NULL,
  `remarks` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `logged_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `student_date` (`student_id`,`log_date`),
  KEY `log_date` (`log_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notification_preferences`
--

DROP TABLE IF EXISTS `user_notification_preferences`;
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `preference_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'User ID (references admin table)',
  `sms_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = SMS disabled (opt-out), 1 = SMS enabled (opt-in)',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last preference update timestamp',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User SMS notification preferences (default: opt-out)';

-- --------------------------------------------------------

--
-- Table structure for table `user_permission`
--

DROP TABLE IF EXISTS `user_permission`;
CREATE TABLE IF NOT EXISTS `user_permission` (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_title` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_type` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `user_level` int DEFAULT NULL,
  `permission_status` enum('0','1') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '0',
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`permission_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_tracker`
--

DROP TABLE IF EXISTS `visitor_tracker`;
CREATE TABLE IF NOT EXISTS `visitor_tracker` (
  `id` int NOT NULL,
  `ip` varchar(15) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `page_view` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `date` int NOT NULL,
  `sync` enum('yes','no') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'no',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `v_discount_audit_summary`
--

DROP TABLE IF EXISTS `v_discount_audit_summary`;
CREATE TABLE IF NOT EXISTS `v_discount_audit_summary` (
  `audit_id` int DEFAULT NULL,
  `entity_type` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `entity_name` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `changed_by_name` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `formatted_date` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb3_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb3_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb3_unicode_ci,
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_expense_summary_by_category`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_expense_summary_by_category`;
CREATE TABLE IF NOT EXISTS `v_expense_summary_by_category` (
`category_name` varchar(100)
,`expense_count` bigint
,`pending_amount` decimal(32,2)
,`approved_amount` decimal(32,2)
,`rejected_amount` decimal(32,2)
,`total_amount` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `v_invoice_discounts_detailed`
--

DROP TABLE IF EXISTS `v_invoice_discounts_detailed`;
CREATE TABLE IF NOT EXISTS `v_invoice_discounts_detailed` (
  `discount_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `student_name` longtext COLLATE utf8mb3_unicode_ci,
  `student_code` longtext COLLATE utf8mb3_unicode_ci,
  `invoice_code` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discount_type_id` int DEFAULT NULL,
  `discount_type_name` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discount_type_icon` varchar(10) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `category_id` tinyint DEFAULT NULL,
  `category_code` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `category_name` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discount_method` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `reason` mediumtext COLLATE utf8mb3_unicode_ci,
  `status` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applied_by` int DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb3_unicode_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb3_unicode_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb3_unicode_ci,
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `waec_grading_scale`
--

DROP TABLE IF EXISTS `waec_grading_scale`;
CREATE TABLE IF NOT EXISTS `waec_grading_scale` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grade` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `min_score` int NOT NULL,
  `max_score` int NOT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL,
  `remark` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_pass` tinyint(1) DEFAULT '1',
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_general_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_general_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `water_charge_log`
--

DROP TABLE IF EXISTS `water_charge_log`;
CREATE TABLE IF NOT EXISTS `water_charge_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `week_start_date` int NOT NULL,
  `week_end_date` int NOT NULL,
  `amount_charged` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','unpaid') COLLATE utf8mb4_unicode_520_ci DEFAULT 'unpaid',
  `year` int NOT NULL,
  `term` int DEFAULT NULL,
  `sem` int DEFAULT NULL,
  `charged_at` int NOT NULL,
  `sync_status` enum('PENDING','SYNCED','FAILED','MANUAL_REVIEW') COLLATE utf8mb4_unicode_520_ci DEFAULT 'PENDING',
  `sync_operation_type` enum('insert','update','both') COLLATE utf8mb4_unicode_520_ci DEFAULT 'insert',
  `last_modified_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_modified_by` int DEFAULT NULL,
  `device_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'local-server-001',
  `version` int DEFAULT '1',
  `retry_count` int DEFAULT '0',
  `sync_error` text COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`,`week_start_date`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_last_modified` (`last_modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Structure for view `invoice_summary`
--
DROP TABLE IF EXISTS `invoice_summary`;

DROP VIEW IF EXISTS `invoice_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `invoice_summary`  AS SELECT `i`.`student_id` AS `student_id`, `i`.`invoice_code` AS `invoice_code`, `i`.`year` AS `year`, `i`.`term` AS `term`, `s`.`name` AS `student_name`, `s`.`student_code` AS `student_code`, `c`.`name` AS `class_name`, sum(`i`.`amount`) AS `total_amount`, sum(`i`.`amount_paid`) AS `total_paid`, sum(`i`.`due`) AS `total_due`, coalesce((select `d`.`discount_amount` from `invoice_discounts` `d` where ((`d`.`student_id` = `i`.`student_id`) and (`d`.`invoice_code` = `i`.`invoice_code`) and (`d`.`status` = 'approved')) limit 1),0) AS `total_discount`, count(distinct `i`.`invoice_id`) AS `item_count`, min(`i`.`creation_timestamp`) AS `invoice_date`, max(`i`.`payment_timestamp`) AS `last_payment_date`, (case when (sum(`i`.`due`) = 0) then 'paid' when sum(`i`.`amount_paid`) then 'partial' else 'unpaid' end) AS `payment_status` FROM (((`invoice` `i` left join `student` `s` on((`i`.`student_id` = `s`.`student_id`))) left join `enroll` `e` on(((`s`.`student_id` = `e`.`student_id`) and (`i`.`year` = `e`.`year`) and (`i`.`term` = `e`.`term`)))) left join `class` `c` on((`e`.`class_id` = `c`.`class_id`))) GROUP BY `i`.`student_id`, `i`.`invoice_code`, `i`.`year`, `i`.`term`, `s`.`name`, `s`.`student_code`, `c`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `pending_approvals_view`
--
DROP TABLE IF EXISTS `pending_approvals_view`;

DROP VIEW IF EXISTS `pending_approvals_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_approvals_view`  AS SELECT `ar`.`id` AS `id`, `ar`.`request_type` AS `request_type`, `ar`.`record_type` AS `record_type`, `ar`.`record_id` AS `record_id`, `ar`.`requested_at` AS `requested_at`, `ar`.`reason` AS `reason`, `ar`.`status` AS `status`, (case when `ar`.`requested_by` in (select `admin`.`admin_id` from `admin`) then (select `admin`.`name` from `admin` where (`admin`.`admin_id` = `ar`.`requested_by`)) when `ar`.`requested_by` in (select `teacher`.`teacher_id` from `teacher`) then (select `teacher`.`name` from `teacher` where (`teacher`.`teacher_id` = `ar`.`requested_by`)) else 'Unknown' end) AS `requested_by_name`, (case when (`ar`.`record_type` = 'invoice') then (select `invoice`.`invoice_code` from `invoice` where (`invoice`.`invoice_id` = `ar`.`record_id`)) when (`ar`.`record_type` = 'payment') then (select `payment`.`receipt_code` from `payment` where (`payment`.`payment_id` = `ar`.`record_id`)) end) AS `record_code`, (case when (`ar`.`record_type` = 'invoice') then (select `s`.`name` from (`invoice` `i` join `student` `s` on((`i`.`student_id` = `s`.`student_id`))) where (`i`.`invoice_id` = `ar`.`record_id`)) when (`ar`.`record_type` = 'payment') then (select `s`.`name` from (`payment` `p` join `student` `s` on((`p`.`student_id` = `s`.`student_id`))) where (`p`.`payment_id` = `ar`.`record_id`)) end) AS `student_name` FROM `approval_requests` AS `ar` WHERE (`ar`.`status` = 'pending') ORDER BY `ar`.`requested_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `portfolio_headers_with_curriculum`
--
DROP TABLE IF EXISTS `portfolio_headers_with_curriculum`;

DROP VIEW IF EXISTS `portfolio_headers_with_curriculum`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `portfolio_headers_with_curriculum`  AS SELECT `ph`.`header_id` AS `header_id`, `ph`.`class_id` AS `class_id`, `ph`.`subject_id` AS `subject_id`, `ph`.`teacher_id` AS `teacher_id`, `ph`.`year` AS `year`, `ph`.`term` AS `term`, `ph`.`semester` AS `semester`, `ph`.`week_number` AS `week_number`, `ph`.`strand_topic` AS `strand_topic`, `ph`.`strand_id` AS `strand_id`, `ph`.`sub_strand_id` AS `sub_strand_id`, `ph`.`indicator_id` AS `indicator_id`, `ph`.`assessment_date` AS `assessment_date`, `ph`.`max_score` AS `max_score`, `ph`.`status` AS `status`, `ph`.`created_by` AS `created_by`, `ph`.`created_at` AS `created_at`, `ph`.`updated_at` AS `updated_at`, `ph`.`deleted_at` AS `deleted_at`, `cs`.`name` AS `strand_name`, `css`.`name` AS `sub_strand_name`, `ci`.`indicator_text` AS `indicator_text`, coalesce(concat(`cs`.`name`,' - ',`css`.`name`),`ph`.`strand_topic`) AS `full_curriculum_path` FROM (((`portfolio_headers` `ph` left join `curriculum_strands` `cs` on((`cs`.`strand_id` = `ph`.`strand_id`))) left join `curriculum_sub_strands` `css` on((`css`.`sub_strand_id` = `ph`.`sub_strand_id`))) left join `curriculum_indicators` `ci` on((`ci`.`indicator_id` = `ph`.`indicator_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `student_account_summary`
--
DROP TABLE IF EXISTS `student_account_summary`;

DROP VIEW IF EXISTS `student_account_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_account_summary`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`name` AS `student_name`, `s`.`parent_id` AS `parent_id`, `c`.`name` AS `class_name`, coalesce(sum(`i`.`amount`),0) AS `total_billed`, coalesce(sum(`i`.`amount_paid`),0) AS `total_paid`, coalesce(sum(`i`.`due`),0) AS `total_outstanding`, coalesce(sum((case when (`i`.`status` = 'paid') then `i`.`amount` else 0 end)),0) AS `fully_paid_amount`, coalesce(sum((case when (`i`.`status` = 'due') then `i`.`amount` else 0 end)),0) AS `unpaid_amount`, count(distinct `i`.`invoice_id`) AS `total_invoices`, `i`.`year` AS `year`, `i`.`term` AS `term` FROM (((`student` `s` left join `enroll` `e` on((`s`.`student_id` = `e`.`student_id`))) left join `class` `c` on((`e`.`class_id` = `c`.`class_id`))) left join `invoice` `i` on((`s`.`student_id` = `i`.`student_id`))) GROUP BY `s`.`student_id`, `i`.`year`, `i`.`term` ;

-- --------------------------------------------------------

--
-- Structure for view `student_credit_summary`
--
DROP TABLE IF EXISTS `student_credit_summary`;

DROP VIEW IF EXISTS `student_credit_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_credit_summary`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`name` AS `student_name`, coalesce(sum(`sc`.`remaining_amount`),0) AS `total_available_credit`, coalesce(sum(`sc`.`credit_amount`),0) AS `total_credits_earned`, coalesce(sum(`sc`.`applied_amount`),0) AS `total_credits_used`, count(`sc`.`credit_id`) AS `total_credit_records`, max(`sc`.`created_at`) AS `last_credit_date` FROM (`student` `s` left join `student_credits` `sc` on(((`s`.`student_id` = `sc`.`student_id`) and (`sc`.`status` = 'active')))) GROUP BY `s`.`student_id`, `s`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `v_expense_summary_by_category`
--
DROP TABLE IF EXISTS `v_expense_summary_by_category`;

DROP VIEW IF EXISTS `v_expense_summary_by_category`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expense_summary_by_category`  AS SELECT `ec`.`name` AS `category_name`, count(`e`.`id`) AS `expense_count`, sum((case when (`e`.`status` = 'pending') then `e`.`amount` else 0 end)) AS `pending_amount`, sum((case when (`e`.`status` = 'approved') then `e`.`amount` else 0 end)) AS `approved_amount`, sum((case when (`e`.`status` = 'rejected') then `e`.`amount` else 0 end)) AS `rejected_amount`, sum(`e`.`amount`) AS `total_amount` FROM (`expense_categories_enhanced` `ec` left join `expenses_enhanced` `e` on((`ec`.`id` = `e`.`category_id`))) GROUP BY `ec`.`id`, `ec`.`name` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_tier2_provider_fk` FOREIGN KEY (`tier2_provider_id`) REFERENCES `pension_tier2_providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `fk_bank_accounts_chart` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_of_accounts` (`account_id`);

--
-- Constraints for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD CONSTRAINT `fk_reconciliation_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`bank_account_id`) ON DELETE CASCADE;

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `fk_bank_trans_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`bank_account_id`),
  ADD CONSTRAINT `fk_bank_trans_journal` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`entry_id`);

--
-- Constraints for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD CONSTRAINT `fk_budget_line_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_lines_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`account_id`),
  ADD CONSTRAINT `fk_budget_lines_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_utilization_log`
--
ALTER TABLE `budget_utilization_log`
  ADD CONSTRAINT `fk_budget_util_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`budget_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_util_line` FOREIGN KEY (`budget_line_id`) REFERENCES `budget_lines` (`budget_line_id`) ON DELETE CASCADE;

--
-- Constraints for table `credit_applications`
--
ALTER TABLE `credit_applications`
  ADD CONSTRAINT `credit_applications_ibfk_1` FOREIGN KEY (`credit_id`) REFERENCES `student_credits` (`credit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_applications_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`invoice_id`) ON DELETE CASCADE;

--
-- Constraints for table `credit_system_logs`
--
ALTER TABLE `credit_system_logs`
  ADD CONSTRAINT `credit_system_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_content_standards`
--
ALTER TABLE `curriculum_content_standards`
  ADD CONSTRAINT `fk_ccs_sub_strand` FOREIGN KEY (`sub_strand_id`) REFERENCES `curriculum_sub_strands` (`sub_strand_id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_indicators`
--
ALTER TABLE `curriculum_indicators`
  ADD CONSTRAINT `curriculum_indicators_ibfk_1` FOREIGN KEY (`sub_strand_id`) REFERENCES `curriculum_sub_strands` (`sub_strand_id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_learning_indicators`
--
ALTER TABLE `curriculum_learning_indicators`
  ADD CONSTRAINT `fk_cli_content_standard` FOREIGN KEY (`content_standard_id`) REFERENCES `curriculum_content_standards` (`content_standard_id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_strands`
--
ALTER TABLE `curriculum_strands`
  ADD CONSTRAINT `fk_cs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_sub_strands`
--
ALTER TABLE `curriculum_sub_strands`
  ADD CONSTRAINT `fk_css_strand` FOREIGN KEY (`strand_id`) REFERENCES `curriculum_strands` (`strand_id`) ON DELETE CASCADE;

--
-- Constraints for table `discount_applications`
--
ALTER TABLE `discount_applications`
  ADD CONSTRAINT `fk_discount_app_profile` FOREIGN KEY (`profile_id`) REFERENCES `discount_profiles` (`profile_id`),
  ADD CONSTRAINT `fk_discount_app_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `enterprise_exam_subjects`
--
ALTER TABLE `enterprise_exam_subjects`
  ADD CONSTRAINT `enterprise_exam_subjects_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `enterprise_exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `enterprise_student_marks`
--
ALTER TABLE `enterprise_student_marks`
  ADD CONSTRAINT `enterprise_student_marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `enterprise_exams` (`exam_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_marks`
--
ALTER TABLE `exam_marks`
  ADD CONSTRAINT `exam_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `fiscal_periods`
--
ALTER TABLE `fiscal_periods`
  ADD CONSTRAINT `fk_periods_year` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`fiscal_year_id`) ON DELETE CASCADE;

--
-- Constraints for table `hod_subjects`
--
ALTER TABLE `hod_subjects`
  ADD CONSTRAINT `fk_hs_admin` FOREIGN KEY (`assigned_by`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hs_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_products`
--
ALTER TABLE `inventory_products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_purchases`
--
ALTER TABLE `inventory_purchases`
  ADD CONSTRAINT `fk_purchase_creator` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_receiver` FOREIGN KEY (`received_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `inventory_suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_purchase_items`
--
ALTER TABLE `inventory_purchase_items`
  ADD CONSTRAINT `fk_purchase_item_product` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_item_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `inventory_purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_purchase_payments`
--
ALTER TABLE `inventory_purchase_payments`
  ADD CONSTRAINT `fk_purchase_payment_admin` FOREIGN KEY (`recorded_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_payment_expenditure` FOREIGN KEY (`expenditure_payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_purchase_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_purchase_payment_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `inventory_purchases` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `inventory_returns`
--
ALTER TABLE `inventory_returns`
  ADD CONSTRAINT `fk_return_admin` FOREIGN KEY (`processed_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_return_sale` FOREIGN KEY (`original_sale_id`) REFERENCES `inventory_sales` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `inventory_return_items`
--
ALTER TABLE `inventory_return_items`
  ADD CONSTRAINT `fk_return_item_product` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_return_item_return` FOREIGN KEY (`return_id`) REFERENCES `inventory_returns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_return_item_sale_item` FOREIGN KEY (`sale_item_id`) REFERENCES `inventory_sale_items` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `inventory_sale_items`
--
ALTER TABLE `inventory_sale_items`
  ADD CONSTRAINT `fk_sale_item_product` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`),
  ADD CONSTRAINT `fk_sale_item_sale` FOREIGN KEY (`sale_id`) REFERENCES `inventory_sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_stock_movements`
--
ALTER TABLE `inventory_stock_movements`
  ADD CONSTRAINT `inventory_stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `fk_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`account_id`),
  ADD CONSTRAINT `fk_journal_lines_entry` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`entry_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_notes`
--
ALTER TABLE `lesson_notes`
  ADD CONSTRAINT `fk_ln_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ln_content_standard` FOREIGN KEY (`content_standard_id`) REFERENCES `curriculum_content_standards` (`content_standard_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_hod` FOREIGN KEY (`hod_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_source` FOREIGN KEY (`source_lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_strand` FOREIGN KEY (`strand_id`) REFERENCES `curriculum_strands` (`strand_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_sub_strand` FOREIGN KEY (`sub_strand_id`) REFERENCES `curriculum_sub_strands` (`sub_strand_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ln_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ln_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_assessments`
--
ALTER TABLE `lesson_note_assessments`
  ADD CONSTRAINT `fk_lna_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_competencies`
--
ALTER TABLE `lesson_note_competencies`
  ADD CONSTRAINT `fk_lnc_competency` FOREIGN KEY (`competency_id`) REFERENCES `core_competencies` (`competency_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lnc_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_indicators`
--
ALTER TABLE `lesson_note_indicators`
  ADD CONSTRAINT `fk_lni_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `curriculum_learning_indicators` (`indicator_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lni_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_references`
--
ALTER TABLE `lesson_note_references`
  ADD CONSTRAINT `fk_lnref_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_resources`
--
ALTER TABLE `lesson_note_resources`
  ADD CONSTRAINT `fk_lnr_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `lesson_note_revisions`
--
ALTER TABLE `lesson_note_revisions`
  ADD CONSTRAINT `fk_lnrrev_lesson_note` FOREIGN KEY (`lesson_note_id`) REFERENCES `lesson_notes` (`lesson_note_id`) ON DELETE CASCADE;

--
-- Constraints for table `non_teaching_staff`
--
ALTER TABLE `non_teaching_staff`
  ADD CONSTRAINT `non_teaching_staff_tier2_provider_fk` FOREIGN KEY (`tier2_provider_id`) REFERENCES `pension_tier2_providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notification_delivery_log`
--
ALTER TABLE `notification_delivery_log`
  ADD CONSTRAINT `fk_notification_delivery_log_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payroll_approvals`
--
ALTER TABLE `payroll_approvals`
  ADD CONSTRAINT `fk_payroll_approvals_pay_id` FOREIGN KEY (`pay_id`) REFERENCES `pay_salary` (`pay_id`) ON DELETE CASCADE;

--
-- Constraints for table `pay_salary`
--
ALTER TABLE `pay_salary`
  ADD CONSTRAINT `pay_salary_tier2_provider_fk` FOREIGN KEY (`tier2_provider_id`) REFERENCES `pension_tier2_providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `portfolio_aggregates`
--
ALTER TABLE `portfolio_aggregates`
  ADD CONSTRAINT `portfolio_aggregates_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portfolio_aggregates_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolio_headers`
--
ALTER TABLE `portfolio_headers`
  ADD CONSTRAINT `portfolio_headers_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`class_id`),
  ADD CONSTRAINT `portfolio_headers_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`);

--
-- Constraints for table `portfolio_scores`
--
ALTER TABLE `portfolio_scores`
  ADD CONSTRAINT `portfolio_scores_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `portfolio_headers` (`header_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portfolio_scores_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `reconciliation_items`
--
ALTER TABLE `reconciliation_items`
  ADD CONSTRAINT `fk_recon_item_reconciliation` FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `result_approval_audit`
--
ALTER TABLE `result_approval_audit`
  ADD CONSTRAINT `result_approval_audit_ibfk_1` FOREIGN KEY (`approval_status_id`) REFERENCES `result_approval_status` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sba_components`
--
ALTER TABLE `sba_components`
  ADD CONSTRAINT `sba_components_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sba_components_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `sba_score_sources`
--
ALTER TABLE `sba_score_sources`
  ADD CONSTRAINT `sba_score_sources_ibfk_1` FOREIGN KEY (`sba_component_id`) REFERENCES `sba_components` (`component_id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_automation_logs`
--
ALTER TABLE `sms_automation_logs`
  ADD CONSTRAINT `fk_sms_log_automation` FOREIGN KEY (`automation_id`) REFERENCES `sms_automations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_credits`
--
ALTER TABLE `student_credits`
  ADD CONSTRAINT `student_credits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_discount_assignments`
--
ALTER TABLE `student_discount_assignments`
  ADD CONSTRAINT `fk_student_discount_profile` FOREIGN KEY (`profile_id`) REFERENCES `discount_profiles` (`profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_discount_assignments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_ledger`
--
ALTER TABLE `student_ledger`
  ADD CONSTRAINT `student_ledger_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_tier2_provider_fk` FOREIGN KEY (`tier2_provider_id`) REFERENCES `pension_tier2_providers` (`provider_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teacher_privileges`
--
ALTER TABLE `teacher_privileges`
  ADD CONSTRAINT `fk_teacher_privileges_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teacher_privileges_revoked_by` FOREIGN KEY (`revoked_by`) REFERENCES `admin` (`admin_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teacher_privileges_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`teacher_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `terminal_reports`
--
ALTER TABLE `terminal_reports`
  ADD CONSTRAINT `terminal_reports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notification_preferences`
--
ALTER TABLE `user_notification_preferences`
  ADD CONSTRAINT `fk_user_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
