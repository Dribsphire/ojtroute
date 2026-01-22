<?php
/**
 * Upload Profile Picture Handler
 * Handles profile picture uploads for admin users
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/ProfileService.php';

use App\Services\ProfileService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get current user ID from session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Check if file was uploaded
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded or upload error occurred'
        ]);
        exit;
    }

    $file = $_FILES['profile_picture'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, and GIF images are allowed.'
        ]);
        exit;
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds 5MB limit'
        ]);
        exit;
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../storage/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'admin_' . $userId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save uploaded file'
        ]);
        exit;
    }

    // Relative path for database storage
    $relativePath = 'storage/uploads/profiles/' . $filename;

    // Update database
    $profileService = new ProfileService();
    $result = $profileService->updateProfilePicture($userId, $relativePath);

    if ($result['success']) {
        // Update session
        $_SESSION['profile_pic_path'] = $relativePath;

        echo json_encode([
            'success' => true,
            'message' => 'Profile picture uploaded successfully',
            'profile_pic_path' => $relativePath
        ]);
    } else {
        // Delete uploaded file if database update failed
        @unlink($filePath);
        
        http_response_code(400);
        echo json_encode($result);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

