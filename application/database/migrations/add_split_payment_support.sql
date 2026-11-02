-- Split Payment Implementation
-- Created: 2026-09-28
-- Purpose: Support multiple payment methods in a single transaction

-- Create payment splits table
CREATE TABLE IF NOT EXISTS `daily_fee_payment_splits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL COMMENT 'FK to daily_fee_transactions.id',
  `payment_method_id` int(11) NOT NULL COMMENT 'FK to payment_methods.id',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount paid via this method',
  `reference_number` varchar(100) DEFAULT NULL COMMENT 'Transaction ref (check no, momo ref, etc)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_payment_method_id` (`payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores split payment details for multi-method transactions';

-- Add foreign key constraints (if tables exist)
-- Note: Run these separately if you get errors about existing constraints
-- ALTER TABLE `daily_fee_payment_splits`
--   ADD CONSTRAINT `fk_split_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `daily_fee_transactions` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `fk_split_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);
