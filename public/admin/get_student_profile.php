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
function getDocumentStatuses($pdo, $studentId)
{
    $statuses = [
        'MOA' => '',
        'Internship Agreement' => '',
        'parents consent' => '',
        'Endorsement' => '',
        'pledge of good conduct' => '',
        'resume' => '',
        'application letter' => '',
        'medical certificate' => '',
        'weekly report' => ''
    ];

    // Map document names to check
    $documentMap = [
        'MOA' => ['Memorandum of Agreement (MOA)', 'MOA'],
        'Internship Agreement' => ['Internship Agreement'],
        'parents consent' => ['Parent Consent Form', 'parents consent', 'parental consent'],
        'Endorsement' => ['Endorsement Letter', 'Endorsement'],
        'pledge of good conduct' => ['Pledge of Good Conduct', 'pledge of good conduct'],
        'resume' => ['Resume/Curriculum Vitae', 'Resume', 'CV', 'resume'],
        'application letter' => ['Application Letter', 'application letter'],
        'medical certificate' => ['Medical Certificate', 'medical certificate', 'Medical Certificate (Excuse)'],
        'weekly report' => ['Weekly OJT Report', 'weekly report', 'Weekly Report']
    ];

    // Get all document submissions for this student
    $sql = "
        SELECT 
            dt.name,
            dt.code,
            ds.status,
            ds.submitted_at
        FROM document_submissions ds
        INNER JOIN document_types dt ON ds.document_type_id = dt.id
        WHERE ds.student_id = :student_id
        ORDER BY ds.submitted_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($submissions as $submission) {
        $docName = strtolower($submission['name']);
        $docCode = strtolower($submission['code'] ?? '');
        $status = $submission['status'];
        $submittedAt = $submission['submitted_at'];

        // Check each document type
        foreach ($documentMap as $key => $possibleNames) {
            // Skip if already found (except for weekly report which we want the latest)
            if ($statuses[$key] !== '' && $key !== 'weekly report') {
                continue;
            }

            foreach ($possibleNames as $possibleName) {
                $possibleNameLower = strtolower($possibleName);

                // Check if document name or code contains the possible name
                if (strpos($docName, $possibleNameLower) !== false || strpos($docCode, $possibleNameLower) !== false) {
                    // For Endorsement, show the submitted date if approved
                    if ($key === 'Endorsement' && $status === 'approved') {
                        $date = new DateTime($submittedAt);
                        $statuses[$key] = $date->format('F j, Y'); // Format: January 3, 2025
                        break 2;
                    }
                    // For weekly report, show "done" if latest is submitted
                    elseif ($key === 'weekly report') {
                        if ($statuses[$key] === '') { // Only set once (latest)
                            $statuses[$key] = ($status === 'approved' || $status === 'pending') ? 'done' : '';
                        }
                        break 2;
                    }
                    // For others, show "done" if approved
                    elseif ($status === 'approved') {
                        $statuses[$key] = 'done';
                        break 2;
                    }
                }
            }
        }
    }

    return $statuses;
}
