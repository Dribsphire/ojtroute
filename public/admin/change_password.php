<?php
/**
 * Change Password Handler
 * Handles password changes for users from the admin panel
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/UserService.php';

use App\Services\UserService;

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Validate required fields
if (empty($data['user_id']) || empty($data['new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: user_id and new_password'
    ]);
    exit;
}

// Validate user_id is numeric
$userId = (int)$data['user_id'];
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

// Validate password length (minimum 6 characters)
if (strlen($data['new_password']) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long'
    ]);
    exit;
}

try {
    $userService = new UserService();
    
    // Update password
    $result = $userService->updatePassword($userId, $data['new_password']);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (\Exception $e) {
    error_log('Password change error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while changing the password. Please try again.'
    ]);
}

