-- =====================================================
-- Track Actual Discount Amounts Applied
-- =====================================================

CREATE TABLE IF NOT EXISTS `discount_applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `discount_category` enum('invoice','daily_fees') NOT NULL,
  `reference_type` varchar(50) NOT NULL COMMENT 'invoice, daily_fee_transaction',
  `reference_id` varchar(100) NOT NULL COMMENT 'invoice_code or transaction_id',
  `original_amount` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `final_amount` decimal(10,2) NOT NULL,
  `year` int(4) NOT NULL,
  `term` int(1) DEFAULT NULL,
  `applied_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `applied_by` int(11) NOT NULL,
  PRIMARY KEY (`application_id`),
  KEY `student_id` (`student_id`),
  KEY `profile_id` (`profile_id`),
  KEY `discount_category` (`discount_category`),
  KEY `year_term` (`year`,`term`),
  KEY `applied_at` (`applied_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for reporting
CREATE INDEX idx_category_year_term ON discount_applications(discount_category, year, term);
CREATE INDEX idx_reference ON discount_applications(reference_type, reference_id);
