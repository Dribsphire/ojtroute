-- Migration: Create excused_dates table
-- Purpose: Track dates when students are excused by instructors with hours credited
-- Created: 2026-01-05

CREATE TABLE IF NOT EXISTS `excused_dates` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK to students table',
    `excused_date` DATE NOT NULL COMMENT 'The date marked as excused',
    `instructor_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK to instructors table',
    `hours_added` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Hours credited for this excused date',
    `reason` TEXT NOT NULL COMMENT 'Reason for the excused absence',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this record was created',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_student_date` (`student_id`, `excused_date`),
    KEY `idx_student_id` (`student_id`),
    KEY `idx_excused_date` (`excused_date`),
    KEY `idx_instructor_id` (`instructor_id`),
    CONSTRAINT `fk_excused_dates_student` 
        FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_excused_dates_instructor` 
        FOREIGN KEY (`instructor_id`) 
        REFERENCES `instructors` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracks excused dates for students with hours credited by instructors';

-- Add index for faster date range queries
CREATE INDEX `idx_date_student` ON `excused_dates` (`excused_date`, `student_id`);
