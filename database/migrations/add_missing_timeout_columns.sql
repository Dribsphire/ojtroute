-- =====================================================
-- Migration: Add Missing Timeout Tracking Columns
-- Purpose: Track students who forget to time out
-- =====================================================

USE `ojt_db`;

-- Add columns to attendance_records table for missing timeout tracking
ALTER TABLE `attendance_records`
ADD COLUMN IF NOT EXISTS `forgot_timeout_reason` TEXT NULL COMMENT 'Student explanation for missing timeout',
ADD COLUMN IF NOT EXISTS `forgot_timeout_file` VARCHAR(500) NULL COMMENT 'Path to supporting document',
ADD COLUMN IF NOT EXISTS `request_status` ENUM('pending', 'approved', 'rejected') NULL COMMENT 'Status of missing timeout request',
ADD COLUMN IF NOT EXISTS `instructor_response` TEXT NULL COMMENT 'Instructor feedback on the request',
ADD COLUMN IF NOT EXISTS `missing_timeout_flagged_at` DATETIME NULL COMMENT 'When the system flagged this as missing timeout';

-- Add index for request_status for faster queries
CREATE INDEX IF NOT EXISTS `idx_request_status` ON `attendance_records` (`request_status`);

-- =====================================================
-- END OF MIGRATION
-- =====================================================
