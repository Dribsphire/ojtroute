<?php
/**
 * Email Controller
 * 
 * Handles email-related API requests
 */

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/UserService.php';

use App\Services\EmailService;
use App\Services\UserService;

class EmailController
{
    private $emailService;
    private $userService;

    public function __construct()
    {
        try {
            $this->emailService = new EmailService();
            $this->userService = new UserService();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Service initialization failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Send email to recipients
     * 
     * Expected POST data:
     * - recipientType: 'all_students', 'all_instructors', or 'specific_student'
     * - specificStudent: (optional) Student ID or email if recipientType is 'specific_student'
     * - subject: Email subject
     * - body: Email body (HTML supported)
     */
    public function sendEmail()
    {
        header('Content-Type: application/json');

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            return;
        }

        // Get POST data
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        // Fallback to $_POST if JSON parsing fails
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // JSON decode failed, try $_POST
            $data = $_POST;
        }

        // Ensure data is an array
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request data format'
            ]);
            return;
        }

        // Validate required fields - trim to handle whitespace-only values
        $subject = isset($data['subject']) ? trim($data['subject']) : '';
        $body = isset($data['body']) ? trim($data['body']) : '';
        
        if (empty($subject) || empty($body)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Subject and body are required'
            ]);
            return;
        }

        $recipientType = $data['recipientType'] ?? 'all_students';
        // Subject and body are already trimmed above
        $specificStudent = $data['specificStudent'] ?? null;

        try {
            // Get recipient email addresses based on type
            $recipients = $this->getRecipients($recipientType, $specificStudent);

            if (empty($recipients)) {
                http_response_code(400);
                
                // Provide more helpful error message
                $errorMessage = 'No recipients found';
                if ($recipientType === 'all_students') {
                    $errorMessage = 'No students with email addresses found. Please ensure students have email addresses in their profiles.';
                } elseif ($recipientType === 'all_instructors') {
                    $errorMessage = 'No instructors with email addresses found. Please ensure instructors have email addresses in their profiles.';
                } elseif ($recipientType === 'specific_student') {
                    $errorMessage = 'Selected student not found or does not have an email address.';
                }
                
                echo json_encode([
                    'success' => false,
                    'message' => $errorMessage
                ]);
                return;
            }

            // Get admin info for template
            $adminInfo = $this->getAdminInfo();

            // Prepare template path
            $templatePath = __DIR__ . '/../../public/emailtemplate/admin_email.html';
            
            if (!file_exists($templatePath)) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email template not found'
                ]);
                return;
            }
            
            // Load template once
            $templateBase = file_get_contents($templatePath);
            
            // Send email to each recipient with personalized content
            $successCount = 0;
            $failCount = 0;
            $errors = [];

            foreach ($recipients as $recipient) {
                // Create personalized template for each recipient
                $template = $templateBase;
                
                // Replace placeholders in template
                $template = str_replace('name here', htmlspecialchars($recipient['full_name']), $template);
                
                // Replace the comment and message - need to handle the HTML comment properly
                $messageHtml = nl2br(htmlspecialchars($body));
                // Replace the HTML comment with the message
                $template = str_replace('<!--Admin message here -->', $messageHtml, $template);
                
                // Replace admin name
                $template = str_replace('Admin Name Here', htmlspecialchars($adminInfo['name']), $template);
                
                // Replace CSS variable with actual color value (multiple occurrences)
                $template = str_replace('var(--accent-clr)', '#1ad21c', $template);
                
                // Fix image path - convert relative path to absolute URL for email
                // The template uses ../images/CHMSU.png, we need to use absolute URL
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                // Get base URL - remove /public/admin from script path
                $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
                $basePath = str_replace('/admin', '', $scriptPath);
                $imageUrl = $protocol . '://' . $host . $basePath . '/images/CHMSU.png';
                $template = str_replace('../images/CHMSU.png', $imageUrl, $template);

            // Send email
            $success = $this->emailService->sendEmail(
                    $recipient['email'],
                $subject,
                    $template
            );

            if ($success) {
                    $successCount++;
                    // Log email to database
                    $this->logEmail($recipient['id'], $subject, $body, 'sent');
                } else {
                    $failCount++;
                    $errorMsg = $this->emailService->getLastError();
                    $errors[] = $recipient['email'] . ': ' . ($errorMsg ?: 'Unknown error');
                    // Log email to database
                    $this->logEmail($recipient['id'], $subject, $body, 'failed');
                }
            }

            if ($successCount > 0) {
                $message = "Email sent successfully to {$successCount} recipient(s)";
                if ($failCount > 0) {
                    $message .= ". {$failCount} failed: " . implode(', ', array_slice($errors, 0, 3));
                }
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'sent' => $successCount,
                    'failed' => $failCount
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send emails: ' . implode(', ', array_slice($errors, 0, 3))
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get recipient email addresses based on type
     * 
     * @param string $recipientType Type of recipient
     * @param string|null $specificStudent Specific student identifier (user_id or email)
     * @return array Array of email addresses with user info
     */
    private function getRecipients($recipientType, $specificStudent = null)
    {
        $recipients = [];
        $db = $this->userService->getDb();

        try {
        switch ($recipientType) {
            case 'all_students':
                    $stmt = $db->prepare("
                        SELECT id, email, full_name, school_id 
                        FROM users 
                        WHERE role = 'student' 
                        AND is_archived = 0 
                        AND email IS NOT NULL 
                        AND email != ''
                    ");
                    $stmt->execute();
                    $recipients = $stmt->fetchAll();
                break;

            case 'all_instructors':
                    $stmt = $db->prepare("
                        SELECT id, email, full_name, school_id 
                        FROM users 
                        WHERE role = 'instructor' 
                        AND is_archived = 0 
                        AND email IS NOT NULL 
                        AND email != ''
                    ");
                    $stmt->execute();
                    $recipients = $stmt->fetchAll();
                break;

            case 'specific_student':
                if ($specificStudent) {
                        // Convert to integer if it's numeric (for ID lookup)
                        $isNumeric = is_numeric($specificStudent);
                        $studentId = $isNumeric ? (int)$specificStudent : null;
                        
                        if ($studentId) {
                            // Lookup by ID (using PDO parameter binding which handles type conversion)
                            $stmt = $db->prepare("
                                SELECT id, email, full_name, school_id 
                                FROM users 
                                WHERE id = :identifier 
                                AND role = 'student' 
                                AND is_archived = 0
                                AND email IS NOT NULL 
                                AND email != ''
                                LIMIT 1
                            ");
                            $stmt->execute([':identifier' => $studentId]);
                        } else {
                            // Lookup by email (if identifier is not numeric)
                            $stmt = $db->prepare("
                                SELECT id, email, full_name, school_id 
                                FROM users 
                                WHERE email = :identifier 
                                AND role = 'student' 
                                AND is_archived = 0
                                AND email IS NOT NULL 
                                AND email != ''
                                LIMIT 1
                            ");
                            $stmt->execute([':identifier' => $specificStudent]);
                        }
                        
                        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if ($user) {
                            $recipients[] = $user;
                        } else {
                            // Log for debugging
                            error_log("Student not found: ID/Email = " . var_export($specificStudent, true));
                        }
                }
                break;
            }
        } catch (\PDOException $e) {
            error_log('Error fetching recipients: ' . $e->getMessage());
        }

        return $recipients;
    }

    /**
     * Get admin information for email template
     * 
     * @return array Admin info with name
     */
    private function getAdminInfo()
    {
        $db = $this->userService->getDb();
        
        try {
            $stmt = $db->prepare("
                SELECT full_name 
                FROM users 
                WHERE role = 'admin' 
                AND is_archived = 0 
                LIMIT 1
            ");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if ($admin) {
                return ['name' => $admin['full_name']];
            }
        } catch (\PDOException $e) {
            error_log('Error fetching admin info: ' . $e->getMessage());
        }
        
        return ['name' => 'OJT Administrator'];
    }

    /**
     * Log email to database
     * 
     * @param int $recipientId User ID of recipient
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $status Status: 'sent' or 'failed'
     */
    private function logEmail($recipientId, $subject, $body, $status)
    {
        $db = $this->userService->getDb();
        
        try {
            // Get current admin user ID from session
            $adminId = null;
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['id'])) {
                $adminId = $_SESSION['id'];
            }

            $stmt = $db->prepare("
                INSERT INTO email_logs (sender_user_id, recipient_user_id, subject, body, status, sent_at)
                VALUES (:sender_id, :recipient_id, :subject, :body, :status, NOW())
            ");
            
            $stmt->execute([
                ':sender_id' => $adminId,
                ':recipient_id' => $recipientId,
                ':subject' => $subject,
                ':body' => $body,
                ':status' => $status
            ]);
        } catch (\PDOException $e) {
            // Log error but don't fail the email sending
            error_log('Error logging email: ' . $e->getMessage());
        }
    }

    /**
     * Get list of students for dropdown
     * Only returns students with email addresses (since we can only email students with emails)
     * 
     * @return void Outputs JSON
     */
    public function getStudents()
    {
        header('Content-Type: application/json');
        
        try {
            $db = $this->userService->getDb();
            $stmt = $db->prepare("
                SELECT u.id, u.school_id, u.full_name, u.email
                FROM users u
                WHERE u.role = 'student' 
                AND u.is_archived = 0
                AND u.email IS NOT NULL 
                AND u.email != ''
                ORDER BY u.full_name ASC
            ");
            $stmt->execute();
            $students = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching students: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Send document approval email
     * 
     * Expected POST data:
     * - email: Recipient email
     * - recipientName: Recipient name
     * - documentName: Document name
     */
    public function sendDocumentApproval()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        if (empty($data['email']) || empty($data['recipientName']) || empty($data['documentName'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email, recipient name, and document name are required'
            ]);
            return;
        }

        $success = $this->emailService->sendDocumentApprovalEmail(
            $data['email'],
            $data['recipientName'],
            $data['documentName']
        );

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Document approval email sent successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email: ' . $this->emailService->getLastError()
            ]);
        }
    }
}

// Handle requests - only run when accessed directly, not when included
if (php_sapi_name() !== 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new EmailController();
    
    $action = $_GET['action'] ?? 'send';
    
    switch ($action) {
        case 'send':
            $controller->sendEmail();
            break;
        case 'get-students':
            $controller->getStudents();
            break;
        case 'document-approval':
            $controller->sendDocumentApproval();
            break;
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Action not found'
            ]);
            break;
    }
}


