<?php
/**
 * Get Students for Archive
 * Returns list of active students (not archived) for the archive modal
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/UserService.php';

use App\Services\UserService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $userService = new UserService();

    // Get year filter if provided
    $year = isset($_GET['year']) ? trim($_GET['year']) : null;

    // Get students (only active, not archived)
    $students = $userService->getStudentsForArchive($year);

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
