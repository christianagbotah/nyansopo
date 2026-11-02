CREATE TABLE IF NOT EXISTS discount_audit_trail (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    discount_type_id INT NOT NULL,
    action ENUM('created', 'updated', 'deleted', 'activated', 'deactivated') NOT NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    FOREIGN KEY (discount_type_id) REFERENCES discount_types(discount_type_id),
    INDEX idx_discount_type (discount_type_id),
    INDEX idx_action (action),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
