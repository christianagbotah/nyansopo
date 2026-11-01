CREATE TABLE IF NOT EXISTS sms_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    message TEXT NOT NULL,
    variables TEXT COMMENT 'JSON array of available variables',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default templates
INSERT INTO sms_templates (name, code, message, variables) VALUES
('Payment Reminder', 'payment_reminder', 'Dear {parent_name}, {student_name} has an outstanding balance of GHS {amount} for {invoice_code}. Please pay to avoid penalties. - {school_name}', '["parent_name","student_name","amount","invoice_code","school_name"]'),
('Payment Received', 'payment_received', 'Dear {parent_name}, we have received GHS {amount} payment for {student_name}. Thank you! - {school_name}', '["parent_name","student_name","amount","school_name"]'),
('Overdue Notice', 'overdue_notice', 'URGENT: {student_name} has an overdue balance of GHS {amount} for {days} days. Please settle immediately. - {school_name}', '["student_name","amount","days","school_name"]');

CREATE TABLE IF NOT EXISTS sms_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    template_code VARCHAR(50) NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'once') NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_day INT COMMENT 'Day of week (1-7) or day of month (1-31)',
    target_criteria TEXT COMMENT 'JSON criteria for selecting recipients',
    is_active TINYINT(1) DEFAULT 1,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_next_run (next_run),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
