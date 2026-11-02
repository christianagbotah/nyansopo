-- Create beneficiary_list table for termly beneficiary tracking
CREATE TABLE IF NOT EXISTS `beneficiary_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `year` varchar(10) NOT NULL,
  `term` varchar(5) NOT NULL,
  `categories` text NOT NULL COMMENT 'JSON format: [{"category_id":1,"amount":100},{"category_id":2,"amount":50}]',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `year_term` (`year`,`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
