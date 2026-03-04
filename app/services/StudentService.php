<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

class StudentService
{
    private $db;
    private $config;

    public function __construct()
    {
        // Load database configuration
        $configPath = __DIR__ . '/../../config/database.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            throw new \Exception('Database configuration file not found');
        }

        // Initialize database connection
        $this->connect();
    }

    /**
     * Get student DB ID from User ID
     * @param int $userId
     * @return int|false
     */
    public function getStudentDbId($userId)
    {
        $stmt = $this->db->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['dbname'],
                $this->config['charset']
            );

            $this->db = new \PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get student profile data
     * 
     * @param int $studentId User ID from session
     * @return array|null Student profile data or null if not found
     */
    public function getStudentProfile($studentId)
    {
        try {
            // Get all admin users (OJT Chairpersons)
            $adminStmt = $this->db->prepare("
            SELECT 
                id,
                full_name,
                CASE 
                    WHEN profile_pic_path IS NULL THEN NULL
                    WHEN profile_pic_path LIKE '../%' THEN profile_pic_path
                    WHEN profile_pic_path LIKE 'storage/%' THEN CONCAT('../../', profile_pic_path)
                    ELSE profile_pic_path
                END as profile_pic_path
            FROM users
            WHERE role = 'admin' 
            AND is_archived = 0
            ORDER BY full_name ASC
        ");
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll(); // Get ALL admins

            $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.school_id,
                u.full_name,
                u.email,
                u.contact,
                u.facebook_name,
                u.profile_pic_path,
                u.year,
                u.section_id,
                s.section_code,
                s.section_name,
                s.department,
                instructor_user.full_name as instructor_name,
                instructor_user.email as instructor_email,
                instructor_user.profile_pic_path as instructor_profile_pic,
                COALESCE(students.target_ojt_hours, 600) as target_ojt_hours,
                sw.company_name,
                sw.company_head,
                sw.company_address,
                sw.position_title,
                sw.workplace_latitude,
                sw.workplace_longitude,
                sw.supervisor_position,
                sw.head_trainee,
                sw.head_trainee_position,
                sw.head_trainee_contact,
                sw.head_trainee_email,
                sw.schedule_start_time,
                sw.schedule_end_time
            FROM users u
            LEFT JOIN sections s ON u.section_id = s.id
            LEFT JOIN instructors ON s.instructor_id = instructors.id
            LEFT JOIN users instructor_user ON instructors.user_id = instructor_user.id
            LEFT JOIN students ON u.id = students.user_id
            LEFT JOIN student_workplaces sw ON students.id = sw.student_id AND sw.is_active = 1
            WHERE u.id = :student_id 
            AND u.role = 'student'
            AND u.is_archived = 0
            LIMIT 1
        ");

            $stmt->execute([':student_id' => $studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                return null;
            }

            // Get OJT hours progress
            $ojtHours = $this->getOJTHoursProgress($studentId);

            // Build profile array
            return [
                'id' => $student['id'],
                'school_id' => $student['school_id'],
                'fullname' => $student['full_name'],
                'email' => $student['email'],
                'contact' => $student['contact'] ?: 'Not provided',
                'facebook' => $student['facebook_name'] ?: 'Not provided',
                'profile_pic' => $student['profile_pic_path'] ?: '../../storage/images/default_profile.jpg',
                'section' => $student['section_name'] ?: 'Not assigned',
                'department' => $student['department'] ?: 'Not assigned',
                'year' => $student['year'] ?: 'Not specified',
                'instructor' => $student['instructor_name'] ?: 'Not assigned',
                'instructor_email' => $student['instructor_email'] ?: 'Not available',
                'instructor_profile' => $student['instructor_profile_pic'] ?: '../../storage/images/default_profile.jpg',
                'admins' => $admins, // Array of all admins
                'workplace' => $student['company_name'] ?: 'Not assigned',
                'supervisor' => $student['company_head'] ?: 'Not assigned',
                'position' => $student['position_title'] ?: 'Intern',
                'workplace_address' => $student['company_address'] ?? 'Not available',
                'workplace_contact' => 'Not available',
                'supervisor_position' => $student['supervisor_position'] ?? '',
                'head_trainee' => $student['head_trainee'] ?? '',
                'head_trainee_position' => $student['head_trainee_position'] ?? '',
                'head_trainee_contact' => $student['head_trainee_contact'] ?? '',
                'head_trainee_email' => $student['head_trainee_email'] ?? '',
                'latitude' => $student['workplace_latitude'],
                'longitude' => $student['workplace_longitude'],
                'schedule_start_time' => $student['schedule_start_time'],
                'schedule_end_time' => $student['schedule_end_time'],
                'ojt_hours' => $ojtHours,
                'classmates' => $this->getClassmates($student['section_id'], $studentId)
            ];
        } catch (\PDOException $e) {
            error_log('Get student profile error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get classmates for a student
     * 
     * @param int $sectionId Section ID
     * @param int $excludeUserId User ID to exclude (current student)
     * @return array List of classmates
     */
    private function getClassmates($sectionId, $excludeUserId)
    {
        if (!$sectionId)
            return [];

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.full_name,
                    CASE 
                        WHEN u.profile_pic_path IS NULL THEN NULL
                        WHEN u.profile_pic_path LIKE '../%' THEN u.profile_pic_path
                        WHEN u.profile_pic_path LIKE 'storage/%' THEN CONCAT('../../', u.profile_pic_path)
                        ELSE u.profile_pic_path
                    END as profile_pic_path
                FROM users u
                JOIN students s ON u.id = s.user_id
                WHERE u.section_id = :section_id
                AND u.role = 'student'
                AND u.is_archived = 0
                AND u.id != :exclude_id
                ORDER BY u.full_name ASC
            ");

            $stmt->execute([
                ':section_id' => $sectionId,
                ':exclude_id' => $excludeUserId
            ]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Get classmates error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get OJT hours progress for a student
     * 
     * @param int $userId User ID (from session)
     * @return array OJT hours data
     */
    private function getOJTHoursProgress($userId)
    {
        try {
            // Get completed hours from ojt_summaries
            $stmt = $this->db->prepare("
                SELECT 
                    os.hours_completed,
                    os.last_updated,
                    COALESCE(s.target_ojt_hours, 600) as target_hours
                FROM users u
                JOIN students s ON u.id = s.user_id
                LEFT JOIN ojt_summaries os ON s.id = os.student_id
                WHERE u.id = :user_id
            ");

            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch();

            $completed = $result['hours_completed'] ? floatval($result['hours_completed']) : 0;
            $total = $result['target_hours'] ? floatval($result['target_hours']) : 600;
            $lastUpdated = isset($result['last_updated']) ? date('Y-m-d', strtotime($result['last_updated'])) : date('Y-m-d');

            // Calculate progress percentage
            // CRITICAL FIX: Cap progress at 100% to prevent UI overflow
            $progress = $total > 0 ? min(100, round(($completed / $total) * 100, 2)) : 0;

            return [
                'completed' => $completed,
                'total' => $total,
                'last_updated' => $lastUpdated,
                'progress' => $progress
            ];
        } catch (\PDOException $e) {
            error_log('Get OJT hours error: ' . $e->getMessage());
            return [
                'completed' => 0,
                'total' => 600,
                'last_updated' => date('Y-m-d'),
                'progress' => 0
            ];
        }
    }

    /**
     * Update student profile
     * 
     * @param int $studentId Student ID
     * @param array $data Profile data to update
     * @return bool Success status
     */
    public function updateStudentProfile($studentId, $data)
    {
        try {
            $allowedFields = ['email', 'contact', 'facebook_name'];
            $updateFields = [];
            $updateValues = [':student_id' => $studentId];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = :$field";
                    $updateValues[":$field"] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                return false; // No valid fields to update
            }

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :student_id AND role = 'student'";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($updateValues);
        } catch (\PDOException $e) {
            error_log('Update student profile error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update student profile picture
     * 
     * @param int $studentId Student ID
     * @param string $profilePicPath Path to profile picture
     * @return bool Success status
     */
    public function updateProfilePicture($studentId, $profilePicPath)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET profile_pic_path = :profile_pic_path 
                WHERE id = :student_id 
                AND role = 'student'
            ");

            return $stmt->execute([
                ':profile_pic_path' => $profilePicPath,
                ':student_id' => $studentId
            ]);
        } catch (\PDOException $e) {
            error_log('Update profile picture error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student schedule (start/end times) from active workplace
     * @param int $studentId Student DB ID (students.id)
     * @return array|null ['schedule_start_time' => 'HH:MM:SS', 'schedule_end_time' => 'HH:MM:SS'] or null
     */
    public function getStudentSchedule($studentId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT schedule_start_time, schedule_end_time
                FROM student_workplaces
                WHERE student_id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch();
            if ($result && $result['schedule_start_time'] && $result['schedule_end_time']) {
                return $result;
            }
            return null;
        } catch (\PDOException $e) {
            error_log('Get student schedule error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a schedule is a cross-day (night) shift
     * A cross-day shift has an end time earlier than its start time (e.g., 21:00 -> 05:00)
     * @param string $startTime HH:MM:SS
     * @param string $endTime HH:MM:SS
     * @return bool
     */
    public function isCrossDayShift($startTime, $endTime)
    {
        return $endTime < $startTime;
    }

    /**
     * Update student schedule
     * @param int $userId User ID from session
     * @param string $startTime Start time in HH:MM format
     * @param string $endTime End time in HH:MM format
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStudentSchedule($userId, $startTime, $endTime)
    {
        try {
            // Get student DB ID
            $studentDbId = $this->getStudentDbId($userId);
            if (!$studentDbId) {
                return ['success' => false, 'message' => 'Student not found.'];
            }


            // Validate times
            $startDt = \DateTime::createFromFormat('H:i', $startTime);
            $endDt = \DateTime::createFromFormat('H:i', $endTime);
            if (!$startDt || !$endDt) {
                return ['success' => false, 'message' => 'Invalid time format. Please use HH:MM.'];
            }

            // Disallow identical times (no 24-hour shift)
            if ($startDt == $endDt) {
                return ['success' => false, 'message' => 'Start and end time cannot be the same.'];
            }

            // If end time is earlier, assume next day (night shift)
            if ($endDt < $startDt) {
                $endDt->modify('+1 day');
            }

            // Optional: limit maximum shift length (e.g. 16 hours)
            $interval = $startDt->diff($endDt);
            $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);

            if ($totalHours > 16) {
                return ['success' => false, 'message' => 'Shift cannot exceed 16 hours.'];
            }

            // Update the active workplace record
            $stmt = $this->db->prepare("
                UPDATE student_workplaces
                SET schedule_start_time = :start_time, schedule_end_time = :end_time
                WHERE student_id = :student_id AND is_active = 1
            ");
            $result = $stmt->execute([
                ':start_time' => $startTime . ':00',
                ':end_time' => $endTime . ':00',
                ':student_id' => $studentDbId
            ]);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Working schedule updated successfully.'];
            } else {
                // rowCount=0 could mean same values (no actual change) or no active workplace
                $check = $this->db->prepare("SELECT id FROM student_workplaces WHERE student_id = :sid AND is_active = 1");
                $check->execute([':sid' => $studentDbId]);
                if ($check->fetch()) {
                    return ['success' => true, 'message' => 'Schedule is already up to date.'];
                }
                return ['success' => false, 'message' => 'No active workplace found.'];
            }
        } catch (\PDOException $e) {
            error_log('Update student schedule error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
    }

    /**
     * Check if a date is a double hours date
     * @param string $date Date in Y-m-d format
     * @return bool
     */
    public function isDoubleHoursDate($date)
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM double_hours_dates WHERE date = ? LIMIT 1");
            $stmt->execute([$date]);
            return (bool) $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Submit workplace change request
     * 
     * @param int $userId User ID
     * @param array $workplaceData Workplace information
     * @return bool Success status
     */
    public function submitWorkplaceChangeRequest($userId, $workplaceData)
    {
        try {
            // Note: workplace_change_requests table links to users table via student_id column
            // based on the foreign key constraint observed in logs.

            $stmt = $this->db->prepare("
                INSERT INTO workplace_change_requests (
                    student_id, 
                    workplace_name, 
                    workplace_address,
                    position_title,
                    supervisor_name,
                    supervisor_position,
                    head_trainee,
                    head_trainee_position,
                    head_trainee_contact,
                    head_trainee_email, 
                    latitude, 
                    longitude, 
                    change_reason, 
                    status, 
                    created_at
                ) VALUES (
                    :student_id,
                    :workplace_name,
                    :workplace_address,
                    :position_title,
                    :supervisor_name,
                    :supervisor_position,
                    :head_trainee,
                    :head_trainee_position,
                    :head_trainee_contact,
                    :head_trainee_email,
                    :latitude,
                    :longitude,
                    :change_reason,
                    'pending',
                    NOW()
                )
            ");

            return $stmt->execute([
                ':student_id' => $userId,
                ':workplace_name' => $workplaceData['workplace_name'],
                ':workplace_address' => $workplaceData['workplace_address'],
                ':position_title' => $workplaceData['position'] ?? '',
                ':supervisor_name' => $workplaceData['supervisor_name'] ?? '',
                ':supervisor_position' => $workplaceData['supervisor_position'] ?? '',
                ':head_trainee' => $workplaceData['head_trainee'] ?? '',
                ':head_trainee_position' => $workplaceData['head_trainee_position'] ?? '',
                ':head_trainee_contact' => $workplaceData['head_trainee_contact'] ?? '',
                ':head_trainee_email' => $workplaceData['head_trainee_email'] ?? '',
                ':latitude' => $workplaceData['latitude'],
                ':longitude' => $workplaceData['longitude'],
                ':change_reason' => $workplaceData['change_reason']
            ]);
        } catch (\PDOException $e) {
            error_log('Submit workplace change request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if student has a pending workplace change request
     * 
     * @param int $userId User ID (student_id in workplace_change_requests)
     * @return bool True if a pending request exists
     */
    public function hasPendingWorkplaceRequest($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt 
                FROM workplace_change_requests 
                WHERE student_id = :student_id AND status = 'pending'
            ");
            $stmt->execute([':student_id' => $userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return ($row['cnt'] > 0);
        } catch (\PDOException $e) {
            error_log('Check pending workplace request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update active workplace details
     * 
     * @param int $studentId Student ID (users.id, convert to students.id)
     * @param array $data Data to update
     * @return bool
     */
    public function updateActiveWorkplace($userId, $data)
    {
        try {
            // Get student DB ID first
            $stmt = $this->db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$userId]);
            $studentDbId = $stmt->fetchColumn();

            if (!$studentDbId)
                return false;

            // Check if active workplace exists
            $stmt = $this->db->prepare("SELECT id FROM student_workplaces WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$studentDbId]);
            $existingId = $stmt->fetchColumn();

            $allowed = [
                'company_name',
                'company_head',
                'position_title',
                'workplace_latitude',
                'workplace_longitude',
                'company_address',
                'supervisor_position',
                'head_trainee',
                'head_trainee_position',
                'head_trainee_contact',
                'head_trainee_email'
            ];

            if ($existingId) {
                // UPDATE existing record
                $fields = [];
                $values = [':id' => $existingId];

                foreach ($allowed as $field) {
                    if (isset($data[$field])) {
                        $fields[] = "$field = :$field";
                        $values[":$field"] = $data[$field];
                    }
                }

                if (empty($fields))
                    return true; // Nothing to update

                $sql = "UPDATE student_workplaces 
                        SET " . implode(', ', $fields) . " 
                        WHERE id = :id";

                $stmt = $this->db->prepare($sql);
                return $stmt->execute($values);
            } else {
                // INSERT new record (Active)
                // company_name is required
                if (empty($data['company_name'])) {
                    return false;
                }

                $cols = ['student_id', 'is_active', 'start_date'];
                $placeholders = [':student_id', '1', 'CURDATE()'];
                $values = [':student_id' => $studentDbId];

                foreach ($allowed as $field) {
                    if (isset($data[$field])) {
                        $cols[] = $field;
                        $placeholders[] = ":$field";
                        $values[":$field"] = $data[$field];
                    }
                }

                $sql = "INSERT INTO student_workplaces (" . implode(', ', $cols) . ") 
                        VALUES (" . implode(', ', $placeholders) . ")";

                $stmt = $this->db->prepare($sql);
                return $stmt->execute($values);
            }
        } catch (\PDOException $e) {
            error_log('Update workplace error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if student has already set up their workplace
     * 
     * @param int $userId User ID from session
     * @return bool True if workplace exists, false otherwise
     */
    public function hasWorkplace($userId)
    {
        try {
            // Get student DB ID first
            $stmt = $this->db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$userId]);
            $studentDbId = $stmt->fetchColumn();

            if (!$studentDbId)
                return false;

            // Check if active workplace exists
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM student_workplaces WHERE student_id = ? AND is_active = 1");
            $stmt->execute([$studentDbId]);
            $count = $stmt->fetchColumn();

            return $count > 0;
        } catch (\PDOException $e) {
            error_log('Check workplace error: ' . $e->getMessage());
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
     * Get all document requirements and student's submission status
     * Only shows documents created by the student's instructor OR system-wide documents
     * @param int $userId
     * @return array
     */
    public function getStudentDocuments($userId)
    {
        try {
            // Get student ID and section instructor
            $stmt = $this->db->prepare("
                SELECT s.id, u.section_id, sec.instructor_id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN sections sec ON u.section_id = sec.id
                WHERE s.user_id = ?
            ");
            $stmt->execute([$userId]);
            $studentData = $stmt->fetch();

            if (!$studentData)
                return [];

            $studentId = $studentData['id'];
            $instructorId = $studentData['instructor_id'];


            // Fetch document types created by student's instructor OR system-wide (NULL instructor_id)
            // Left join with student's submissions
            $sql = "
                SELECT 
                    dt.id as document_type_id,
                    dt.name,
                    dt.code,
                    dt.category,
                    dt.is_pre_required,
                    dt.template_path,
                    dt.instructor_id,
                    dt.deadline,
                    ds.id as submission_id,
                    ds.status,
                    ds.submitted_at,
                    ds.file_path,
                    ds.feedback
                FROM document_types dt
                LEFT JOIN document_submissions ds ON dt.id = ds.document_type_id AND ds.student_id = :student_id
                WHERE dt.is_active = 1
                AND (dt.instructor_id = :instructor_id OR dt.instructor_id IS NULL)
                ORDER BY dt.is_pre_required DESC, dt.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $studentId,
                ':instructor_id' => $instructorId
            ]);
            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            error_log('Get student documents error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of new documents uploaded by instructor in the last 7 days
     * @param int $userId
     * @return int Count of new documents
     */
    public function getNewDocumentsCount($userId)
    {
        try {
            // Get student's instructor
            $stmt = $this->db->prepare("
                SELECT sec.instructor_id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN sections sec ON u.section_id = sec.id
                WHERE s.user_id = ?
            ");
            $stmt->execute([$userId]);
            $data = $stmt->fetch();

            if (!$data || !$data['instructor_id']) {
                return 0;
            }

            $instructorId = $data['instructor_id'];

            // Count documents created by instructor in last 7 days
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM document_types 
                WHERE instructor_id = :instructor_id 
                AND is_active = 1
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([':instructor_id' => $instructorId]);

            return (int) $stmt->fetchColumn();

        } catch (\PDOException $e) {
            error_log('Get new documents count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if student serves attendance requirements
     * @param int $studentId
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function checkAttendanceEligibility($studentId)
    {
        // Check Workplace
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM student_workplaces WHERE student_id = ? AND is_active = 1");
        $stmt->execute([$studentId]);
        if ($stmt->fetchColumn() == 0) {
            return ['allowed' => false, 'message' => 'You must set your Workplace Location in your Profile before tracking attendance.'];
        }

        // Get student's instructor ID
        $stmt = $this->db->prepare("
            SELECT sec.instructor_id 
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN sections sec ON u.section_id = sec.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $instructorData = $stmt->fetch();
        $instructorId = $instructorData['instructor_id'] ?? null;

        // Check Pre-Required Documents (only for student's instructor OR system-wide)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM document_types 
            WHERE is_pre_required = 1 
            AND is_active = 1
            AND (instructor_id = :instructor_id OR instructor_id IS NULL)
        ");
        $stmt->execute([':instructor_id' => $instructorId]);
        $totalPreReqs = $stmt->fetchColumn();

        if ($totalPreReqs > 0) {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT dt.id)
                FROM document_submissions ds
                JOIN document_types dt ON ds.document_type_id = dt.id
                WHERE ds.student_id = :student_id 
                  AND dt.is_pre_required = 1 
                  AND dt.is_active = 1
                  AND (dt.instructor_id = :instructor_id OR dt.instructor_id IS NULL)
                  AND ds.status = 'approved'
            ");
            $stmt->execute([
                ':student_id' => $studentId,
                ':instructor_id' => $instructorId
            ]);
            $approvedPreReqs = $stmt->fetchColumn();

            if ($approvedPreReqs < $totalPreReqs) {
                return ['allowed' => false, 'message' => 'You must submit and have all Pre-Required Documents approved before tracking attendance.'];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Upload a document submission
     * @param int $userId
     * @param int $documentTypeId
     * @param array $file
     * @return array ['success' => bool, 'message' => string]
     */
    public function uploadDocument($userId, $documentTypeId, $file)
    {
        try {
            // Get student ID
            $stmt = $this->db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$userId]);
            $studentId = $stmt->fetchColumn();
            if (!$studentId)
                return ['success' => false, 'message' => 'Student not found'];

            // Validation
            $allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
            if (!in_array($file['type'], $allowed))
                return ['success' => false, 'message' => 'Invalid file type. URL: ' . $file['type']]; // Improved error
            if ($file['size'] > 10 * 1024 * 1024)
                return ['success' => false, 'message' => 'File too large (Max 10MB).'];

            // Upload
            $dir = __DIR__ . '/../../storage/uploads/student_docs/';
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'doc_' . $studentId . '_' . $documentTypeId . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                return ['success' => false, 'message' => 'Upload failed'];
            }

            $dbPath = '../../storage/uploads/student_docs/' . $filename;

            // Check if exists
            $check = $this->db->prepare("SELECT id FROM document_submissions WHERE student_id = ? AND document_type_id = ?");
            $check->execute([$studentId, $documentTypeId]);
            $exists = $check->fetchColumn();

            if ($exists) {
                // Update
                $stmt = $this->db->prepare("UPDATE document_submissions SET file_path = ?, status = 'pending', submitted_at = NOW() WHERE id = ?");
                $stmt->execute([$dbPath, $exists]);
            } else {
                // Insert
                $stmt = $this->db->prepare("INSERT INTO document_submissions (student_id, document_type_id, file_path, status, submitted_at) VALUES (?, ?, ?, 'pending', NOW())");
                $stmt->execute([$studentId, $documentTypeId, $dbPath]);
            }

            return ['success' => true, 'message' => 'Document submitted successfully'];

        } catch (\PDOException $e) {
            error_log('Upload document error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    public function recordAttendance($studentId, $blockType, $lat, $lng, $photoData)
    {
        // Set timezone to Philippine Time (Asia/Manila, UTC+8)
        $phpTimezone = new \DateTimeZone('Asia/Manila');
        $now = new \DateTime('now', $phpTimezone);
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('Y-m-d H:i:s');

        // 1. Get Student Workplace Coords and Schedule
        $stmt = $this->db->prepare("
            SELECT workplace_latitude, workplace_longitude, schedule_start_time, schedule_end_time
            FROM student_workplaces 
            WHERE student_id = ? AND is_active = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$studentId]);
        $workplace = $stmt->fetch();
        if (!$workplace) {
            return ['success' => false, 'message' => 'No workplace assigned.'];
        }

        // Check if schedule is set
        if (!$workplace['schedule_start_time'] || !$workplace['schedule_end_time']) {
            return ['success' => false, 'message' => 'Please set your working schedule in your profile before recording attendance.'];
        }

        // FIX: Check for any existing ongoing (uncompleted) attendance record
        // This prevents duplicate shifts when a student forgot to time out from a previous shift
        $stmtOngoing = $this->db->prepare("
            SELECT id, attendance_date, block_type, time_in
            FROM attendance_records
            WHERE student_id = ?
            AND time_out IS NULL
            AND status = 'ongoing'
            ORDER BY time_in DESC
            LIMIT 1
        ");
        $stmtOngoing->execute([$studentId]);
        $ongoingRecord = $stmtOngoing->fetch();

        if ($ongoingRecord) {
            // There's an uncompleted shift - determine if it has actually expired
            $ongoingTimeIn = new \DateTime($ongoingRecord['time_in'], $phpTimezone);
            $isCrossDay = $this->isCrossDayShift($workplace['schedule_start_time'], $workplace['schedule_end_time']);

            // Calculate when the ongoing shift's block should have ended
            $ongoingDate = $ongoingRecord['attendance_date'];
            if ($ongoingRecord['block_type'] === 'regular') {
                $blockEndDt = new \DateTime($ongoingDate . ' ' . $workplace['schedule_end_time'], $phpTimezone);
                if ($isCrossDay) {
                    $blockEndDt->modify('+1 day');
                }
            } else {
                // overtime
                $blockEndDt = new \DateTime($ongoingDate . ' ' . $workplace['schedule_end_time'], $phpTimezone);
                if ($isCrossDay) {
                    $blockEndDt->modify('+1 day');
                }
                $blockEndDt->modify('+4 hours');
            }

            if ($now < $blockEndDt) {
                // The ongoing shift hasn't expired yet - block the new time-in
                return [
                    'success' => false,
                    'message' => 'You still have an active shift from ' . $ongoingTimeIn->format('M j, g:i A') . '. Please time out first before starting a new shift.'
                ];
            }
            // If the ongoing shift has expired (forgotten timeout), allow the student to time in.
            // The expired record will remain for the Missing Timeouts flow where the student
            // can submit a request to get those hours validated by their instructor.
        }

        // Determine block type from schedule instead of client input
        $scheduleStart = new \DateTime($currentDate . ' ' . $workplace['schedule_start_time'], $phpTimezone);
        $scheduleEnd = new \DateTime($currentDate . ' ' . $workplace['schedule_end_time'], $phpTimezone);
        
        // Handle night shift: if end time is earlier than start time, it means end time is next day
        $isCrossDay = $this->isCrossDayShift($workplace['schedule_start_time'], $workplace['schedule_end_time']);
        if ($isCrossDay) {
            // If current time is in the morning portion of a cross-day shift (before end time),
            // the shift actually started yesterday, so adjust schedule start to yesterday
            $endHour = (int) explode(':', $workplace['schedule_end_time'])[0];
            $startHour = (int) explode(':', $workplace['schedule_start_time'])[0];
            $currentHour = (int) $now->format('H');
            
            if ($currentHour < $endHour || ($currentHour < $startHour && $currentHour < 12)) {
                // We're in the morning portion - shift started yesterday
                $scheduleStart->modify('-1 day');
                // scheduleEnd stays on current date (already correct)
            } else {
                // We're in the evening portion - shift ends tomorrow
                $scheduleEnd->modify('+1 day');
            }
        }
        
        $actualBlockType = ($now >= $scheduleEnd) ? 'overtime' : 'regular';

        // Validate the block type matches what the client sent (for safety)
        if ($blockType !== $actualBlockType) {
            $blockType = $actualBlockType; // Server-side determination takes priority
        }

        // Check if overtime window has ended (schedule_end + 4 hours)
        if ($blockType === 'overtime') {
            $overtimeEnd = clone $scheduleEnd;
            $overtimeEnd->modify('+4 hours');
            if ($now > $overtimeEnd) {
                return ['success' => false, 'message' => 'Overtime window has ended.'];
            }
        }

        // Block regular time-in if schedule has not started yet (strict - no early allowance)
        if ($blockType === 'regular') {
            if ($now < $scheduleStart) {
                return ['success' => false, 'message' => 'Your shift has not started yet. Please wait until your scheduled start time (' . $scheduleStart->format('g:i A') . ').'];
            }
            // Block regular time-in if schedule end has already passed
            if ($now >= $scheduleEnd) {
                return ['success' => false, 'message' => 'Your regular shift schedule has ended. You cannot time in after ' . $scheduleEnd->format('g:i A') . '.'];
            }
        }

        // 2. Calculate Distance (track but don't block)
        $distance = $this->calculateDistance($lat, $lng, $workplace['workplace_latitude'], $workplace['workplace_longitude']);

        // Track whether student is within the allowed radius (60m)
        $withinRadius = ($distance <= 60) ? 1 : 0;

        // 3. Save Image
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $data = substr($photoData, strpos($photoData, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                return ['success' => false, 'message' => 'Invalid image type'];
            }
            $data = base64_decode($data);
            if ($data === false) {
                return ['success' => false, 'message' => 'Base64 decode failed'];
            }
        } else {
            return ['success' => false, 'message' => 'Invalid image data'];
        }

        $dir = __DIR__ . '/../../storage/uploads/attendance_images/';
        if (!is_dir($dir))
            mkdir($dir, 0755, true);

        $filename = 'att_' . $studentId . '_' . $now->format('Ymd_His') . '.' . $type;
        file_put_contents($dir . $filename, $data);
        $photoPath = '../../storage/uploads/attendance_images/' . $filename;

        // 4. Determine the correct attendance_date (shift date, not calendar date)
        // For cross-day shifts in the morning portion, the shift date is yesterday
        $attendanceDate = $currentDate;
        if ($isCrossDay) {
            $currentHourNow = (int) $now->format('H');
            $endH = (int) explode(':', $workplace['schedule_end_time'])[0];
            $startH = (int) explode(':', $workplace['schedule_start_time'])[0];
            if ($currentHourNow < $endH || ($currentHourNow < $startH && $currentHourNow < 12)) {
                // Morning portion of cross-day shift - attendance belongs to yesterday
                $yesterdayDt = clone $now;
                $yesterdayDt->modify('-1 day');
                $attendanceDate = $yesterdayDt->format('Y-m-d');
            }
        }

        // Insert Record with explicit Philippine timezone values
        try {
            $stmt = $this->db->prepare("
                INSERT INTO attendance_records 
                (student_id, attendance_date, block_type, time_in, time_in_latitude, time_in_longitude, within_radius, photo_path, status)
                VALUES 
                (:sid, :date, :block, :time_in, :lat, :lng, :wr, :path, 'ongoing')
            ");
            $stmt->execute([
                ':sid' => $studentId,
                ':date' => $attendanceDate,
                ':block' => $blockType,
                ':time_in' => $currentTime,
                ':lat' => $lat,
                ':lng' => $lng,
                ':wr' => $withinRadius,
                ':path' => $photoPath
            ]);

            $msg = 'Time in recorded successfully';
            if ($distance > 40) {
                $msg .= ' (Note: You are ' . round($distance) . 'm away, which is within the extended 60m allowance)';
            }

            // Check if student is late (timed in after schedule start)
            $lateMinutes = 0;
            if ($blockType === 'regular') {
                // Use the already calculated scheduleStart which handles night shifts properly
                if ($now > $scheduleStart) {
                    $lateMinutes = (int) round(($now->getTimestamp() - $scheduleStart->getTimestamp()) / 60);
                }
            }

            return ['success' => true, 'message' => $msg, 'within_radius' => $withinRadius, 'late_minutes' => $lateMinutes];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => 'You have already timed in for this block today.'];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Record Time Out for a student
     * @param int $studentId
     * @param string $blockType
     * @return array ['success' => bool, 'message' => string, 'hours_worked' => float]
     */
    public function recordTimeOut($studentId, $blockType)
    {
        // CRITICAL FIX: Wrap entire operation in transaction to prevent race conditions
        $this->db->beginTransaction();

        try {
            // 1. Find the active ongoing attendance record for this block type
            // FIX: Search by ongoing status instead of CURDATE() only, so cross-day shifts are found
            $stmt = $this->db->prepare("
                SELECT id, time_in, time_out, status, block_type, attendance_date
                FROM attendance_records 
                WHERE student_id = ? 
                AND block_type = ?
                AND time_out IS NULL
                AND status = 'ongoing'
                ORDER BY time_in DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$studentId, $blockType]);
            $record = $stmt->fetch();

            if (!$record) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No active Time In record found for this block.'];
            }

            if ($record['time_out']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'You have already timed out for this block.'];
            }

            // 2. Get student schedule for dynamic block end times
            $scheduleStmt = $this->db->prepare("
                SELECT schedule_start_time, schedule_end_time
                FROM student_workplaces
                WHERE student_id = ? AND is_active = 1
                LIMIT 1
            ");
            $scheduleStmt->execute([$studentId]);
            $schedule = $scheduleStmt->fetch();

            // Determine block end DATETIME dynamically from schedule (not just time)
            $blockEndDateTime = null;
            if ($schedule && $schedule['schedule_end_time']) {
                $isCrossDay = $this->isCrossDayShift($schedule['schedule_start_time'], $schedule['schedule_end_time']);
                $attendanceDate = $record['attendance_date'];

                if ($blockType === 'regular') {
                    $blockEndDateTime = new \DateTime($attendanceDate . ' ' . $schedule['schedule_end_time'], new \DateTimeZone('Asia/Manila'));
                    // For cross-day shifts, the end time is on the next calendar day
                    if ($isCrossDay) {
                        $blockEndDateTime->modify('+1 day');
                    }
                } elseif ($blockType === 'overtime') {
                    // Overtime ends 4 hours after schedule end
                    $blockEndDateTime = new \DateTime($attendanceDate . ' ' . $schedule['schedule_end_time'], new \DateTimeZone('Asia/Manila'));
                    if ($isCrossDay) {
                        $blockEndDateTime->modify('+1 day');
                    }
                    $blockEndDateTime->modify('+4 hours');
                }
            }

            // Fallback for legacy block types (morning/afternoon/overtime)
            if (!$blockEndDateTime) {
                $legacyEndTimes = [
                    'morning' => '12:00:00',
                    'afternoon' => '18:00:00',
                    'overtime' => '22:00:00'
                ];
                $legacyEnd = $legacyEndTimes[$blockType] ?? null;
                if ($legacyEnd) {
                    $blockEndDateTime = new \DateTime($record['attendance_date'] . ' ' . $legacyEnd, new \DateTimeZone('Asia/Manila'));
                }
            }

            if (!$blockEndDateTime) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Invalid block type.'];
            }

            // Get current time in Asia/Manila timezone
            $currentDateTime = new \DateTime('now', new \DateTimeZone('Asia/Manila'));

            // FIX: Compare full datetimes instead of separate date/time strings
            // This correctly handles cross-day shifts
            if ($currentDateTime > $blockEndDateTime) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Block period has ended. You cannot time out after the block has ended. Please submit a missing timeout request in the "Missing Timeouts" page.'
                ];
            }

            // 3. CRITICAL FIX: Calculate hours with maximum cap to prevent overflow
            // Use explicit Philippine timezone for time_out
            $timeOutValue = $currentDateTime->format('Y-m-d H:i:s');

            // Update attendance record with time_out and calculate hours using MySQL
            $stmt = $this->db->prepare("
                UPDATE attendance_records 
                SET time_out = ?, 
                    status = 'completed',
                    hours = LEAST(TIMESTAMPDIFF(SECOND, time_in, ?) / 3600, 12.0)
                WHERE id = ?
            ");
            $stmt->execute([$timeOutValue, $timeOutValue, $record['id']]);

            // Get the calculated hours from the database
            $stmt = $this->db->prepare("
                SELECT hours 
                FROM attendance_records 
                WHERE id = ?
            ");
            $stmt->execute([$record['id']]);
            $updatedRecord = $stmt->fetch();
            $hoursWorked = round($updatedRecord['hours'], 2);

            // Apply double hours multiplier if this is a double hours date
            $isDoubleDay = $this->isDoubleHoursDate($record['attendance_date']);
            if ($isDoubleDay) {
                $hoursWorked = round($hoursWorked * 2, 2);
                // Update the record with doubled hours
                $stmt = $this->db->prepare("UPDATE attendance_records SET hours = ? WHERE id = ?");
                $stmt->execute([$hoursWorked, $record['id']]);
            }

            // CRITICAL FIX: Validate hours before proceeding
            $maxHours = $isDoubleDay ? 24 : 12;
            if ($hoursWorked > $maxHours) {
                $this->db->rollBack();
                error_log("CRITICAL: Hours overflow detected for student {$studentId}, block {$blockType}: {$hoursWorked} hours");
                return [
                    'success' => false,
                    'message' => "Invalid hours detected (maximum {$maxHours} hours per block). Please contact your instructor or administrator."
                ];
            }

            // Log warning for unusually high hours (but still valid)
            if ($hoursWorked > ($isDoubleDay ? 16 : 8)) {
                error_log("WARNING: High hours detected for student {$studentId}, block {$blockType}: {$hoursWorked} hours" . ($isDoubleDay ? ' (double hours day)' : ''));
            }

            // 4. Update ojt_summaries table with row lock to prevent race conditions
            $stmt = $this->db->prepare("
                SELECT id, hours_completed 
                FROM ojt_summaries 
                WHERE student_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$studentId]);
            $summary = $stmt->fetch();

            if ($summary) {
                // Update existing record
                $newTotal = $summary['hours_completed'] + $hoursWorked;

                // CRITICAL FIX: Validate total hours don't exceed reasonable maximum (1000 hours)
                if ($newTotal > 1000) {
                    $this->db->rollBack();
                    error_log("CRITICAL: Total hours overflow detected for student {$studentId}: {$newTotal} hours");
                    return [
                        'success' => false,
                        'message' => 'Total hours limit exceeded. Please contact your administrator.'
                    ];
                }

                $stmt = $this->db->prepare("
                    UPDATE ojt_summaries 
                    SET hours_completed = ?, last_updated = NOW()
                    WHERE student_id = ?
                ");
                $stmt->execute([$newTotal, $studentId]);
            } else {
                // Insert new record using INSERT ... ON DUPLICATE KEY UPDATE for safety
                $stmt = $this->db->prepare("
                    INSERT INTO ojt_summaries (student_id, hours_completed, last_updated)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        hours_completed = hours_completed + VALUES(hours_completed),
                        last_updated = NOW()
                ");
                $stmt->execute([$studentId, $hoursWorked]);
            }

            // CRITICAL FIX: Commit transaction only if all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Time Out recorded successfully. You worked for {$hoursWorked} hours.",
                'hours_worked' => $hoursWorked
            ];

        } catch (\PDOException $e) {
            // CRITICAL FIX: Rollback transaction on any error
            $this->db->rollBack();
            error_log('Record time out error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
        } catch (\Exception $e) {
            // Catch any other exceptions
            $this->db->rollBack();
            error_log('Unexpected error in recordTimeOut: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
    /**
     * Get today's attendance records for a student
     * FIX: Also fetches yesterday's ongoing (uncompleted) records for cross-day shifts,
     * and includes missing_timeout_flagged_at and request_status columns.
     * @param int $studentId
     * @return array
     */
    public function getTodayAttendance($studentId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT block_type, time_in, time_out, status, within_radius,
                       missing_timeout_flagged_at, request_status, attendance_date
                FROM attendance_records
                WHERE student_id = ?
                AND (
                    attendance_date = CURDATE()
                    OR (
                        attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                        AND time_out IS NULL
                        AND status IN ('ongoing', 'pending_exception')
                    )
                )
                ORDER BY attendance_date DESC, time_in DESC
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Get today attendance error: ' . $e->getMessage());
            return [];
        }
    }

    public function getMissingTimeouts($studentId)
    {
        try {
            // FIX: For cross-day shifts, a record from yesterday with end time in the morning
            // should only be shown as "missing" after that morning end time has passed.
            // We check: if schedule_end < schedule_start (cross-day), the effective end datetime
            // is attendance_date + 1 day + schedule_end_time.
            $stmt = $this->db->prepare("
                SELECT ar.*,
                       ar.attendance_date,
                       ar.time_in,
                       ar.block_type,
                       ar.forgot_timeout_reason  AS reason,
                       ar.forgot_timeout_file    AS letter_file_path,
                       ar.request_status         AS status,
                       ar.instructor_response,
                       sw.schedule_start_time,
                       sw.schedule_end_time
                FROM attendance_records ar
                LEFT JOIN student_workplaces sw
                       ON sw.student_id = ar.student_id
                      AND sw.is_active = 1
                WHERE ar.student_id = ?
                  AND ar.time_in   IS NOT NULL
                  AND ar.time_out  IS NULL
                  AND (
                        ar.request_status IS NOT NULL

                        OR (
                            CASE
                                WHEN sw.schedule_end_time < sw.schedule_start_time THEN
                                    NOW() > CONCAT(DATE_ADD(ar.attendance_date, INTERVAL 1 DAY), ' ', COALESCE(sw.schedule_end_time, '18:00:00'))
                                ELSE
                                    NOW() > CONCAT(ar.attendance_date, ' ', COALESCE(sw.schedule_end_time, '18:00:00'))
                            END
                        )
                  )
                ORDER BY ar.attendance_date DESC, ar.block_type ASC
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('getMissingTimeouts error: ' . $e->getMessage());
            return [];
        }
    }

    public function submitTimeoutRequest($studentId, $recordId, $reason, $file)
    {
        try {
            // Verify record belongs to student
            $stmt = $this->db->prepare("SELECT id FROM attendance_records WHERE id = ? AND student_id = ?");
            $stmt->execute([$recordId, $studentId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Record not found.'];
            }

            $filePath = null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                // Save to public/uploads/documents/
                $uploadDir = __DIR__ . '/../../public/uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'timeout_req_' . $recordId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Path relative to public/student/ (where the calling script is)
                    $filePath = '../uploads/documents/' . $filename;
                } else {
                    return ['success' => false, 'message' => 'Failed to upload file.'];
                }
            }

            $sql = "UPDATE attendance_records SET request_status = 'pending', forgot_timeout_reason = ?";
            $params = [$reason];

            if ($filePath) {
                $sql .= ", forgot_timeout_file = ?";
                $params[] = $filePath;
            }

            $sql .= " WHERE id = ?";
            $params[] = $recordId;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Request submitted successfully.'];

        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate hours for missing timeout based on block end time
     * @param int $recordId
     * @return array
     */
    public function calculateMissingTimeoutHours($recordId)
    {
        try {
            // Get the record
            $stmt = $this->db->prepare("
                SELECT id, time_in, block_type, attendance_date 
                FROM attendance_records 
                WHERE id = ?
            ");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch();

            if (!$record) {
                return ['success' => false, 'message' => 'Record not found.'];
            }

            // Define block end times
            $blockEndTimes = [
                'morning' => '12:00:00',
                'afternoon' => '18:00:00',
                'overtime' => '22:00:00'
            ];

            $blockType = $record['block_type'];
            if (!isset($blockEndTimes[$blockType])) {
                return ['success' => false, 'message' => 'Invalid block type.'];
            }

            // Construct the assumed time_out using attendance_date + block end time
            $assumedTimeOut = $record['attendance_date'] . ' ' . $blockEndTimes[$blockType];

            // Calculate hours using MySQL TIMESTAMPDIFF
            $stmt = $this->db->prepare("
                UPDATE attendance_records 
                SET time_out = ?,
                    hours = TIMESTAMPDIFF(SECOND, time_in, ?) / 3600,
                    status = 'completed'
                WHERE id = ?
            ");
            $stmt->execute([$assumedTimeOut, $assumedTimeOut, $recordId]);

            // Get the calculated hours
            $stmt = $this->db->prepare("SELECT hours FROM attendance_records WHERE id = ?");
            $stmt->execute([$recordId]);
            $updated = $stmt->fetch();
            $hoursWorked = round($updated['hours'], 2);

            // Apply double hours multiplier if applicable
            if ($this->isDoubleHoursDate($record['attendance_date'])) {
                $hoursWorked = round($hoursWorked * 2, 2);
                $stmt = $this->db->prepare("UPDATE attendance_records SET hours = ? WHERE id = ?");
                $stmt->execute([$hoursWorked, $recordId]);
            }

            return [
                'success' => true,
                'hours_worked' => $hoursWorked,
                'assumed_time_out' => $assumedTimeOut
            ];

        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Approve timeout request and calculate hours
     * @param int $recordId
     * @param string $instructorResponse
     * @return array
     */
    public function approveTimeoutRequest($recordId, $instructorResponse = '')
    {
        try {
            // Calculate hours based on block end time
            $result = $this->calculateMissingTimeoutHours($recordId);

            if (!$result['success']) {
                return $result;
            }

            $hoursWorked = $result['hours_worked'];

            // Get student_id for updating ojt_summaries
            $stmt = $this->db->prepare("SELECT student_id FROM attendance_records WHERE id = ?");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch();
            $studentId = $record['student_id'];

            // Update request status to approved
            $stmt = $this->db->prepare("
                UPDATE attendance_records 
                SET request_status = 'approved',
                    instructor_response = ?
                WHERE id = ?
            ");
            $stmt->execute([$instructorResponse ?: 'Approved. Hours calculated based on block end time.', $recordId]);

            // Update ojt_summaries
            $stmt = $this->db->prepare("
                SELECT id, hours_completed 
                FROM ojt_summaries 
                WHERE student_id = ?
            ");
            $stmt->execute([$studentId]);
            $summary = $stmt->fetch();

            if ($summary) {
                $newTotal = $summary['hours_completed'] + $hoursWorked;
                $stmt = $this->db->prepare("
                    UPDATE ojt_summaries 
                    SET hours_completed = ?, last_updated = NOW()
                    WHERE student_id = ?
                ");
                $stmt->execute([$newTotal, $studentId]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO ojt_summaries (student_id, hours_completed, last_updated)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$studentId, $hoursWorked]);
            }

            return [
                'success' => true,
                'message' => "Request approved. {$hoursWorked} hours added to student's total.",
                'hours_worked' => $hoursWorked
            ];

        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Reject timeout request
     * @param int $recordId
     * @param string $instructorResponse
     * @return array
     */
    public function rejectTimeoutRequest($recordId, $instructorResponse = '')
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE attendance_records 
                SET request_status = 'rejected',
                    instructor_response = ?
                WHERE id = ?
            ");
            $stmt->execute([$instructorResponse ?: 'Request rejected.', $recordId]);

            return ['success' => true, 'message' => 'Request rejected.'];

        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
