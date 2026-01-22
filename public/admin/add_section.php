<?php
/**
 * Add Section Handler
 * Creates a new section
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/SectionService.php';

use App\Services\SectionService;

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

    // Validate required fields
    $required = ['section_code', 'section_name', 'department', 'year'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: {$field}"
            ]);
            exit;
        }
    }

    $sectionService = new SectionService();
    $result = $sectionService->addSection([
        'section_code' => trim($data['section_code']),
        'section_name' => trim($data['section_name']),
        'department' => trim($data['department']),
        'year' => trim($data['year'])
    ]);

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

