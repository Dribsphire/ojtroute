-- Migration: Add instructor_id to document_types table
-- Date: 2026-01-06
-- Description: Adds instructor_id foreign key to track which instructor created/uploaded each document type

-- Step 1: Add instructor_id column
ALTER TABLE document_types 
ADD COLUMN instructor_id INT UNSIGNED NULL 
COMMENT 'ID of the instructor who created this document type';

-- Step 2: Add index first (required for foreign key)
CREATE INDEX idx_document_types_instructor_id ON document_types(instructor_id);

-- Step 3: Add foreign key constraint
-- Note: Adjust the referenced table name if needed (could be 'instructors' or 'instructor')
-- Also ensure the instructor table's id column is INT UNSIGNED
ALTER TABLE document_types 
ADD CONSTRAINT fk_document_types_instructor 
FOREIGN KEY (instructor_id) REFERENCES instructors(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- If the above fails, try this alternative (if table is named 'instructor' not 'instructors'):
-- ALTER TABLE document_types 
-- ADD CONSTRAINT fk_document_types_instructor 
-- FOREIGN KEY (instructor_id) REFERENCES instructor(id) 
-- ON DELETE SET NULL 
-- ON UPDATE CASCADE;

-- Verify the changes
-- DESCRIBE document_types;
-- SHOW CREATE TABLE document_types;
