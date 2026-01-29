<?php
/**
 * Get Student Profile Handler
 * Returns detailed student profile information
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
    if (empty($_GET['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }

    $userId = (int) $_GET['user_id'];

    $reportsService = new ReportsService();
    $profile = $reportsService->getStudentProfile($userId);

    if ($profile) {
        // Get document checklist
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

        $documentStatuses = getDocumentStatuses($pdo, $profile['student_id']);

        echo json_encode([
            'success' => true,
            'student' => $profile,
            'documents' => $documentStatuses
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Get document submission statuses for a student
 * (Reused from export_reports_csv.php)
 */
/**
 * Get document submission statuses for a student
 * Fetches dynamic pre-required documents from document_types table
 */
function getDocumentStatuses($pdo, $studentId)
{
    // 1. Fetch the student's instructor ID first to filter relevant documents
    $instSql = "
        SELECT s.instructor_id 
        FROM students st
        JOIN users u ON st.user_id = u.id
        JOIN sections s ON u.section_id = s.id
        WHERE st.id = :student_id
    ";
    $instStmt = $pdo->prepare($instSql);
    $instStmt->execute([':student_id' => $studentId]);
    $instructorId = $instStmt->fetchColumn();

    // 2. Fetch all pre-required document types for this instructor (or global)
    $docTypesSql = "
        SELECT id, name
        FROM document_types
        WHERE is_active = 1 
        AND is_pre_required = 1
        AND (instructor_id = :inst_id OR instructor_id IS NULL)
        ORDER BY name ASC
    ";
    $docTypesStmt = $pdo->prepare($docTypesSql);
    $docTypesStmt->execute([':inst_id' => $instructorId]);
    $preReqDocs = $docTypesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize statuses array with dynamic document names
    $statuses = [];
    foreach ($preReqDocs as $doc) {
        $statuses[$doc['name']] = false; // Default to incomplete
    }

    if (empty($preReqDocs)) {
        return [];
    }

    // 3. Get actual submissions for these specific document types
    $subSql = "
        SELECT 
            dt.name,
            ds.status,
            ds.submitted_at
        FROM document_submissions ds
        INNER JOIN document_types dt ON ds.document_type_id = dt.id
        WHERE ds.student_id = :student_id
        AND dt.is_pre_required = 1
    ";
    $subStmt = $pdo->prepare($subSql);
    $subStmt->execute([':student_id' => $studentId]);
    $submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Map submissions to the requirements
    foreach ($submissions as $sub) {
        $docName = $sub['name'];

        // Only process if this is one of our required docs
        if (array_key_exists($docName, $statuses)) {
            $status = $sub['status'];

            // Logic: Mark as 'done' (true) if Approved or Pending
            // Note: User can refine this if they want to distinguish pending vs approved in the UI
            if ($status === 'approved') {
                $statuses[$docName] = 'approved';
            } elseif ($status === 'pending') {
                $statuses[$docName] = 'pending';
            }
            // If rejected/revise, it remains false (incomplete)
        }
    }

    return $statuses;
}
