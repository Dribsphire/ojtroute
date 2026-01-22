-- Migration to add position_title and supervisor_name columns to workplace_change_requests table
-- Run this if the columns don't exist

-- Check if table exists, if not create it
CREATE TABLE IF NOT EXISTS `workplace_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `workplace_name` varchar(255) NOT NULL,
  `workplace_address` text,
  `position_title` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
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

-- Add columns if they don't exist (for existing tables)
-- These will fail silently if columns already exist
ALTER TABLE `workplace_change_requests` 
ADD COLUMN `position_title` varchar(255) DEFAULT NULL AFTER `workplace_address`;

ALTER TABLE `workplace_change_requests` 
ADD COLUMN `supervisor_name` varchar(255) DEFAULT NULL AFTER `position_title`;
