<?php
/**
 * Get Instructors Handler
 * Returns list of instructors for dropdown
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/SectionService.php';

use App\Services\SectionService;

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
    $sectionService = new SectionService();
    $instructors = $sectionService->getInstructors();
    
    echo json_encode([
        'success' => true,
        'instructors' => $instructors
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

