-- Comprehensive Audit Trail System
CREATE TABLE IF NOT EXISTS audit_trail (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    record_type ENUM('invoice', 'payment', 'journal', 'budget', 'expense', 'account', 'discount', 'other') NOT NULL,
    record_id INT NOT NULL,
    action ENUM('create', 'update', 'delete', 'lock', 'unlock', 'post', 'void', 'approve', 'reject') NOT NULL,
    old_values TEXT NULL COMMENT 'JSON encoded old values',
    new_values TEXT NULL COMMENT 'JSON encoded new values',
    performed_by INT NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    notes TEXT NULL,
    INDEX idx_record_type (record_type),
    INDEX idx_record_id (record_id),
    INDEX idx_action (action),
    INDEX idx_performed_by (performed_by),
    INDEX idx_performed_at (performed_at),
    INDEX idx_composite (record_type, record_id, performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
