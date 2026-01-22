-- Migration: Allow Same Document Code Per Instructor
-- This allows each instructor to create their own "Resume", "Application Letter", etc.
-- without conflicts with other instructors' documents

-- Step 1: Drop the global unique constraint on 'code'
ALTER TABLE `document_types` 
DROP INDEX `unique_code`;

-- Step 2: Add a composite unique constraint on (code, instructor_id)
-- This allows the same code for different instructors, but prevents duplicates within the same instructor
-- Note: NULL instructor_id (system-wide documents) can have duplicate codes since NULL != NULL in unique constraints
ALTER TABLE `document_types` 
ADD UNIQUE KEY `unique_code_per_instructor` (`code`, `instructor_id`);

-- Verification Query: Check current document codes by instructor
SELECT 
    dt.code,
    COALESCE(u.full_name, 'System-Wide') as instructor_name,
    dt.instructor_id,
    COUNT(*) as count
FROM document_types dt
LEFT JOIN instructors i ON dt.instructor_id = i.id
LEFT JOIN users u ON i.user_id = u.id
GROUP BY dt.code, dt.instructor_id, u.full_name
ORDER BY dt.code, dt.instructor_id;
