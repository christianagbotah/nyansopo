-- Notifications table for system-wide notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Admin ID who will receive the notification',
  `type` varchar(50) NOT NULL COMMENT 'Type: admission, payment, etc',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` text COMMENT 'JSON data with additional details',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admission logs table for detailed admission tracking
CREATE TABLE IF NOT EXISTS `admission_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `admitted_by` int(11) NOT NULL COMMENT 'Admin ID who admitted the student',
  `class_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `residence_type` varchar(20) NOT NULL,
  `bill_items` text NOT NULL COMMENT 'JSON array of bill items with amounts',
  `total_bill_amount` decimal(10,2) NOT NULL,
  `admission_date` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  KEY `admitted_by` (`admitted_by`),
  KEY `admission_date` (`admission_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
