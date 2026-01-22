-- Migration: Add points column to document_submissions table
-- This allows instructors to assign bonus points for document quality

-- Add points column to document_submissions
ALTER TABLE `document_submissions` 
ADD COLUMN `points` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'Bonus points awarded by instructor for document quality' AFTER `feedback`;

-- Verification Query
SELECT 
    ds.id,
    u.full_name as student_name,
    dt.name as document_type,
    ds.status,
    ds.points,
    ds.feedback
FROM document_submissions ds
JOIN students s ON ds.student_id = s.id
JOIN users u ON s.user_id = u.id
JOIN document_types dt ON ds.document_type_id = dt.id
ORDER BY ds.submitted_at DESC
LIMIT 10;
