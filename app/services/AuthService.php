<?php

namespace App\Services;

require_once __DIR__ . '/../../vendor/autoload.php';

class AuthService
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
     * Authenticate admin user
     * 
     * @param string $schoolId School ID / Username
     * @param string $password Plain text password
     * @return array Returns array with 'success' (bool), 'user' (array|null), and 'error' (string) keys
     */
    public function authenticateAdmin($schoolId, $password)
    {
        try {
            // First check if user exists with this school_id and is admin
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    school_id,
                    full_name,
                    email,
                    password_hash,
                    role,
                    section_id,
                    contact,
                    facebook_name,
                    year,
                    profile_pic_path,
                    is_archived
                FROM users 
                WHERE LOWER(school_id) = LOWER(:school_id)
                AND role = 'admin'
                AND is_archived = 0
                LIMIT 1
            ");

            $stmt->execute([':school_id' => $schoolId]);
            $user = $stmt->fetch();

            // User doesn't exist or is not an admin
            if (!$user) {
                // Check if user exists but is not admin
                $stmtCheck = $this->db->prepare("
                    SELECT id, role FROM users 
                    WHERE LOWER(school_id) = LOWER(:school_id)
                    LIMIT 1
                ");
                $stmtCheck->execute([':school_id' => $schoolId]);
                $userExists = $stmtCheck->fetch();

                if ($userExists) {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_admin' // User exists but not admin
                    ];
                } else {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_found' // User doesn't exist
                    ];
                }
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                // Enhanced error logging for debugging
                error_log("Authentication failed for admin: {$schoolId}");
                error_log("Password hash length: " . strlen($user['password_hash']));
                error_log("Hash format valid: " . (preg_match('/^\$2[ayb]\$.{56}$/', $user['password_hash']) ? 'yes' : 'no'));

                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'wrong_password' // Password is incorrect
                ];
            }

            // Remove password hash from returned data
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'error' => null
            ];
        } catch (\PDOException $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return [
                'success' => false,
                'user' => null,
                'error' => 'database_error'
            ];
        }
    }

    /**
     * Authenticate student user
     * 
     * @param string $schoolId School ID / Username
     * @param string $password Plain text password
     * @return array Returns array with 'success' (bool), 'user' (array|null), and 'error' (string) keys
     */
    public function authenticateStudent($schoolId, $password)
    {
        try {
            // First check if user exists with this school_id and is student
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.password_hash,
                    u.role,
                    u.section_id,
                    u.contact,
                    u.facebook_name,
                    u.year,
                    u.profile_pic_path,
                    u.is_archived,
                    s.department,
                    s.target_ojt_hours
                FROM users u
                LEFT JOIN students s ON u.id = s.user_id
                WHERE LOWER(u.school_id) = LOWER(:school_id)
                AND u.role = 'student'
                AND u.is_archived = 0
                LIMIT 1
            ");

            $stmt->execute([':school_id' => $schoolId]);
            $user = $stmt->fetch();

            // User doesn't exist or is not a student
            if (!$user) {
                // Check if user exists but is not student
                $stmtCheck = $this->db->prepare("
                    SELECT id, role FROM users 
                    WHERE LOWER(school_id) = LOWER(:school_id)
                    LIMIT 1
                ");
                $stmtCheck->execute([':school_id' => $schoolId]);
                $userExists = $stmtCheck->fetch();

                if ($userExists) {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_student' // User exists but not student
                    ];
                } else {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_found' // User doesn't exist
                    ];
                }
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                // Enhanced error logging for debugging
                error_log("Authentication failed for student: {$schoolId}");
                error_log("Password hash length: " . strlen($user['password_hash']));
                error_log("Hash format valid: " . (preg_match('/^\$2[ayb]\$.{56}$/', $user['password_hash']) ? 'yes' : 'no'));

                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'wrong_password' // Password is incorrect
                ];
            }

            // Remove password hash from returned data
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'error' => null
            ];
        } catch (\PDOException $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return [
                'success' => false,
                'user' => null,
                'error' => 'database_error'
            ];
        }
    }

    /**
     * Authenticate instructor user
     * 
     * @param string $schoolId School ID / Username
     * @param string $password Plain text password
     * @return array Returns array with 'success' (bool), 'user' (array|null), and 'error' (string) keys
     */
    public function authenticateInstructor($schoolId, $password)
    {
        try {
            // First check if user exists with this school_id and is instructor
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.school_id,
                    u.full_name,
                    u.email,
                    u.password_hash,
                    u.role,
                    u.section_id,
                    u.contact,
                    u.facebook_name,
                    u.year,
                    u.profile_pic_path,
                    u.is_archived,
                    i.id as instructor_id,
                    i.department
                FROM users u
                LEFT JOIN instructors i ON u.id = i.user_id
                WHERE LOWER(u.school_id) = LOWER(:school_id)
                AND u.role = 'instructor'
                AND u.is_archived = 0
                LIMIT 1
            ");

            $stmt->execute([':school_id' => $schoolId]);
            $user = $stmt->fetch();

            // User doesn't exist or is not an instructor
            if (!$user) {
                // Check if user exists but is not instructor
                $stmtCheck = $this->db->prepare("
                    SELECT id, role FROM users 
                    WHERE LOWER(school_id) = LOWER(:school_id)
                    LIMIT 1
                ");
                $stmtCheck->execute([':school_id' => $schoolId]);
                $userExists = $stmtCheck->fetch();

                if ($userExists) {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_instructor' // User exists but not instructor
                    ];
                } else {
                    return [
                        'success' => false,
                        'user' => null,
                        'error' => 'not_found' // User doesn't exist
                    ];
                }
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                // Enhanced error logging for debugging
                error_log("Authentication failed for instructor: {$schoolId}");
                error_log("Password hash length: " . strlen($user['password_hash']));
                error_log("Hash format valid: " . (preg_match('/^\$2[ayb]\$.{56}$/', $user['password_hash']) ? 'yes' : 'no'));

                return [
                    'success' => false,
                    'user' => null,
                    'error' => 'wrong_password' // Password is incorrect
                ];
            }

            // Remove password hash from returned data
            unset($user['password_hash']);

            return [
                'success' => true,
                'user' => $user,
                'error' => null
            ];
        } catch (\PDOException $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return [
                'success' => false,
                'user' => null,
                'error' => 'database_error'
            ];
        }
    }

    /**
     * Check if instructor is assigned to a section
     * 
     * @param int $instructorId Instructor ID from instructors table
     * @return bool True if assigned to at least one section, false otherwise
     */
    public function isInstructorAssignedToSection($instructorId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM sections 
                WHERE instructor_id = :instructor_id 
                AND is_active = 1
            ");
            $stmt->execute([':instructor_id' => $instructorId]);
            $result = $stmt->fetch();

            return isset($result['count']) && $result['count'] > 0;
        } catch (\PDOException $e) {
            error_log('Check instructor assignment error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set user session
     * 
     * @param array $user User data array
     */
    public function setSession($user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['school_id'] = $user['school_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_authenticated'] = true;

        // Add role-specific data
        if (isset($user['section_id'])) {
            $_SESSION['section_id'] = $user['section_id'];
        }
        if (isset($user['department'])) {
            $_SESSION['department'] = $user['department'];
        }
        if (isset($user['profile_pic_path'])) {
            $_SESSION['profile_pic_path'] = $user['profile_pic_path'];
        }
        if (isset($user['instructor_id'])) {
            $_SESSION['instructor_id'] = $user['instructor_id'];
        }
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['is_authenticated']) && $_SESSION['is_authenticated'] === true;
    }

    /**
     * Check if user has admin role
     * 
     * @return bool
     */
    public function isAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Destroy session and logout
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all session variables
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();

        // Regenerate session ID to prevent session fixation
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Require admin authentication - redirects to login if not authenticated
     * 
     * @param string $redirectUrl URL to redirect to if not authenticated
     */
    public function requireAdmin($redirectUrl = '../admin_login.php')
    {
        if (!$this->isAuthenticated() || !$this->isAdmin()) {
            $this->logout();
            header('Location: ' . $redirectUrl);
            exit();
        }
    }

    /**
     * Set no-cache headers to prevent browser back button access
     */
    public function setNoCacheHeaders()
    {
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * Get database connection (for other services)
     * 
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }
}

