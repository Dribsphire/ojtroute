-- Migration to update document_types and generic table population
-- Add template_path column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'document_types';
SET @columnname = 'template_path';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(255) NULL COMMENT "Path to template file" AFTER description')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ensure categories are correct (we might need to alter enum if strictly enforcing, but current schema has 'other' which is fine)
-- We will just insert the default types. valid categories: 'pre_required', 'weekly', 'monthly', 'excuse', 'other'

-- Insert default pre-required documents
INSERT INTO `document_types` (`name`, `code`, `category`, `is_pre_required`, `is_required`, `is_active`) VALUES
('Application Letter', 'REQ_APP_LETTER', 'pre_required', 1, 1, 1),
('Resume', 'REQ_RESUME', 'pre_required', 1, 1, 1),
('Endorsement Letter', 'REQ_ENDORSEMENT', 'pre_required', 1, 1, 1),
('Certificate of Attendance', 'REQ_CERT_ATTENDANCE', 'pre_required', 1, 1, 1),
('Medical Certification', 'REQ_MEDICAL', 'pre_required', 1, 1, 1),
('Parent Consent', 'REQ_PARENT_CONSENT', 'pre_required', 1, 1, 1),
('Pledge of Good Conduct', 'REQ_PLEDGE', 'pre_required', 1, 1, 1),
('Misdemeanor Penalty', 'REQ_MISDEMEANOR', 'pre_required', 1, 1, 1)
ON DUPLICATE KEY UPDATE 
    category = 'pre_required',
    is_pre_required = 1,
    is_required = 1;
