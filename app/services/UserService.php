<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

class UserService
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
     * Get or create section by section name
     * 
     * @param string $sectionName Full section name (e.g., "BSIT-4A")
     * @param string $year Academic year
     * @return int Section ID
     */
    private function getOrCreateSection($sectionName, $year = '2025')
    {
        // Extract section code and department from section name
        // Example: "BSIT-4A" -> code: "4A", department: "College of Computer Studies"
        $sectionCode = '';
        $department = 'College of Computer Studies'; // Default

        if (preg_match('/([A-Z]+)-?(\d+[A-Z]?)$/i', $sectionName, $matches)) {
            $program = strtoupper($matches[1]);
            $sectionCode = $matches[2];

            // Map program codes to departments
            $departmentMap = [
                'BSIT' => 'College of Computer Studies',
                'BSIS' => 'College of Computer Studies',
                'BSED' => 'College of Education',
                'BSCE' => 'College of Engineering',
                'BSITE' => 'College of Industrial Technology',
            ];

            if (isset($departmentMap[$program])) {
                $department = $departmentMap[$program];
            }
        } else {
            // Fallback: try to extract from section name
            $sectionCode = preg_replace('/[^0-9A-Z]/i', '', $sectionName);
        }

        // Check if section exists
        $stmt = $this->db->prepare("
            SELECT id FROM sections 
            WHERE section_name = :section_name 
            AND year = :year 
            LIMIT 1
        ");
        $stmt->execute([
            ':section_name' => $sectionName,
            ':year' => $year
        ]);
        $section = $stmt->fetch();

        if ($section) {
            return $section['id'];
        }

        // Create new section
        $stmt = $this->db->prepare("
            INSERT INTO sections (section_code, section_name, department, year, is_active)
            VALUES (:section_code, :section_name, :department, :year, 1)
        ");
        $stmt->execute([
            ':section_code' => $sectionCode,
            ':section_name' => $sectionName,
            ':department' => $department,
            ':year' => $year
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Register a single user
     * 
     * @param array $userData User data array
     * @return array Result with 'success' and 'message'
     */
    public function registerUser($userData)
    {
        try {
            // Validate required fields
            $required = ['school_id', 'full_name', 'email', 'role', 'password'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: {$field}"
                    ];
                }
            }

            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE school_id = :school_id LIMIT 1");
            $stmt->execute([':school_id' => $userData['school_id']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => "User with School ID '{$userData['school_id']}' already exists"
                ];
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $userData['email']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => "Email '{$userData['email']}' already exists"
                ];
            }

            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);

            // Get section_id if section is provided and role is student
            $sectionId = null;
            if (!empty($userData['section']) && $userData['role'] === 'student') {
                $sectionId = $this->getOrCreateSection($userData['section'], $userData['year'] ?? '2025');
            }

            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    school_id, full_name, email, password_hash, role, gender,
                    section_id, contact, facebook_name, year, is_archived
                ) VALUES (
                    :school_id, :full_name, :email, :password_hash, :role, :gender,
                    :section_id, :contact, :facebook_name, :year, 0
                )
            ");

            $stmt->execute([
                ':school_id' => trim($userData['school_id']),
                ':full_name' => trim($userData['full_name']),
                ':email' => trim($userData['email']),
                ':password_hash' => $passwordHash,
                ':role' => $userData['role'],
                ':gender' => $userData['gender'] ?? null,
                ':section_id' => $sectionId,
                ':contact' => $userData['contact'] ?? null,
                ':facebook_name' => $userData['facebook_name'] ?? null,
                ':year' => $userData['year'] ?? '2025'
            ]);

            $userId = $this->db->lastInsertId();

            // If student, create student record
            if ($userData['role'] === 'student') {
                $stmt = $this->db->prepare("
                    INSERT INTO students (user_id, department, target_ojt_hours)
                    VALUES (:user_id, :department, 600.00)
                ");

                // Get department from section
                $department = 'College of Computer Studies'; // Default
                if ($sectionId) {
                    $stmtDept = $this->db->prepare("SELECT department FROM sections WHERE id = :id");
                    $stmtDept->execute([':id' => $sectionId]);
                    $sectionData = $stmtDept->fetch();
                    if ($sectionData) {
                        $department = $sectionData['department'];
                    }
                }

                $stmt->execute([
                    ':user_id' => $userId,
                    ':department' => $department
                ]);
            }

            // If instructor, create instructor record
            if ($userData['role'] === 'instructor') {
                $stmt = $this->db->prepare("
                    INSERT INTO instructors (user_id, department)
                    VALUES (:user_id, :department)
                ");

                $department = $userData['department'] ?? 'College of Computer Studies';
                $stmt->execute([
                    ':user_id' => $userId,
                    ':department' => $department
                ]);
            }

            return [
                'success' => true,
                'message' => "User '{$userData['school_id']}' registered successfully",
                'user_id' => $userId
            ];

        } catch (\PDOException $e) {
            error_log('User registration error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process CSV file and register users
     * 
     * @param string $csvFilePath Path to uploaded CSV file
     * @return array Result with 'success', 'total', 'successful', 'failed', 'errors'
     */
    public function registerUsersFromCSV($csvFilePath)
    {
        $results = [
            'success' => true,
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (!file_exists($csvFilePath)) {
            return [
                'success' => false,
                'message' => 'CSV file not found'
            ];
        }

        $handle = fopen($csvFilePath, 'r');
        if ($handle === false) {
            return [
                'success' => false,
                'message' => 'Could not open CSV file'
            ];
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'CSV file is empty or invalid'
            ];
        }

        // Store original headers for reference
        $originalHeaders = $headers;

        // Normalize headers (trim and lowercase)
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headers);

        // Map CSV columns to our expected format
        // Handle various possible column names
        $columnMap = [
            'school_id' => ['school_id', 'schoolid', 'id'],
            'full_name' => ['full_name', 'fullname', 'name', 'first_name'],
            'email' => ['email'],
            'section' => ['section'],
            'role' => ['role'],
            'gender' => ['gender', 'gender '], // Handle trailing space
            'contact' => ['contact', 'phone', 'contact_number'],
            'facebook_name' => ['facebook', 'facebook_name'],
            'password' => ['password'],
            'year' => ['year']
        ];

        $columnIndexes = [];
        foreach ($columnMap as $field => $possibleNames) {
            foreach ($possibleNames as $name) {
                $index = array_search($name, $headers);
                if ($index !== false) {
                    $columnIndexes[$field] = $index;
                    break;
                }
            }
        }

        // Handle duplicate password columns - find all password columns
        $passwordIndexes = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim($header));
            if ($normalized === 'password') {
                $passwordIndexes[] = $index;
            }
        }

        // If we have multiple password columns, read first data row to determine which is role vs password
        if (count($passwordIndexes) > 1) {
            $filePosition = ftell($handle);
            $firstDataRow = fgetcsv($handle);

            if ($firstDataRow !== false) {
                // Check each password column to see if it contains role values
                foreach ($passwordIndexes as $pwdIndex) {
                    if (isset($firstDataRow[$pwdIndex])) {
                        $value = strtolower(trim($firstDataRow[$pwdIndex]));
                        if (in_array($value, ['student', 'instructor', 'admin'])) {
                            // This is actually the role column
                            if (!isset($columnIndexes['role'])) {
                                $columnIndexes['role'] = $pwdIndex;
                            }
                        } else {
                            // This is the actual password column
                            $columnIndexes['password'] = $pwdIndex;
                        }
                    }
                }
                // Reset file pointer to start of data (after header)
                fseek($handle, $filePosition);
            } else {
                // No data row - use last password column as password
                $columnIndexes['password'] = end($passwordIndexes);
            }
        } else if (count($passwordIndexes) === 1) {
            // Single password column - use it as password
            $columnIndexes['password'] = $passwordIndexes[0];
        }

        // Validate required columns
        $required = ['school_id', 'full_name', 'email', 'password'];
        foreach ($required as $field) {
            if (!isset($columnIndexes[$field])) {
                fclose($handle);
                return [
                    'success' => false,
                    'message' => "Required column '{$field}' not found in CSV"
                ];
            }
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Process each row
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                $results['total']++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map CSV row to user data
                $userData = [];
                foreach ($columnIndexes as $field => $index) {
                    if (isset($row[$index]) && $row[$index] !== '') {
                        $userData[$field] = trim($row[$index]);
                    }
                }

                // If full_name is empty but first_name exists, use first_name
                if (empty($userData['full_name']) && isset($userData['first_name'])) {
                    $userData['full_name'] = $userData['first_name'];
                }

                // Handle case where role might be in a mislabeled column
                // Check if role value looks like a password (numeric/alphanumeric) vs actual role word
                if (isset($userData['role'])) {
                    $roleValue = strtolower(trim($userData['role']));
                    // If role value is not a valid role, it might be a password
                    if (!in_array($roleValue, ['student', 'instructor', 'admin'])) {
                        // This might be a password, check other columns for role
                        unset($userData['role']);
                    }
                }

                // If role is still empty, check all columns for role keywords
                if (empty($userData['role'])) {
                    foreach ($row as $index => $cell) {
                        $cell = strtolower(trim($cell));
                        if (in_array($cell, ['student', 'instructor', 'admin'])) {
                            $userData['role'] = $cell;
                            break;
                        }
                    }
                    // Default to student if still empty
                    if (empty($userData['role'])) {
                        $userData['role'] = 'student';
                    }
                }

                // Ensure password exists - if not, use school_id as default password
                if (empty($userData['password'])) {
                    $userData['password'] = $userData['school_id'] ?? 'default123';
                }

                // Set defaults (role already set above if needed)
                if (empty($userData['year'])) {
                    $userData['year'] = '2025'; // Default year
                }

                // Register user
                $result = $this->registerUser($userData);

                if ($result['success']) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNum,
                        'school_id' => $userData['school_id'] ?? 'N/A',
                        'message' => $result['message']
                    ];
                }
            }

            // Commit transaction
            $this->db->commit();
            fclose($handle);

        } catch (\Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            fclose($handle);

            return [
                'success' => false,
                'message' => 'Error processing CSV: ' . $e->getMessage(),
                'processed' => $results['total'],
                'successful' => $results['successful']
            ];
        }

        return $results;
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
     * Get all users with pagination, search, and filters
     * 
     * @param array $options Options array with:
     *   - page: Current page number (default: 1)
     *   - per_page: Records per page (default: 10)
     *   - search: Search term for school_id, full_name, or email
     *   - role: Filter by role (student, instructor, admin)
     *   - section: Filter by section name
     *   - year: Filter by year
     * @return array Result with 'users', 'total', 'total_pages', 'current_page'
     */
    public function getUsers($options = [])
    {
        $page = isset($options['page']) ? (int) $options['page'] : 1;
        $perPage = isset($options['per_page']) ? (int) $options['per_page'] : 10;
        $search = isset($options['search']) ? trim($options['search']) : '';
        $role = isset($options['role']) ? trim($options['role']) : '';
        $section = isset($options['section']) ? trim($options['section']) : '';
        $year = isset($options['year']) ? trim($options['year']) : '';

        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        // Build WHERE clause
        $whereConditions = ['u.is_archived = 0'];
        $params = [];

        if (!empty($search)) {
            $searchParam1 = ':search1';
            $searchParam2 = ':search2';
            $searchParam3 = ':search3';
            $whereConditions[] = "(u.school_id LIKE {$searchParam1} OR u.full_name LIKE {$searchParam2} OR u.email LIKE {$searchParam3})";
            $searchValue = '%' . $search . '%';
            $params[$searchParam1] = $searchValue;
            $params[$searchParam2] = $searchValue;
            $params[$searchParam3] = $searchValue;
        }

        if (!empty($role)) {
            $whereConditions[] = "u.role = :role";
            $params[':role'] = $role;
        }

        if (!empty($section)) {
            $whereConditions[] = "s.section_name LIKE :section";
            $params[':section'] = '%' . $section . '%';
        }

        if (!empty($year)) {
            $whereConditions[] = "u.year = :year";
            $params[':year'] = $year;
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

        // Get users with pagination
        // Note: LIMIT and OFFSET values are already validated as integers above
        // Using intval() for extra safety
        $perPage = intval($perPage);
        $offset = intval($offset);

        $sql = "
            SELECT 
                u.id,
                u.school_id,
                u.full_name,
                u.email,
                u.role,
                u.gender,
                u.contact,
                u.facebook_name,
                u.year,
                u.is_archived,
                s.id as section_id,
                s.section_name,
                s.section_code
            FROM users u
            LEFT JOIN sections s ON u.section_id = s.id
            WHERE {$whereClause}
            ORDER BY u.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);

        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $users = $stmt->fetchAll();

        $totalPages = ceil($total / $perPage);

        return [
            'users' => $users,
            'total' => $total,
            'total_pages' => $totalPages,
            'current_page' => $page
        ];
    }

    /**
     * Get all sections for filter dropdown
     * 
     * @return array List of sections
     */
    public function getSections()
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT section_name, section_code
            FROM sections
            WHERE is_active = 1
            ORDER BY section_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all years for filter dropdown
     * 
     * @return array List of years
     */
    public function getYears()
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT year
            FROM users
            WHERE year IS NOT NULL AND year != ''
            ORDER BY year DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Archive users by year
     * 
     * @param string $year Year to archive users from
     * @return array Result with 'success', 'message', and 'archived_count'
     */
    public function archiveUsersByYear($year)
    {
        try {
            // Validate year is not empty
            if (empty($year)) {
                return [
                    'success' => false,
                    'message' => 'Year is required'
                ];
            }

            // Start transaction
            $this->db->beginTransaction();

            // Count users to be archived (excluding admins and already archived users)
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM users
                WHERE year = :year
                AND role != 'admin'
                AND is_archived = 0
            ");
            $countStmt->execute([':year' => $year]);
            $countResult = $countStmt->fetch();
            $userCount = $countResult['count'];

            if ($userCount == 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "No users found for year {$year} that can be archived (excluding admins and already archived users)"
                ];
            }

            // Archive users (set is_archived = 1 and archived_at = NOW())
            // Exclude admin users - they should never be archived
            $stmt = $this->db->prepare("
                UPDATE users
                SET is_archived = 1,
                    archived_at = NOW(),
                    updated_at = NOW()
                WHERE year = :year
                AND role != 'admin'
                AND is_archived = 0
            ");

            $stmt->execute([':year' => $year]);

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Successfully archived {$userCount} user(s) from year {$year}",
                'archived_count' => $userCount
            ];

        } catch (\PDOException $e) {
            // Rollback on error
            $this->db->rollBack();
            error_log('Archive users error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get students for archive modal
     * 
     * @param string|null $year Optional year filter
     * @return array List of active students
     */
    public function getStudentsForArchive($year = null)
    {
        try {
            $sql = "
                SELECT 
                    u.id,
                    u.school_id,
                    u.full_name,
                    u.year,
                    s.section_name
                FROM users u
                LEFT JOIN sections s ON u.section_id = s.id
                WHERE u.role = 'student'
                AND u.is_archived = 0
            ";

            $params = [];

            if ($year !== null && $year !== '') {
                $sql .= " AND u.year = :year";
                $params[':year'] = $year;
            }

            $sql .= " ORDER BY u.full_name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            error_log('Get students for archive error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Archive students by IDs
     * 
     * @param array $studentIds Array of student user IDs
     * @return array Result with 'success', 'message', and 'archived_count'
     */
    public function archiveStudentsByIds($studentIds)
    {
        try {
            // Validate student IDs array
            if (empty($studentIds) || !is_array($studentIds)) {
                return [
                    'success' => false,
                    'message' => 'Student IDs are required'
                ];
            }

            // Start transaction
            $this->db->beginTransaction();

            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

            // Count students to be archived (only students, not archived)
            $countSql = "
                SELECT COUNT(*) as count
                FROM users
                WHERE id IN ($placeholders)
                AND role = 'student'
                AND is_archived = 0
            ";

            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($studentIds);
            $countResult = $countStmt->fetch();
            $studentCount = $countResult['count'];

            if ($studentCount == 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'No valid students found to archive'
                ];
            }

            // Archive students (set is_archived = 1 and archived_at = NOW())
            $archiveSql = "
                UPDATE users
                SET is_archived = 1,
                    archived_at = NOW(),
                    updated_at = NOW()
                WHERE id IN ($placeholders)
                AND role = 'student'
                AND is_archived = 0
            ";

            $archiveStmt = $this->db->prepare($archiveSql);
            $archiveStmt->execute($studentIds);

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Successfully archived {$studentCount} student(s)",
                'archived_count' => $studentCount
            ];

        } catch (\PDOException $e) {
            // Rollback on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Archive students error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get archived students with optional filters and pagination
     * 
     * @param string|null $year Optional year filter
     * @param string|null $search Optional search term
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @return array Result with 'students', 'total', 'total_pages', 'current_page'
     */
    public function getArchivedStudents($year = null, $search = null, $page = 1, $perPage = 20)
    {
        try {
            $page = max(1, $page);
            $offset = ($page - 1) * $perPage;

            // Build base query
            $whereConditions = ["u.role = 'student'", "u.is_archived = 1"];
            $params = [];

            if ($year !== null && $year !== '') {
                $whereConditions[] = "u.year = :year";
                $params[':year'] = $year;
            }

            if ($search !== null && $search !== '') {
                $whereConditions[] = "(u.school_id LIKE :search1 OR u.full_name LIKE :search2)";
                $searchValue = '%' . $search . '%';
                $params[':search1'] = $searchValue;
                $params[':search2'] = $searchValue;
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Get total count
            $countSql = "
                SELECT COUNT(*) as total
                FROM users u
                WHERE {$whereClause}
            ";

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            // Get paginated results
            $perPage = intval($perPage);
            $offset = intval($offset);

            $sql = "
                SELECT 
                    u.id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.year,
                    u.archived_at,
                    s.section_name
                FROM users u
                LEFT JOIN sections s ON u.section_id = s.id
                WHERE {$whereClause}
                ORDER BY u.archived_at DESC, u.full_name ASC
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll();

            $totalPages = ceil($total / $perPage);

            return [
                'students' => $students,
                'total' => $total,
                'total_pages' => $totalPages,
                'current_page' => $page
            ];

        } catch (\PDOException $e) {
            error_log('Get archived students error: ' . $e->getMessage());
            return [
                'students' => [],
                'total' => 0,
                'total_pages' => 0,
                'current_page' => 1
            ];
        }
    }

    /**
     * Get all years that have archived students
     * 
     * @return array List of years
     */
    public function getArchivedYears()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT year
                FROM users
                WHERE role = 'student'
                AND is_archived = 1
                AND year IS NOT NULL 
                AND year != ''
                ORDER BY year DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (\PDOException $e) {
            error_log('Get archived years error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Restore archived student
     * 
     * @param int $studentId Student user ID
     * @return array Result with 'success' and 'message'
     */
    public function restoreStudent($studentId)
    {
        try {
            // Validate student exists and is archived
            $stmt = $this->db->prepare("
                SELECT id, school_id, role, is_archived 
                FROM users 
                WHERE id = :student_id 
                LIMIT 1
            ");
            $stmt->execute([':student_id' => $studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                return [
                    'success' => false,
                    'message' => 'Student not found'
                ];
            }

            if ($student['role'] !== 'student') {
                return [
                    'success' => false,
                    'message' => 'Only students can be restored'
                ];
            }

            if ($student['is_archived'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Student is not archived'
                ];
            }

            // Restore student
            $stmt = $this->db->prepare("
                UPDATE users
                SET is_archived = 0,
                    archived_at = NULL,
                    updated_at = NOW()
                WHERE id = :student_id
            ");

            $stmt->execute([':student_id' => $studentId]);

            return [
                'success' => true,
                'message' => "Student '{$student['school_id']}' restored successfully"
            ];

        } catch (\PDOException $e) {
            error_log('Restore student error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $newPassword New password (plain text)
     * @return array Result with 'success' and 'message'
     */
    public function updatePassword($userId, $newPassword)
    {
        try {
            // Validate user exists
            $stmt = $this->db->prepare("SELECT id, school_id FROM users WHERE id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Validate password is not empty
            if (empty($newPassword)) {
                return [
                    'success' => false,
                    'message' => 'Password cannot be empty'
                ];
            }

            // Hash the new password
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update password
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = :password_hash, 
                    updated_at = NOW()
                WHERE id = :user_id
            ");

            $stmt->execute([
                ':password_hash' => $passwordHash,
                ':user_id' => $userId
            ]);

            return [
                'success' => true,
                'message' => "Password updated successfully for user '{$user['school_id']}'"
            ];

        } catch (\PDOException $e) {
            error_log('Password update error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete user
     * 
     * @param int $userId User ID
     * @return array Result with 'success' and 'message'
     */
    public function deleteUser($userId)
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get user info
            $stmt = $this->db->prepare("SELECT id, school_id, role FROM users WHERE id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            $schoolId = $user['school_id'];

            // Delete related records based on role
            if ($user['role'] === 'student') {
                // Delete student record
                $stmt = $this->db->prepare("DELETE FROM students WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
            } elseif ($user['role'] === 'instructor') {
                // Delete instructor record
                $stmt = $this->db->prepare("DELETE FROM instructors WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $userId]);
            }

            // Delete user record
            // Note: Foreign key constraints should handle related records
            // If there are issues, we may need to delete related records first
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => "User '{$schoolId}' deleted successfully"
            ];

        } catch (\PDOException $e) {
            // Rollback on error
            $this->db->rollBack();
            error_log('User deletion error: ' . $e->getMessage());

            // Check for foreign key constraint errors
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete user: User has related records (documents, attendance, etc.). Please archive the user instead.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

