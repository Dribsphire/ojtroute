<?php
/**
 * Get Instructor Profile Handler
 * Returns instructor profile details by section ID or instructor ID
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
    $instructor = null;
    
    if (isset($_GET['section_id'])) {
        $sectionId = (int)$_GET['section_id'];
        $instructor = $sectionService->getInstructorBySection($sectionId);
    } elseif (isset($_GET['instructor_id'])) {
        $instructorId = (int)$_GET['instructor_id'];
        $instructor = $sectionService->getInstructorProfile($instructorId);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Either section_id or instructor_id is required'
        ]);
        exit;
    }
    
    if ($instructor) {
        echo json_encode([
            'success' => true,
            'instructor' => $instructor
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Instructor not found'
        ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

