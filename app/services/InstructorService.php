<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

class InstructorService
{
    private $db;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        try {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->db = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new \Exception('Database connection failed');
        }
    }

    /**
     * Get instructor ID from user ID
     * 
     * @param int $userID User ID from session
     * @return int|null Instructor ID or null if not found
     */
    public function getInstructorId($userID)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id 
                FROM instructors 
                WHERE user_id = :user_id
            ");

            $stmt->execute([':user_id' => $userID]);
            $result = $stmt->fetch();

            return $result ? $result['id'] : null;
        } catch (\PDOException $e) {
            error_log('Get instructor ID error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get instructor profile
     * 
     * @param int $userId User ID
     * @return array|null Instructor profile data
     */
    public function getInstructorProfile($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.contact,
                    u.facebook_name,
                    u.profile_pic_path,
                    u.role,
                    i.department
                FROM users u
                LEFT JOIN instructors i ON u.id = i.user_id
                WHERE u.id = :user_id
                AND u.role = 'instructor'
            ");

            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Get instructor profile error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update instructor personal information
     * 
     * @param int $userId User ID
     * @param array $data Associative array of data to update
     * @return bool True on success
     */
    public function updateInstructorInfo($userId, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET full_name = :fullname,
                    email = :email,
                    contact = :contact,
                    facebook_name = :facebook
                WHERE id = :id
            ");

            return $stmt->execute([
                ':fullname' => $data['fullname'],
                ':email' => $data['email'],
                ':contact' => $data['contact'],
                ':facebook' => $data['facebook'],
                ':id' => $userId
            ]);
        } catch (\PDOException $e) {
            error_log('Update instructor info error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Change password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Get current hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $currentHash = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $currentHash)) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }

            // Update with new hash
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $result = $update->execute([':hash' => $newHash, ':id' => $userId]);

            return ['success' => $result, 'message' => $result ? 'Password updated successfully.' : 'Database error.'];
        } catch (\PDOException $e) {
            error_log('Change password error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred.'];
        }
    }

    /**
     * Update profile picture path
     * 
     * @param int $userId
     * @param string $path
     * @return bool
     */
    public function updateProfilePicturePath($userId, $path)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET profile_pic_path = :path WHERE id = :id");
            return $stmt->execute([':path' => $path, ':id' => $userId]);
        } catch (\PDOException $e) {
            error_log('Update profile pic error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all sections assigned to an instructor
     * 
     * @param int $instructorId Instructor ID
     * @return array Array of sections
     */
    public function getInstructorSections($instructorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    section_code,
                    section_name,
                    department,
                    year,
                    is_active
                FROM sections
                WHERE instructor_id = :instructor_id
                AND is_active = 1
                ORDER BY section_code ASC
            ");

            $stmt->execute([':instructor_id' => $instructorId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Get instructor sections error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all students assigned to an instructor's sections
     * 
     * @param int $instructorId Instructor ID
     * @return array Array of students with their details
     */
    public function getInstructorStudents($instructorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.contact,
                    u.profile_pic_path,
                    s.section_code,
                    s.section_name,
                    s.department,
                    sw.company_name,
                    sw.company_address,
                    sw.company_head,
                    sw.position_title,
                    sw.is_active as workplace_active,
                    COALESCE(os.hours_completed, 0) as total_hours,
                    COALESCE(students.target_ojt_hours, 600) as target_hours,
                    os.last_updated as hours_last_updated,
                    os.manual_adjustment_hours,
                    os.adjustment_reason,
                    students.id as student_db_id
                FROM users u
                INNER JOIN sections s ON u.section_id = s.id
                LEFT JOIN students ON u.id = students.user_id
                LEFT JOIN student_workplaces sw ON students.id = sw.student_id AND sw.is_active = 1
                LEFT JOIN ojt_summaries os ON students.id = os.student_id
                WHERE s.instructor_id = :instructor_id
                AND u.role = 'student'
                AND u.is_archived = 0
                ORDER BY s.section_code ASC, u.full_name ASC
            ");

            $stmt->execute([':instructor_id' => $instructorId]);
            $students = $stmt->fetchAll();

            // Process the data to add computed fields
            foreach ($students as &$student) {
                $student['status'] = $student['workplace_active'] ? 'active' : 'inactive';
                $student['progress_percentage'] = $student['target_hours'] > 0
                    ? round(($student['total_hours'] / $student['target_hours']) * 100, 1)
                    : 0;
            }

            return $students;
        } catch (\PDOException $e) {
            error_log('Get instructor students error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed student information
     * 
     * @param int $userId Student User ID
     * @return array|null Student details
     */
    public function getStudentDetails($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.contact,
                    u.profile_pic_path,
                    s.section_name,
                    students.id as student_db_id,
                    sw.company_name,
                    sw.company_address,
                    sw.company_head as supervisor_name,
                    sw.start_date,
                    sw.workplace_latitude as latitude,
                    sw.workplace_longitude as longitude,
                    COALESCE(os.hours_completed, 0) as total_hours
                FROM users u
                INNER JOIN sections s ON u.section_id = s.id
                LEFT JOIN students ON u.id = students.user_id
                LEFT JOIN student_workplaces sw ON students.id = sw.student_id AND sw.is_active = 1
                LEFT JOIN ojt_summaries os ON students.id = os.student_id
                WHERE u.id = :user_id
                AND u.role = 'student'
            ");

            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('Get student details error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update student OJT hours (manual adjustment)
     * 
     * @param int $studentId Student ID (from students table)
     * @param float $newHours New total hours
     * @param int $instructorId Instructor ID making the change
     * @param string $reason Reason for adjustment
     * @return bool Success status
     */
    public function updateStudentHours($studentId, $newHours, $instructorId, $reason = 'Manual adjustment by instructor')
    {
        try {
            $this->db->beginTransaction();

            // Get current hours from attendance records
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(hours), 0) as current_hours
                FROM attendance_records
                WHERE student_id = :student_id
                AND status = 'completed'
            ");
            $stmt->execute([':student_id' => $studentId]);
            $result = $stmt->fetch();
            $currentHours = $result['current_hours'];

            // Calculate the adjustment needed
            $adjustment = $newHours - $currentHours;

            // Update or insert into ojt_summaries table
            $stmt = $this->db->prepare("
                INSERT INTO ojt_summaries 
                    (student_id, hours_completed, manual_adjustment_hours, adjusted_by_instructor_id, adjustment_reason)
                VALUES 
                    (:student_id, :hours_completed, :adjustment, :instructor_id, :reason)
                ON DUPLICATE KEY UPDATE
                    hours_completed = :hours_completed_update,
                    manual_adjustment_hours = manual_adjustment_hours + :adjustment_update,
                    adjusted_by_instructor_id = :instructor_id_update,
                    adjustment_reason = :reason_update,
                    last_updated = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                ':student_id' => $studentId,
                ':hours_completed' => $newHours,
                ':adjustment' => $adjustment,
                ':instructor_id' => $instructorId,
                ':reason' => $reason,
                ':hours_completed_update' => $newHours,
                ':adjustment_update' => $adjustment,
                ':instructor_id_update' => $instructorId,
                ':reason_update' => $reason
            ]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Update student hours error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add hours to all students in a section
     * 
     * @param int $sectionId Section ID
     * @param float $hoursToAdd Hours to add
     * @param int $instructorId Instructor ID making the change
     * @param string $reason Reason for bulk adjustment
     * @return bool Success status
     */
    public function addHoursToSection($sectionId, $hoursToAdd, $instructorId, $reason = 'Bulk hours adjustment')
    {
        try {
            $this->db->beginTransaction();

            // Get all students in the section
            $stmt = $this->db->prepare("
                SELECT students.id
                FROM users u
                INNER JOIN students ON u.id = students.user_id
                WHERE u.section_id = :section_id
                AND u.role = 'student'
                AND u.is_archived = 0
            ");
            $stmt->execute([':section_id' => $sectionId]);
            $students = $stmt->fetchAll();

            // Add hours to each student
            foreach ($students as $student) {
                // Get current total hours
                $stmt = $this->db->prepare("
                    SELECT COALESCE(hours_completed, 0) as current_hours
                    FROM ojt_summaries
                    WHERE student_id = :student_id
                ");
                $stmt->execute([':student_id' => $student['id']]);
                $result = $stmt->fetch();
                $currentHours = $result ? $result['current_hours'] : 0;

                $newHours = $currentHours + $hoursToAdd;

                // Update ojt_summaries
                $stmt = $this->db->prepare("
                    INSERT INTO ojt_summaries 
                        (student_id, hours_completed, manual_adjustment_hours, adjusted_by_instructor_id, adjustment_reason)
                    VALUES 
                        (:student_id, :hours_completed, :adjustment, :instructor_id, :reason)
                    ON DUPLICATE KEY UPDATE
                        hours_completed = hours_completed + :adjustment_update,
                        manual_adjustment_hours = manual_adjustment_hours + :adjustment_update_2,
                        adjusted_by_instructor_id = :instructor_id_update,
                        adjustment_reason = :reason_update,
                        last_updated = CURRENT_TIMESTAMP
                ");

                $stmt->execute([
                    ':student_id' => $student['id'],
                    ':hours_completed' => $newHours,
                    ':adjustment' => $hoursToAdd,
                    ':instructor_id' => $instructorId,
                    ':reason' => $reason,
                    ':adjustment_update' => $hoursToAdd,
                    ':adjustment_update_2' => $hoursToAdd, // Duplicate usage needs unique name too if emulation is off
                    ':instructor_id_update' => $instructorId,
                    ':reason_update' => $reason
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Add hours to section error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database connection
     * 
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }
    /**
     * Get all active document types for a specific instructor
     * Returns documents created by the instructor OR system-wide documents (instructor_id IS NULL)
     * 
     * @param int|null $instructorId
     * @return array
     */
    public function getAllDocumentTypes($instructorId = null)
    {
        try {
            if ($instructorId) {
                // Get documents created by this instructor OR system-wide documents
                $stmt = $this->db->prepare("
                    SELECT * FROM document_types 
                    WHERE is_active = 1 
                    AND (instructor_id = :instructor_id OR instructor_id IS NULL)
                    ORDER BY is_pre_required DESC, name ASC
                ");
                $stmt->execute([':instructor_id' => $instructorId]);
            } else {
                // Get all documents (for backward compatibility)
                $stmt = $this->db->query("
                    SELECT * FROM document_types 
                    WHERE is_active = 1 
                    ORDER BY is_pre_required DESC, name ASC
                ");
            }
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Get document types error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update document type details
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateDocumentType($id, $data)
    {
        try {
            // Filter allowed fields
            $allowed = ['description', 'is_required', 'is_pre_required', 'category'];
            $updates = [];
            $params = [':id' => $id];

            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($updates))
                return true;

            $sql = "UPDATE document_types SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log('Update document type error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload template for a document type
     * 
     * @param int $typeId
     * @param array $file $_FILES['file']
     * @return array ['success' => bool, 'message' => string]
     */
    public function uploadDocumentTemplate($typeId, $file)
    {
        try {
            // validate file
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Invalid file type. Only PDF and Word documents allowed.'];
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                return ['success' => false, 'message' => 'File too large. Max 5MB.'];
            }

            // Create dir if not exists
            $uploadDir = __DIR__ . '/../../storage/uploads/templates/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'template_' . $typeId . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Update DB
                $dbPath = '../../storage/uploads/templates/' . $filename;
                $stmt = $this->db->prepare("UPDATE document_types SET template_path = :path WHERE id = :id");
                $stmt->execute([':path' => $dbPath, ':id' => $typeId]);

                return ['success' => true, 'message' => 'Template uploaded successfully'];
            }

            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        } catch (\PDOException $e) {
            error_log('Upload template error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Get student submissions for instructor's sections
     * 
     * @param int $instructorId
     * @return array
     */
    public function getStudentSubmissions($instructorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ds.id,
                    ds.status,
                    ds.submitted_at,
                    ds.feedback,
                    ds.points,
                    ds.accuracyQualityPoints,
                    ds.professionalPresentationPoints,
                    ds.file_path,
                    u.full_name as student_name,
                    s.section_name as section,
                    dt.name as document_type,
                    dt.is_pre_required,
                    dt.category,
                    dt.deadline
                FROM document_submissions ds
                JOIN students st ON ds.student_id = st.id
                JOIN users u ON st.user_id = u.id
                JOIN sections s ON u.section_id = s.id
                JOIN document_types dt ON ds.document_type_id = dt.id
                WHERE s.instructor_id = :instructor_id
                ORDER BY ds.submitted_at DESC
            ");

            $stmt->execute([':instructor_id' => $instructorId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Get submissions error: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Create a new document type
     *
     * @param string $name
     * @param string $code
     * @param string $category
     * @param string|null $deadline
     * @param int|null $instructorId
     * @param array $file (Optional)
     * @return array
     */
    public function createDocumentType($name, $code, $category, $deadline = null, $instructorId = null, $file = null)
    {
        try {
            // Check if code already exists for this instructor
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM document_types 
                WHERE code = :code 
                AND instructor_id = :instructor_id
            ");
            $checkStmt->execute([
                ':code' => $code,
                ':instructor_id' => $instructorId
            ]);

            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'You already have a document with this code. Please use a different code.'];
            }

            $isPreRequired = ($category === 'pre_required') ? 1 : 0;
            $frequency = 'once';

            $stmt = $this->db->prepare("
                INSERT INTO document_types (name, code, category, is_pre_required, is_required, frequency, deadline, instructor_id, is_active)
                VALUES (:name, :code, :category, :is_pre_required, 1, :frequency, :deadline, :instructor_id, 1)
            ");

            $stmt->execute([
                ':name' => $name,
                ':code' => $code,
                ':category' => $category,
                ':is_pre_required' => $isPreRequired,
                ':frequency' => $frequency,
                ':deadline' => $deadline,
                ':instructor_id' => $instructorId
            ]);

            $documentId = $this->db->lastInsertId();

            if ($file && $file['size'] > 0) {
                // Reuse existing upload logic
                return $this->uploadDocumentTemplate($documentId, $file);
            }

            return ['success' => true, 'message' => 'Document type created successfully'];

        } catch (\PDOException $e) {
            error_log('Create document type error: ' . $e->getMessage());

            // Check for duplicate code error (composite unique key)
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'unique_code_per_instructor') !== false) {
                return ['success' => false, 'message' => 'You already have a document with this code. Please use a different code.'];
            }

            return ['success' => false, 'message' => 'Failed to create document type. Please try again.'];
        }
    }
    /**
     * Update submission status
     * @param int $submissionId
     * @param string $status
     * @param string $feedback
     * @param float|null $points Bonus points
     * @param float|null $accuracyQualityPoints Accuracy & Quality points
     * @param float|null $professionalPresentationPoints Professional Presentation points
     * @return array
     */
    public function updateSubmissionStatus($submissionId, $status, $feedback, $points = null, $accuracyQualityPoints = null, $professionalPresentationPoints = null)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE document_submissions 
                SET status = :status, 
                    feedback = :feedback, 
                    points = :points,
                    accuracyQualityPoints = :accuracyQualityPoints,
                    professionalPresentationPoints = :professionalPresentationPoints
                WHERE id = :id
            ");
            $result = $stmt->execute([
                ':status' => $status,
                ':feedback' => $feedback,
                ':points' => $points,
                ':accuracyQualityPoints' => $accuracyQualityPoints,
                ':professionalPresentationPoints' => $professionalPresentationPoints,
                ':id' => $submissionId
            ]);

            return $result ? ['success' => true, 'message' => 'Submission updated'] : ['success' => false, 'message' => 'Update failed'];
        } catch (\PDOException $e) {
            error_log('Update submission error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Bulk update submission status
     * @param array $submissionIds
     * @param string $status
     * @return array
     */
    public function bulkUpdateSubmissionStatus($submissionIds, $status)
    {
        try {
            if (empty($submissionIds))
                return ['success' => false, 'message' => 'No items selected'];

            $inQuery = implode(',', array_fill(0, count($submissionIds), '?'));
            $sql = "UPDATE document_submissions SET status = ? WHERE id IN ($inQuery)";

            $stmt = $this->db->prepare($sql);
            $params = array_merge([$status], $submissionIds);
            $result = $stmt->execute($params);

            return $result ? ['success' => true, 'message' => 'Bulk update successful'] : ['success' => false, 'message' => 'Update failed'];
        } catch (\PDOException $e) {
            error_log('Bulk update error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Get approved pre-required documents for a student
     * @param int $studentId
     * @return array
     */
    public function getApprovedPreReqDocuments($studentId)
    {
        try {
            $sql = "
                SELECT 
                    dt.name,
                    dt.code,
                    dt.category,
                    ds.submitted_at,
                    ds.file_path,
                    ds.status,
                    ds.feedback,
                    ds.points
                FROM document_types dt
                JOIN document_submissions ds ON dt.id = ds.document_type_id
                WHERE ds.student_id = :student_id
                  AND dt.is_pre_required = 1
                  AND ds.status = 'approved'
                ORDER BY ds.submitted_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $studentId]);
            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            error_log('Get approved docs error: ' . $e->getMessage());
            return [];
        }
    }
    /**
     * Get count of pending submissions for an instructor's section
     * @param int $instructorId
     * @return int
     */
    public function getPendingSubmissionsCount($instructorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(ds.id) 
                FROM document_submissions ds
                JOIN students s ON ds.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN sections sec ON u.section_id = sec.id
                WHERE sec.instructor_id = :instructor_id
                  AND ds.status = 'pending'
            ");
            $stmt->execute([':instructor_id' => $instructorId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Delete a document type
     * 
     * @param int $id Document Type ID
     * @param int $instructorId Instructor ID (for authorization)
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteDocumentType($id, $instructorId)
    {
        try {
            // First check if the document type exists and belongs to the instructor
            $stmt = $this->db->prepare("SELECT id, name FROM document_types WHERE id = :id AND instructor_id = :instructor_id");
            $stmt->execute([':id' => $id, ':instructor_id' => $instructorId]);
            $docType = $stmt->fetch();

            if (!$docType) {
                return ['success' => false, 'message' => 'Document type not found or you do not have permission to delete it.'];
            }

            // Check if there are any submissions for this document type
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM document_submissions WHERE document_type_id = :id");
            $checkStmt->execute([':id' => $id]);
            $submissionCount = $checkStmt->fetchColumn();

            if ($submissionCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete document type because student submissions exist for it.'];
            }

            // Proceed with deletion
            $deleteStmt = $this->db->prepare("DELETE FROM document_types WHERE id = :id");
            $result = $deleteStmt->execute([':id' => $id]);

            return $result ? ['success' => true, 'message' => 'Document type deleted successfully.'] : ['success' => false, 'message' => 'Failed to delete document type.'];

        } catch (\PDOException $e) {
            error_log('Delete document type error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
    }
}
