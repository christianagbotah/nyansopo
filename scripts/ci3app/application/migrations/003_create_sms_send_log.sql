-- =====================================================================
-- SMS send log: audit trail for manually-triggered SMS campaigns
-- (e.g., receivables debtor reminders from the financial reports page)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `sms_send_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL COMMENT 'Message template that was sent',
  `recipient_count` int NOT NULL DEFAULT 0 COMMENT 'Number of personalized messages submitted',
  `sent_by` int DEFAULT NULL COMMENT 'admin_id of the sender',
  `send_context` varchar(50) DEFAULT NULL COMMENT 'e.g. receivables_reminder, manual_bulk',
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `provider_response` text DEFAULT NULL COMMENT 'Raw gateway response for debugging',
  `created_at` int NOT NULL COMMENT 'Unix timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_context_created` (`send_context`, `created_at`),
  KEY `idx_sent_by` (`sent_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
