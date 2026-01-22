<?php
session_start();
require_once __DIR__ . '/../../app/services/InstructorService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['count' => 0, 'timeout_count' => 0]);
    exit;
}

$service = new \App\Services\InstructorService();
$instructorId = $service->getInstructorId($_SESSION['user_id']);

if ($instructorId) {
    try {
        $count = $service->getPendingSubmissionsCount($instructorId);

        // Get pending timeout requests count
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
        $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);

        $stmt = $db->prepare("
            SELECT COUNT(*) as timeout_count
            FROM attendance_records ar
            JOIN students st ON ar.student_id = st.id
            JOIN users u ON st.user_id = u.id
            JOIN sections s ON u.section_id = s.id
            WHERE s.instructor_id = :instructor_id
            AND ar.request_status = 'pending'
        ");
        $stmt->execute([':instructor_id' => $instructorId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $timeoutCount = $result['timeout_count'] ?? 0;

        // Get new attendance records count (since last view)
        $lastViewTime = $_SESSION['attendance_last_view'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));

        $stmt = $db->prepare("
            SELECT COUNT(*) as attendance_count
            FROM attendance_records ar
            JOIN students st ON ar.student_id = st.id
            JOIN users u ON st.user_id = u.id
            JOIN sections s ON u.section_id = s.id
            WHERE s.instructor_id = :instructor_id
            AND ar.created_at > :last_view
        ");
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':last_view' => $lastViewTime
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $attendanceCount = $result['attendance_count'] ?? 0;

        echo json_encode([
            'count' => $count,
            'timeout_count' => $timeoutCount,
            'attendance_count' => $attendanceCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'timeout_count' => 0, 'attendance_count' => 0]);
    }
} else {
    echo json_encode(['count' => 0, 'timeout_count' => 0, 'attendance_count' => 0]);
}
