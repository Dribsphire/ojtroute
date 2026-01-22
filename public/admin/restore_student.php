<?php
/**
 * Restore Student Handler
 * Handles restoring archived students from admin panel
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

    // Validate student_id is provided
    if (empty($data['student_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Student ID is required'
        ]);
        exit;
    }

    $studentId = (int) $data['student_id'];

    // Restore student
    $userService = new UserService();
    $result = $userService->restoreStudent($studentId);

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
