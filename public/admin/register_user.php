<?php
/**
 * Manual User Registration Handler
 * Handles single user registration from the admin panel
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
$required = ['school_id', 'full_name', 'email', 'role', 'gender', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Missing required field: {$field}"
        ]);
        exit;
    }
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate role
if (!in_array($data['role'], ['student', 'instructor', 'admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid role. Must be student, instructor, or admin'
    ]);
    exit;
}

// Validate gender
if (!in_array($data['gender'], ['male', 'female', 'non-binary'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid gender'
    ]);
    exit;
}

// Validate section for students
if ($data['role'] === 'student' && empty($data['section'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Section is required for students'
    ]);
    exit;
}

try {
    $userService = new UserService();
    
    // Prepare user data
    $userData = [
        'school_id' => trim($data['school_id']),
        'full_name' => trim($data['full_name']),
        'email' => trim($data['email']),
        'role' => $data['role'],
        'gender' => $data['gender'],
        'password' => $data['password'],
        'section' => !empty($data['section']) ? trim($data['section']) : null,
        'year' => !empty($data['year']) ? trim($data['year']) : '2025',
        'contact' => !empty($data['contact']) ? trim($data['contact']) : null,
        'facebook_name' => !empty($data['facebook_name']) ? trim($data['facebook_name']) : null,
    ];

    // Register user
    $result = $userService->registerUser($userData);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => "User '{$userData['school_id']}' registered successfully!",
            'user_id' => $result['user_id'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (\Exception $e) {
    error_log('User registration error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while registering the user. Please try again.'
    ]);
}

