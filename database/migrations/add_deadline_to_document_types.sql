-- Migration: Add deadline column to document_types table
-- Date: 2026-01-06
-- Description: Adds a deadline column to allow instructors to set submission deadlines for document requirements

-- Add deadline column
ALTER TABLE document_types 
ADD COLUMN deadline DATE NULL 
COMMENT 'Submission deadline for this document type';

-- Verify the column was added
-- SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'ojt_monitoring' 
-- AND TABLE_NAME = 'document_types' 
-- AND COLUMN_NAME = 'deadline';
