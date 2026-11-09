-- Invoice Modification Requests Table
CREATE TABLE IF NOT EXISTS `invoice_modification_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_code` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('edit','delete') NOT NULL,
  `requested_by` int(11) NOT NULL,
  `request_reason` text NOT NULL,
  `old_data` text NOT NULL COMMENT 'JSON of original invoice items',
  `new_data` text DEFAULT NULL COMMENT 'JSON of new invoice items for edit',
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_comment` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `invoice_code` (`invoice_code`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `requested_by` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
