-- SQL Test Queries to Verify Pre-Required Documents Separation
-- Run these queries to verify the fix is working correctly

-- ============================================================
-- 1. Check how many pre-required documents each instructor has
-- ============================================================
SELECT 
    COALESCE(i.user_id, 'System-Wide') as instructor_user_id,
    COALESCE(u.full_name, 'System Documents') as instructor_name,
    COUNT(*) as pre_required_count
FROM document_types dt
LEFT JOIN instructors i ON dt.instructor_id = i.id
LEFT JOIN users u ON i.user_id = u.id
WHERE dt.is_pre_required = 1 
AND dt.is_active = 1
GROUP BY dt.instructor_id, i.user_id, u.full_name
ORDER BY instructor_user_id;

-- ============================================================
-- 2. For a specific student, show what pre-required documents they should see
-- Replace 58 with the actual student ID you want to test
-- ============================================================
SELECT 
    dt.id,
    dt.name,
    dt.code,
    dt.is_pre_required,
    COALESCE(u.full_name, 'System-Wide') as created_by_instructor,
    ds.status as submission_status
FROM students s
JOIN users su ON s.user_id = su.id
LEFT JOIN sections sec ON su.section_id = sec.id
JOIN document_types dt ON (dt.instructor_id = sec.instructor_id OR dt.instructor_id IS NULL)
LEFT JOIN document_submissions ds ON dt.id = ds.document_type_id AND ds.student_id = s.id
LEFT JOIN instructors i ON dt.instructor_id = i.id
LEFT JOIN users u ON i.user_id = u.id
WHERE s.id = 58  -- Change this to test different students
AND dt.is_pre_required = 1
AND dt.is_active = 1
ORDER BY dt.created_at;

-- ============================================================
-- 3. Check attendance eligibility for a specific student
-- This mimics what the checkAttendanceEligibility() method does
-- Replace 58 with the actual student ID you want to test
-- ============================================================

-- Step 1: Get student's instructor
SELECT 
    s.id as student_id,
    su.full_name as student_name,
    sec.instructor_id,
    iu.full_name as instructor_name
FROM students s
JOIN users su ON s.user_id = su.id
LEFT JOIN sections sec ON su.section_id = sec.id
LEFT JOIN instructors i ON sec.instructor_id = i.id
LEFT JOIN users iu ON i.user_id = iu.id
WHERE s.id = 58;  -- Change this to test different students

-- Step 2: Count total pre-required documents for this student's instructor
SELECT COUNT(*) as total_pre_required
FROM students s
JOIN users su ON s.user_id = su.id
LEFT JOIN sections sec ON su.section_id = sec.id
JOIN document_types dt ON (dt.instructor_id = sec.instructor_id OR dt.instructor_id IS NULL)
WHERE s.id = 58  -- Change this to test different students
AND dt.is_pre_required = 1
AND dt.is_active = 1;

-- Step 3: Count approved pre-required documents for this student
SELECT COUNT(DISTINCT dt.id) as approved_pre_required
FROM document_submissions ds
JOIN document_types dt ON ds.document_type_id = dt.id
JOIN students s ON ds.student_id = s.id
JOIN users su ON s.user_id = su.id
LEFT JOIN sections sec ON su.section_id = sec.id
WHERE ds.student_id = 58  -- Change this to test different students
AND dt.is_pre_required = 1
AND dt.is_active = 1
AND (dt.instructor_id = sec.instructor_id OR dt.instructor_id IS NULL)
AND ds.status = 'approved';

-- ============================================================
-- 4. Compare students from different instructors
-- ============================================================
SELECT 
    s.id as student_id,
    su.full_name as student_name,
    sec.section_name,
    iu.full_name as instructor_name,
    (SELECT COUNT(*) 
     FROM document_types dt 
     WHERE dt.is_pre_required = 1 
     AND dt.is_active = 1
     AND (dt.instructor_id = sec.instructor_id OR dt.instructor_id IS NULL)
    ) as total_pre_required,
    (SELECT COUNT(DISTINCT dt.id)
     FROM document_submissions ds
     JOIN document_types dt ON ds.document_type_id = dt.id
     WHERE ds.student_id = s.id
     AND dt.is_pre_required = 1
     AND dt.is_active = 1
     AND (dt.instructor_id = sec.instructor_id OR dt.instructor_id IS NULL)
     AND ds.status = 'approved'
    ) as approved_pre_required
FROM students s
JOIN users su ON s.user_id = su.id
LEFT JOIN sections sec ON su.section_id = sec.id
LEFT JOIN instructors i ON sec.instructor_id = i.id
LEFT JOIN users iu ON i.user_id = iu.id
WHERE s.id IN (57, 58)  -- Student 57 and 58 for comparison
ORDER BY sec.instructor_id, s.id;

-- ============================================================
-- 5. List all pre-required documents by instructor
-- ============================================================
SELECT 
    COALESCE(iu.full_name, 'System-Wide') as instructor,
    dt.id,
    dt.name,
    dt.code,
    dt.is_pre_required,
    dt.is_active,
    dt.created_at
FROM document_types dt
LEFT JOIN instructors i ON dt.instructor_id = i.id
LEFT JOIN users iu ON i.user_id = iu.id
WHERE dt.is_pre_required = 1
ORDER BY dt.instructor_id, dt.created_at;
