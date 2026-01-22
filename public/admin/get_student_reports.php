<?php
/**
 * Get Student Reports Handler
 * Returns list of students with OJT hours and workplace info
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/ReportsService.php';

use App\Services\ReportsService;

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
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sectionId = isset($_GET['section_id']) ? (int) $_GET['section_id'] : null;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 10;

    $reportsService = new ReportsService();
    $result = $reportsService->getStudentReports($search, $page, $perPage, $sectionId);

    echo json_encode([
        'success' => true,
        'students' => $result['students'],
        'total' => $result['total'],
        'total_pages' => $result['total_pages'],
        'current_page' => $result['current_page']
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

