-- Portfolio Assessment Settings Toggle
-- Add setting to enable/disable portfolio assessment module

INSERT INTO `settings` (`type`, `description`) 
VALUES ('portfolio_assessment_enabled', '0')
ON DUPLICATE KEY UPDATE `type` = `type`;

-- Note: The following tables already exist in the schema:
-- - portfolio_assessment (stores assessment records)
-- - portfolio_audit_trail (tracks changes)
