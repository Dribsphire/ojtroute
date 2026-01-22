<?php

namespace App\Services;

use PDO;
use PDOException;

class ProfileService
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
     * Get admin profile by user ID
     * 
     * @param int $userId User ID
     * @return array|false Admin profile or false if not found
     */
    public function getAdminProfile($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                school_id,
                full_name,
                email,
                role,
                gender,
                contact,
                facebook_name,
                profile_pic_path,
                created_at
            FROM users
            WHERE id = :user_id
            AND role = 'admin'
            AND is_archived = 0
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Update admin profile
     * 
     * @param int $userId User ID
     * @param array $profileData Profile data (full_name, email, gender, contact, facebook_name)
     * @return array Result with 'success' and 'message'
     */
    public function updateAdminProfile($userId, $profileData)
    {
        try {
            // Verify user exists and is admin
            $stmt = $this->db->prepare("
                SELECT id, school_id FROM users 
                WHERE id = :user_id 
                AND role = 'admin' 
                AND is_archived = 0
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Admin user not found'
                ];
            }

            // Check if email is being changed and if it's already taken
            if (!empty($profileData['email'])) {
                $stmt = $this->db->prepare("
                    SELECT id FROM users 
                    WHERE email = :email 
                    AND id != :user_id
                    LIMIT 1
                ");
                $stmt->execute([
                    ':email' => trim($profileData['email']),
                    ':user_id' => $userId
                ]);
                if ($stmt->fetch()) {
                    return [
                        'success' => false,
                        'message' => 'Email address is already in use by another user'
                    ];
                }
            }

            // Build UPDATE query dynamically
            $updateFields = [];
            $params = [':user_id' => $userId];

            if (isset($profileData['full_name'])) {
                $updateFields[] = "full_name = :full_name";
                $params[':full_name'] = trim($profileData['full_name']);
            }

            if (isset($profileData['email'])) {
                $updateFields[] = "email = :email";
                $params[':email'] = trim($profileData['email']);
            }

            if (isset($profileData['gender'])) {
                $updateFields[] = "gender = :gender";
                $params[':gender'] = $profileData['gender'];
            }

            if (isset($profileData['contact'])) {
                $updateFields[] = "contact = :contact";
                $params[':contact'] = trim($profileData['contact']) ?: null;
            }

            if (isset($profileData['facebook_name'])) {
                $updateFields[] = "facebook_name = :facebook_name";
                $params[':facebook_name'] = trim($profileData['facebook_name']) ?: null;
            }

            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'No fields to update'
                ];
            }

            $updateFields[] = "updated_at = NOW()";

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];

        } catch (PDOException $e) {
            error_log('Update profile error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update profile picture path
     * 
     * @param int $userId User ID
     * @param string $profilePicPath Path to profile picture
     * @return array Result with 'success' and 'message'
     */
    public function updateProfilePicture($userId, $profilePicPath)
    {
        try {
            // Verify user exists and is admin
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE id = :user_id 
                AND role = 'admin' 
                AND is_archived = 0
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Admin user not found'
                ];
            }

            // Delete old profile picture if it exists
            $stmt = $this->db->prepare("
                SELECT profile_pic_path FROM users 
                WHERE id = :user_id 
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $oldUser = $stmt->fetch();
            
            if ($oldUser && !empty($oldUser['profile_pic_path'])) {
                $oldPath = __DIR__ . '/../../' . $oldUser['profile_pic_path'];
                if (file_exists($oldPath) && $oldPath !== __DIR__ . '/../../' . $profilePicPath) {
                    @unlink($oldPath);
                }
            }

            // Update profile picture path
            $stmt = $this->db->prepare("
                UPDATE users 
                SET profile_pic_path = :profile_pic_path, updated_at = NOW()
                WHERE id = :user_id
            ");
            $stmt->execute([
                ':profile_pic_path' => $profilePicPath,
                ':user_id' => $userId
            ]);

            return [
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'profile_pic_path' => $profilePicPath
            ];

        } catch (PDOException $e) {
            error_log('Update profile picture error: ' . $e->getMessage());
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

