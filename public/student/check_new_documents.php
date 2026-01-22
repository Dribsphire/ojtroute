<?php
session_start();
require_once '../../app/services/StudentService.php';

// Set JSON header
header('Content-Type: application/json');

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['count' => 0]);
    exit;
}

$studentService = new \App\Services\StudentService();
$userId = $_SESSION['user_id'];

try {
    // Get count of new documents (created in last 7 days that student hasn't viewed)
    $count = $studentService->getNewDocumentsCount($userId);

    echo json_encode([
        'count' => $count,
        'success' => true
    ]);

} catch (Exception $e) {
    error_log('Check new documents error: ' . $e->getMessage());
    echo json_encode([
        'count' => 0,
        'success' => false,
        'error' => 'Failed to check documents'
    ]);
}
