<?php

namespace App\Services;

use PDO;
use PDOException;

class ReportsService
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
     * Get student reports with OJT hours and workplace info
     * 
     * @param string $search Optional search term
     * @param int $page Page number (for pagination)
     * @param int $perPage Records per page
     * @param int|null $sectionId Optional section ID to filter by
     * @return array Result with 'students', 'total', 'total_pages', 'current_page'
     */
    public function getStudentReports($search = '', $page = 1, $perPage = 10, $sectionId = null)
    {
        $searchTerm = '%' . $search . '%';
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset = ($page - 1) * $perPage;

        // Build WHERE clause
        $whereConditions = [
            "u.role = 'student'",
            "u.is_archived = 0"
        ];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "(
                u.school_id LIKE :search1 OR 
                u.full_name LIKE :search2 OR 
                s.section_code LIKE :search3 OR
                s.section_name LIKE :search4
            )";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        if ($sectionId !== null && $sectionId > 0) {
            $whereConditions[] = "u.section_id = :section_id";
            $params[':section_id'] = $sectionId;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count
        $countSql = "
            SELECT COUNT(DISTINCT u.id) as total
            FROM users u
            LEFT JOIN sections s ON u.section_id = s.id
            WHERE {$whereClause}
        ";

        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Get students with OJT hours and workplace info
        $sql = "
            SELECT 
                u.id as user_id,
                u.school_id,
                u.full_name,
                u.profile_pic_path,
                s.id as section_id,
                s.section_code,
                s.section_name,
                COALESCE(ojt.hours_completed, 0) as total_hours,
                COALESCE(ojt.hours_required, st.target_ojt_hours, 600.00) as hours_required,
                wp.company_name,
                wp.company_head,
                wp.position_title,
                wp.start_date,
                wp.id as workplace_id
            FROM users u
            INNER JOIN students st ON u.id = st.user_id
            LEFT JOIN sections s ON u.section_id = s.id AND s.is_active = 1
            LEFT JOIN ojt_summaries ojt ON st.id = ojt.student_id
            LEFT JOIN student_workplaces wp ON st.id = wp.student_id AND wp.is_active = 1
            WHERE {$whereClause}
            ORDER BY u.full_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $students = $stmt->fetchAll();

        $totalPages = ceil($total / $perPage);

        return [
            'students' => $students,
            'total' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page
        ];
    }

    /**
     * Get student profile details by user ID
     * 
     * @param int $userId User ID
     * @return array|false Student profile or false if not found
     */
    public function getStudentProfile($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.school_id,
                u.full_name,
                u.email,
                u.contact,
                u.facebook_name,
                u.profile_pic_path,
                u.year,
                s.id as section_id,
                s.section_code,
                s.section_name,
                st.id as student_id,
                st.target_ojt_hours,
                COALESCE(ojt.hours_completed, 0) as total_hours,
                COALESCE(ojt.hours_required, st.target_ojt_hours, 600.00) as hours_required,
                COALESCE(ojt.manual_adjustment_hours, 0) as adjustment_hours,
                wp.company_name,
                wp.company_head,
                wp.position_title,
                wp.company_address,
                wp.supervisor_position,
                wp.head_trainee,
                wp.head_trainee_position,
                wp.head_trainee_contact,
                wp.head_trainee_email,
                wp.start_date,
                wp.end_date,
                wp.workplace_latitude,
                wp.workplace_longitude
            FROM users u
            INNER JOIN students st ON u.id = st.user_id
            LEFT JOIN sections s ON u.section_id = s.id AND s.is_active = 1
            LEFT JOIN ojt_summaries ojt ON st.id = ojt.student_id
            LEFT JOIN student_workplaces wp ON st.id = wp.student_id AND wp.is_active = 1
            WHERE u.id = :user_id
            AND u.role = 'student'
            AND u.is_archived = 0
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
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

