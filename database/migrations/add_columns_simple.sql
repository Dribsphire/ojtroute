-- Simple migration to add missing columns
-- Copy and paste this into phpMyAdmin SQL tab

-- Add position_title column
ALTER TABLE `workplace_change_requests` 
ADD COLUMN `position_title` VARCHAR(255) DEFAULT NULL AFTER `workplace_address`;

-- Add supervisor_name column  
ALTER TABLE `workplace_change_requests` 
ADD COLUMN `supervisor_name` VARCHAR(255) DEFAULT NULL AFTER `position_title`;
