<?php

namespace App\Services;

use PDO;
use PDOException;

class SectionService
{
    private $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $this->db = $this->connect($config);
    }

    private function connect($config)
    {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            return $pdo;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new \Exception('Database connection failed');
        }
    }

    /**
     * Get all sections with instructor info and student counts
     * 
     * @param string $search Optional search term
     * @return array List of sections
     */
    public function getSections($search = '')
    {
        $searchTerm = '%' . $search . '%';
        
        $sql = "
            SELECT 
                s.id,
                s.section_code,
                s.section_name,
                s.department,
                s.year,
                s.is_active,
                s.instructor_id,
                i.id as instructor_table_id,
                u.id as instructor_user_id,
                u.school_id as instructor_school_id,
                u.full_name as instructor_name,
                u.email as instructor_email,
                u.contact as instructor_contact,
                u.profile_pic_path as instructor_avatar,
                COUNT(DISTINCT st.id) as student_count
            FROM sections s
            LEFT JOIN instructors i ON s.instructor_id = i.id
            LEFT JOIN users u ON i.user_id = u.id
            LEFT JOIN users st ON st.section_id = s.id AND st.role = 'student' AND st.is_archived = 0
            WHERE s.is_active = 1
        ";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (
                s.section_code LIKE :search1 OR 
                s.section_name LIKE :search2 OR 
                u.full_name LIKE :search3
            )";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
        }

        $sql .= " GROUP BY s.id ORDER BY s.section_code ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single section by ID
     * 
     * @param int $sectionId Section ID
     * @return array|false Section data or false if not found
     */
    public function getSectionById($sectionId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                i.id as instructor_table_id,
                u.id as instructor_user_id,
                u.school_id as instructor_school_id,
                u.full_name as instructor_name,
                u.email as instructor_email,
                u.contact as instructor_contact,
                u.profile_pic_path as instructor_avatar
            FROM sections s
            LEFT JOIN instructors i ON s.instructor_id = i.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE s.id = :section_id
        ");
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->fetch();
    }

    /**
     * Add a new section
     * 
     * @param array $sectionData Section data (section_code, section_name, department, year)
     * @return array Result with 'success' and 'message'
     */
    public function addSection($sectionData)
    {
        try {
            // Validate required fields
            $required = ['section_code', 'section_name', 'department', 'year'];
            foreach ($required as $field) {
                if (empty($sectionData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: {$field}"
                    ];
                }
            }

            // Check if section already exists
            $stmt = $this->db->prepare("
                SELECT id FROM sections 
                WHERE section_code = :section_code 
                AND department = :department 
                AND year = :year
                LIMIT 1
            ");
            $stmt->execute([
                ':section_code' => trim($sectionData['section_code']),
                ':department' => trim($sectionData['department']),
                ':year' => trim($sectionData['year'])
            ]);

            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => "Section '{$sectionData['section_code']}' already exists for this department and year"
                ];
            }

            // Insert new section
            $stmt = $this->db->prepare("
                INSERT INTO sections (section_code, section_name, department, year, is_active)
                VALUES (:section_code, :section_name, :department, :year, 1)
            ");
            $stmt->execute([
                ':section_code' => trim($sectionData['section_code']),
                ':section_name' => trim($sectionData['section_name']),
                ':department' => trim($sectionData['department']),
                ':year' => trim($sectionData['year'])
            ]);

            $sectionId = $this->db->lastInsertId();

            return [
                'success' => true,
                'message' => "Section '{$sectionData['section_code']}' added successfully",
                'section_id' => $sectionId
            ];

        } catch (PDOException $e) {
            error_log('Add section error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign an instructor to a section
     * 
     * @param int $sectionId Section ID
     * @param int $instructorId Instructor ID (from instructors table)
     * @return array Result with 'success' and 'message'
     */
    public function assignInstructor($sectionId, $instructorId)
    {
        try {
            // Validate section exists
            $stmt = $this->db->prepare("SELECT id FROM sections WHERE id = :section_id LIMIT 1");
            $stmt->execute([':section_id' => $sectionId]);
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Section not found'
                ];
            }

            // If instructor_id is null or 0, unassign instructor
            if (empty($instructorId)) {
                $stmt = $this->db->prepare("
                    UPDATE sections 
                    SET instructor_id = NULL, updated_at = NOW()
                    WHERE id = :section_id
                ");
                $stmt->execute([':section_id' => $sectionId]);

                return [
                    'success' => true,
                    'message' => 'Instructor unassigned successfully'
                ];
            }

            // Validate instructor exists
            $stmt = $this->db->prepare("SELECT id FROM instructors WHERE id = :instructor_id LIMIT 1");
            $stmt->execute([':instructor_id' => $instructorId]);
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Instructor not found'
                ];
            }

            // Update section
            $stmt = $this->db->prepare("
                UPDATE sections 
                SET instructor_id = :instructor_id, updated_at = NOW()
                WHERE id = :section_id
            ");
            $stmt->execute([
                ':instructor_id' => $instructorId,
                ':section_id' => $sectionId
            ]);

            return [
                'success' => true,
                'message' => 'Instructor assigned successfully'
            ];

        } catch (PDOException $e) {
            error_log('Assign instructor error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all instructors for dropdown
     * 
     * @return array List of instructors
     */
    public function getInstructors()
    {
        $stmt = $this->db->prepare("
            SELECT 
                i.id as instructor_id,
                u.id as user_id,
                u.school_id,
                u.full_name,
                u.email,
                u.contact,
                u.profile_pic_path
            FROM instructors i
            INNER JOIN users u ON i.user_id = u.id
            WHERE u.role = 'instructor' 
            AND u.is_archived = 0
            ORDER BY u.full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get instructor profile details
     * 
     * @param int $instructorId Instructor ID (from instructors table)
     * @return array|false Instructor profile or false if not found
     */
    public function getInstructorProfile($instructorId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                i.id as instructor_id,
                u.id as user_id,
                u.school_id,
                u.full_name,
                u.email,
                u.contact,
                u.profile_pic_path,
                u.facebook_name,
                i.department,
                COUNT(DISTINCT s.id) as assigned_sections_count,
                GROUP_CONCAT(DISTINCT CONCAT(s.section_code, ' - ', s.section_name) SEPARATOR ', ') as assigned_sections,
                COUNT(DISTINCT st.id) as total_students
            FROM instructors i
            INNER JOIN users u ON i.user_id = u.id
            LEFT JOIN sections s ON s.instructor_id = i.id AND s.is_active = 1
            LEFT JOIN users st ON st.section_id = s.id AND st.role = 'student' AND st.is_archived = 0
            WHERE i.id = :instructor_id
            GROUP BY i.id, u.id
        ");
        $stmt->execute([':instructor_id' => $instructorId]);
        return $stmt->fetch();
    }

    /**
     * Get instructor profile by section ID
     * 
     * @param int $sectionId Section ID
     * @return array|false Instructor profile or false if not found
     */
    public function getInstructorBySection($sectionId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                i.id as instructor_id,
                u.id as user_id,
                u.school_id,
                u.full_name,
                u.email,
                u.contact,
                u.profile_pic_path,
                u.facebook_name,
                i.department,
                s.section_code,
                s.section_name,
                COUNT(DISTINCT st.id) as student_count
            FROM sections s
            INNER JOIN instructors i ON s.instructor_id = i.id
            INNER JOIN users u ON i.user_id = u.id
            LEFT JOIN users st ON st.section_id = s.id AND st.role = 'student' AND st.is_archived = 0
            WHERE s.id = :section_id
            GROUP BY i.id, u.id, s.id
        ");
        $stmt->execute([':section_id' => $sectionId]);
        return $stmt->fetch();
    }

    /**
     * Delete a section
     * 
     * @param int $sectionId Section ID
     * @return array Result with 'success' and 'message'
     */
    public function deleteSection($sectionId)
    {
        try {
            // Start transaction to ensure the update commits
            $this->db->beginTransaction();
            
            // First, verify section exists
            $stmt = $this->db->prepare("
                SELECT id, section_code, is_active 
                FROM sections 
                WHERE id = :section_id
                LIMIT 1
            ");
            $stmt->execute([':section_id' => $sectionId]);
            $section = $stmt->fetch();
            
            if (!$section) {
                $this->db->rollBack();
                error_log("Delete section: Section ID {$sectionId} not found");
                return [
                    'success' => false,
                    'message' => 'Section not found'
                ];
            }
            
            error_log("Delete section: Found section ID {$sectionId}, code: {$section['section_code']}, is_active: {$section['is_active']}");
            
            // Check if already deleted
            if ($section['is_active'] == 0) {
                $this->db->rollBack();
                error_log("Delete section: Section ID {$sectionId} is already deleted");
                return [
                    'success' => false,
                    'message' => 'Section is already deleted'
                ];
            }

            // Check if section has students
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE section_id = :section_id AND role = 'student' AND is_archived = 0
            ");
            $stmt->execute([':section_id' => $sectionId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $this->db->rollBack();
                error_log("Delete section: Section ID {$sectionId} has {$result['count']} students");
                return [
                    'success' => false,
                    'message' => "Cannot delete section: It has {$result['count']} active student(s). Please reassign or archive students first."
                ];
            }

            // Soft delete (set is_active = 0) instead of hard delete
            $stmt = $this->db->prepare("
                UPDATE sections 
                SET is_active = 0, updated_at = NOW()
                WHERE id = :section_id
            ");
            $stmt->execute([':section_id' => $sectionId]);
            
            // Check if update was successful
            $rowCount = $stmt->rowCount();
            error_log("Delete section: UPDATE executed, rowCount: {$rowCount}");
            
            if ($rowCount === 0) {
                $this->db->rollBack();
                error_log("Delete section: No rows updated for section ID {$sectionId}");
                return [
                    'success' => false,
                    'message' => 'Failed to delete section: No rows were updated'
                ];
            }

            // Verify the update actually happened (before commit)
            $stmt = $this->db->prepare("
                SELECT is_active 
                FROM sections 
                WHERE id = :section_id
                LIMIT 1
            ");
            $stmt->execute([':section_id' => $sectionId]);
            $verify = $stmt->fetch();
            
            if ($verify && $verify['is_active'] == 1) {
                $this->db->rollBack();
                error_log("Delete section: WARNING - Section ID {$sectionId} still has is_active = 1 after UPDATE");
                return [
                    'success' => false,
                    'message' => 'Failed to delete section: Update did not persist'
                ];
            }
            
            // Commit the transaction
            $this->db->commit();
            
            error_log("Delete section: Successfully deleted section ID {$sectionId}");

            return [
                'success' => true,
                'message' => 'Section deleted successfully'
            ];

        } catch (PDOException $e) {
            // Rollback on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Delete section error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get database connection (for use in other services)
     * 
     * @return PDO Database connection
     */
    public function getDb()
    {
        return $this->db;
    }
}

