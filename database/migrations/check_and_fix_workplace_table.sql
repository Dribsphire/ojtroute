-- Check current structure and fix workplace_change_requests table
-- Run this in phpMyAdmin or MySQL command line

-- First, let's see if the table exists
SHOW TABLES LIKE 'workplace_change_requests';

-- If table exists, show its structure
DESCRIBE workplace_change_requests;

-- Now let's fix it by adding missing columns if they don't exist
-- MySQL will ignore if columns already exist (with warnings, but won't fail)

-- Create table if it doesn't exist
CREATE TABLE IF NOT EXISTS `workplace_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `workplace_name` varchar(255) NOT NULL,
  `workplace_address` text,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `change_reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add position_title column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'workplace_change_requests';
SET @columnname = 'position_title';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' varchar(255) DEFAULT NULL AFTER workplace_address')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add supervisor_name column if it doesn't exist
SET @columnname = 'supervisor_name';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' varchar(255) DEFAULT NULL AFTER position_title')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verify the changes
DESCRIBE workplace_change_requests;
