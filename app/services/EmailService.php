<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class EmailService
{
    private $mail;
    private $config;

    public function __construct()
    {
        // Load email configuration
        $configPath = __DIR__ . '/../../config/email.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            // Default to local mail if config doesn't exist
            $this->config = [
                'use_smtp' => false,
                'from_email' => 'noreply@ojt.local',
                'from_name' => 'OJT Route System',
                'reply_to_email' => 'noreply@ojt.local',
                'reply_to_name' => 'OJT Route System',
                'is_html' => true
            ];
        }

        // Initialize PHPMailer only if SMTP is enabled
        if ($this->config['use_smtp'] ?? false) {
            $this->initializePHPMailer();
        }
    }

    /**
     * Initialize PHPMailer for SMTP
     */
    private function initializePHPMailer()
    {
        try {
            // Initialize PHPMailer
            $this->mail = new PHPMailer(true);

            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = $this->config['smtp_auth'];
            $this->mail->Username = $this->config['smtp_username'];
            $this->mail->Password = $this->config['smtp_password'];
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['smtp_port'];
            $this->mail->CharSet = $this->config['charset'];

            // Enable verbose debug output (set to 0 for production)
            $this->mail->SMTPDebug = 0;

            // From address
            $this->mail->setFrom(
                $this->config['from_email'],
                $this->config['from_name']
            );

            // Reply to
            $this->mail->addReplyTo(
                $this->config['reply_to_email'],
                $this->config['reply_to_name']
            );

            // Email format
            $this->mail->isHTML($this->config['is_html']);
        } catch (Exception $e) {
            throw new \Exception("EmailService initialization failed: {$this->mail->ErrorInfo}");
        }
    }

    /**
     * Send a simple email
     *
     * @param string|array $to Email address(es) - can be string or array
     * @param string $subject Email subject
     * @param string $body Email body (HTML supported)
     * @param string|array|null $cc CC email address(es) - optional
     * @param string|array|null $bcc BCC email address(es) - optional
     * @param array|null $attachments Array of file paths to attach
     * @return bool True on success, false on failure
     */
    public function sendEmail($to, $subject, $body, $cc = null, $bcc = null, $attachments = null)
    {
        try {
            // Use PHP mail() if SMTP is disabled (faster for local delivery)
            if (!($this->config['use_smtp'] ?? false)) {
                return $this->sendLocalMail($to, $subject, $body, $cc, $bcc, $attachments);
            }

            // Use PHPMailer for SMTP
            return $this->sendSMTPMail($to, $subject, $body, $cc, $bcc, $attachments);
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using PHP's built-in mail() function (faster local delivery)
     */
    private function sendLocalMail($to, $subject, $body, $cc = null, $bcc = null, $attachments = null)
    {
        try {
            // For development/demo purposes, log emails as well
            $this->logEmailForDemo($to, $subject, $body, $cc, $bcc, $attachments);

            // Prepare headers
            $headers = [
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                'Reply-To: ' . $this->config['reply_to_name'] . ' <' . $this->config['reply_to_email'] . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8'
            ];

            // Add CC if provided
            if ($cc) {
                if (is_array($cc)) {
                    $headers[] = 'Cc: ' . implode(', ', $cc);
                } else {
                    $headers[] = 'Cc: ' . $cc;
                }
            }

            // Add BCC if provided
            if ($bcc) {
                if (is_array($bcc)) {
                    $headers[] = 'Bcc: ' . implode(', ', $bcc);
                } else {
                    $headers[] = 'Bcc: ' . $bcc;
                }
            }

            // Convert headers array to string
            $headersString = implode("\r\n", $headers);

            // Handle multiple recipients
            $toAddresses = is_array($to) ? implode(', ', $to) : $to;

            // Send email using PHP mail()
            $success = mail($toAddresses, $subject, $body, $headersString);

            if (!$success) {
                error_log("PHP mail() failed to send email to: " . $toAddresses);
            }

            return $success;
        } catch (Exception $e) {
            error_log("Local mail sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log email for demo purposes (bypasses mail server requirements)
     */
    private function logEmailForDemo($to, $subject, $body, $cc = null, $bcc = null, $attachments = null)
    {
        try {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . '/emails.log';
            $timestamp = date('Y-m-d H:i:s');

            $toAddresses = is_array($to) ? implode(', ', $to) : $to;

            $logEntry = "=== Email Sent at {$timestamp} ===\n";
            $logEntry .= "To: {$toAddresses}\n";
            $logEntry .= "Subject: {$subject}\n";
            $logEntry .= "Body: {$body}\n";
            if ($cc) {
                $logEntry .= "CC: " . (is_array($cc) ? implode(', ', $cc) : $cc) . "\n";
            }
            if ($bcc) {
                $logEntry .= "BCC: " . (is_array($bcc) ? implode(', ', $bcc) : $bcc) . "\n";
            }
            $logEntry .= "Status: Logged (demo mode)\n";
            $logEntry .= "=====================================\n\n";

            // Write to log file
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

            // Also create individual HTML files for easy viewing
            $individualLogDir = $logDir . '/emails';
            if (!is_dir($individualLogDir)) {
                mkdir($individualLogDir, 0755, true);
            }

            $filename = preg_replace('/[^a-zA-Z0-9]/', '_', $subject) . '_' . time() . '.html';
            $individualFile = $individualLogDir . '/' . $filename;

            $emailContent = "<!DOCTYPE html><html><head><title>{$subject}</title></head><body>";
            $emailContent .= "<h2>Email Details</h2>";
            $emailContent .= "<p><strong>To:</strong> {$toAddresses}</p>";
            $emailContent .= "<p><strong>Subject:</strong> {$subject}</p>";
            $emailContent .= "<p><strong>Sent:</strong> {$timestamp}</p>";
            $emailContent .= "<hr>";
            $emailContent .= $body;
            $emailContent .= "</body></html>";

            file_put_contents($individualFile, $emailContent, LOCK_EX);

            return true; // Always return true for demo mode
        } catch (Exception $e) {
            error_log("Email logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using PHPMailer SMTP
     */
    private function sendSMTPMail($to, $subject, $body, $cc = null, $bcc = null, $attachments = null)
    {
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearCCs();
            $this->mail->clearBCCs();
            $this->mail->clearAttachments();

            // Add recipients
            if (is_array($to)) {
                foreach ($to as $email) {
                    $this->mail->addAddress($email);
                }
            } else {
                $this->mail->addAddress($to);
            }

            // Add CC if provided
            if ($cc) {
                if (is_array($cc)) {
                    foreach ($cc as $email) {
                        $this->mail->addCC($email);
                    }
                } else {
                    $this->mail->addCC($cc);
                }
            }

            // Add BCC if provided
            if ($bcc) {
                if (is_array($bcc)) {
                    foreach ($bcc as $email) {
                        $this->mail->addBCC($email);
                    }
                } else {
                    $this->mail->addBCC($bcc);
                }
            }

            // Add attachments if provided
            if ($attachments && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment)) {
                        $this->mail->addAttachment($attachment);
                    }
                }
            }

            // Set email content
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body); // Plain text version

            // Send email
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("SMTP mail sending failed: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send email using a template
     *
     * @param string|array $to Email address(es)
     * @param string $subject Email subject
     * @param string $templatePath Path to HTML template file
     * @param array $variables Variables to replace in template (e.g., ['name' => 'John'])
     * @param string|array|null $cc CC email address(es) - optional
     * @param string|array|null $bcc BCC email address(es) - optional
     * @return bool True on success, false on failure
     */
    public function sendTemplateEmail($to, $subject, $templatePath, $variables = [], $cc = null, $bcc = null)
    {
        try {
            // Load template
            if (!file_exists($templatePath)) {
                throw new \Exception("Template file not found: {$templatePath}");
            }

            $template = file_get_contents($templatePath);

            // Replace variables in template
            foreach ($variables as $key => $value) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
                // Also support PHP-style variables
                $template = str_replace('{$' . $key . '}', $value, $template);
            }

            return $this->sendEmail($to, $subject, $template, $cc, $bcc);
        } catch (\Exception $e) {
            error_log("Template email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send document approval email
     *
     * @param string $to Recipient email
     * @param string $recipientName Recipient name
     * @param string $documentName Document name
     * @return bool True on success, false on failure
     */
    public function sendDocumentApprovalEmail($to, $recipientName, $documentName)
    {
        $templatePath = __DIR__ . '/../../public/emailtemplate/document_approval.html';
        $subject = 'Document Approval Notification - OJT Route System';

        $variables = [
            'recipientName' => $recipientName,
            'documentName' => $documentName
        ];

        return $this->sendTemplateEmail($to, $subject, $templatePath, $variables);
    }

    /**
     * Get the last error message
     *
     * @return string Error message
     */
    public function getLastError()
    {
        if ($this->config['use_smtp'] ?? false && isset($this->mail)) {
            return $this->mail->ErrorInfo;
        }

        return 'Email logged successfully (demo mode) - check logs/emails/ directory';
    }

    /**
     * Get students by instructor ID
     *
     * @param int $instructorId Instructor ID
     * @return array Array of students
     */
    public function getStudentsByInstructor($instructorId)
    {
        try {
            $configPath = __DIR__ . '/../../config/database.php';
            $config = require $configPath;

            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['dbname'],
                $config['charset']
            );

            $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.email, u.school_id
                FROM users u
                JOIN sections s ON u.section_id = s.id
                WHERE s.instructor_id = :instructor_id 
                AND u.role = 'student'
                AND u.is_archived = 0
                AND s.is_active = 1
                ORDER BY u.full_name
            ");

            $stmt->execute([':instructor_id' => $instructorId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Database error getting students: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get student by ID
     *
     * @param int $studentId Student ID
     * @return array|null Student data or null if not found
     */
    public function getStudentById($studentId)
    {
        try {
            $configPath = __DIR__ . '/../../config/database.php';
            $config = require $configPath;

            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['dbname'],
                $config['charset']
            );

            $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.email, u.school_id
                FROM users u
                WHERE u.id = :student_id 
                AND u.role = 'student'
                AND u.is_archived = 0
                LIMIT 1
            ");

            $stmt->execute([':student_id' => $studentId]);
            $student = $stmt->fetch();

            return $student ?: null;
        } catch (\PDOException $e) {
            error_log('Database error getting student: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log email sending activity
     *
     * @param int $adminId Admin/Instructor ID
     * @param string $recipientScope Recipient scope (all_students, specific_student, etc.)
     * @param string $subject Email subject
     * @param string $body Email body
     * @param int $sentCount Number of successful sends
     * @param int $failedCount Number of failed sends
     * @return bool Success status
     */
    public function logEmail($adminId, $recipientScope, $subject, $body, $sentCount, $failedCount)
    {
        try {
            $configPath = __DIR__ . '/../../config/database.php';
            $config = require $configPath;

            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['dbname'],
                $config['charset']
            );

            $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

            $stmt = $pdo->prepare("
                INSERT INTO email_logs (admin_id, recipient_scope, subject, body, sent_at)
                VALUES (:admin_id, :recipient_scope, :subject, :body, NOW())
            ");

            return $stmt->execute([
                ':admin_id' => $adminId,
                ':recipient_scope' => $recipientScope,
                ':subject' => $subject,
                ':body' => $body
            ]);
        } catch (\PDOException $e) {
            error_log('Database error logging email: ' . $e->getMessage());
            return false;
        }
    }
}

