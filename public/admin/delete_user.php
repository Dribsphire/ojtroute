<?php
/**
 * Delete User Handler
 * Handles user deletion from the admin panel
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
if (empty($data['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required field: user_id'
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

try {
    $userService = new UserService();
    
    // Delete user
    $result = $userService->deleteUser($userId);

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
    error_log('User deletion error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the user. Please try again.'
    ]);
}

