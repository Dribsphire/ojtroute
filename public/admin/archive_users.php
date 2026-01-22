<?php
/**
 * Archive Users by Year Handler
 * Handles archiving users by year from admin panel
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/UserService.php';

use App\Services\UserService;

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
    // Get POST data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    // Fallback to $_POST if JSON parsing fails
    if (!$data || !is_array($data)) {
        $data = $_POST;
    }

    // Validate student_ids is provided
    if (empty($data['student_ids']) || !is_array($data['student_ids'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Student IDs are required'
        ]);
        exit;
    }

    $studentIds = $data['student_ids'];

    // Archive students
    $userService = new UserService();
    $result = $userService->archiveStudentsByIds($studentIds);

    if ($result['success']) {
        echo json_encode($result);
    } else {
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


