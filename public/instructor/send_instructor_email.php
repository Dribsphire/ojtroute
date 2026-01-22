<?php
// Increase execution time limit for email sending
set_time_limit(300); // 5 minutes instead of default 2 minutes

session_start();

// Require instructor authentication
require_once __DIR__ . '/../../app/middleware/requireInstructor.php';

// Load email service
require_once __DIR__ . '/../../app/services/EmailService.php';

use App\Services\EmailService;

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$recipientType = $input['recipientType'] ?? '';
$subject = $input['subject'] ?? '';
$body = $input['body'] ?? '';
$instructorName = $input['instructorName'] ?? 'Instructor';
$specificStudent = $input['specificStudent'] ?? null;

// Validate input
if (empty($recipientType) || empty($subject) || empty($body)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (!in_array($recipientType, ['all_students', 'specific_student'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid recipient type']);
    exit;
}

if ($recipientType === 'specific_student' && empty($specificStudent)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required for specific student emails']);
    exit;
}

try {
    $emailService = new EmailService();
    
    // Get instructor's section ID
    $instructorId = $_SESSION['instructor_id'] ?? null;
    if (!$instructorId) {
        echo json_encode(['success' => false, 'message' => 'Instructor not found']);
        exit;
    }
    
    // Get students based on recipient type
    $students = [];
    if ($recipientType === 'all_students') {
        // Get all students from instructor's sections
        $students = $emailService->getStudentsByInstructor($instructorId);
    } else {
        // Get specific student
        $student = $emailService->getStudentById($specificStudent);
        if ($student) {
            $students = [$student];
        }
    }
    
    if (empty($students)) {
        echo json_encode(['success' => false, 'message' => 'No students found']);
        exit;
    }
    
    // Prepare email template
    $templatePath = __DIR__ . '/../emailtemplate/instructor_email.html';
    if (!file_exists($templatePath)) {
        echo json_encode(['success' => false, 'message' => 'Email template not found']);
        exit;
    }
    
    $template = file_get_contents($templatePath);
    
    // Replace placeholders in template
    $emailBody = str_replace('<!--Admin message here -->', nl2br(htmlspecialchars($body)), $template);
    $emailBody = str_replace('name here', 'Students', $emailBody);
    $emailBody = str_replace('Admin Name Here', htmlspecialchars($instructorName), $emailBody);
    
    // Send emails
    $sentCount = 0;
    $failedCount = 0;
    $failedEmails = [];
    
    foreach ($students as $student) {
        try {
            $personalizedBody = str_replace('Students', htmlspecialchars($student['full_name']), $emailBody);
            
            $success = $emailService->sendEmail(
                $student['email'],
                $subject,
                $personalizedBody
            );
            
            if ($success) {
                $sentCount++;
            } else {
                $failedCount++;
                $failedEmails[] = $student['email'];
            }
        } catch (Exception $e) {
            $failedCount++;
            $failedEmails[] = $student['email'];
            error_log('Failed to send email to ' . $student['email'] . ': ' . $e->getMessage());
        }
    }
    
    // Log email sending
    $emailService->logEmail($_SESSION['user_id'], $recipientType, $subject, $body, $sentCount, $failedCount);
    
    // Return response
    echo json_encode([
        'success' => true,
        'message' => 'Email processing completed',
        'sent' => $sentCount,
        'failed' => $failedCount,
        'failed_emails' => $failedEmails
    ]);
    
} catch (Exception $e) {
    error_log('Email sending error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending emails']);
}
?>
