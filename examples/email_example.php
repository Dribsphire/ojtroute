<?php
/**
 * PHPMailer Usage Examples
 * 
 * This file demonstrates how to use the EmailService class
 * to send emails in your application.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/EmailService.php';

use App\Services\EmailService;

try {
    // Initialize EmailService
    $emailService = new EmailService();

    // Example 1: Send a simple email
    echo "Example 1: Sending simple email...\n";
    $success = $emailService->sendEmail(
        'recipient@example.com',
        'Test Subject',
        '<h1>Hello!</h1><p>This is a test email.</p>'
    );
    
    if ($success) {
        echo "Email sent successfully!\n";
    } else {
        echo "Failed to send email: " . $emailService->getLastError() . "\n";
    }

    // Example 2: Send email to multiple recipients
    echo "\nExample 2: Sending to multiple recipients...\n";
    $success = $emailService->sendEmail(
        ['student1@example.com', 'student2@example.com'],
        'Announcement',
        '<p>This is an announcement for all students.</p>',
        null, // CC
        null, // BCC
        null  // Attachments
    );

    // Example 3: Send email with CC and BCC
    echo "\nExample 3: Sending with CC and BCC...\n";
    $success = $emailService->sendEmail(
        'student@example.com',
        'Important Notice',
        '<p>This is an important notice.</p>',
        'instructor@example.com', // CC
        'admin@example.com'       // BCC
    );

    // Example 4: Send email with attachment
    echo "\nExample 4: Sending with attachment...\n";
    $success = $emailService->sendEmail(
        'student@example.com',
        'Document Attached',
        '<p>Please find the attached document.</p>',
        null,
        null,
        [__DIR__ . '/../public/images/documentSample/PDF Sample 1.pdf'] // Attachments
    );

    // Example 5: Send email using template
    echo "\nExample 5: Sending template email...\n";
    $success = $emailService->sendTemplateEmail(
        'student@example.com',
        'Welcome to OJT Route System',
        __DIR__ . '/../public/emailtemplate/document_approval.html',
        [
            'recipientName' => 'John Doe',
            'documentName' => 'Memorandum of Agreement (MOA)'
        ]
    );

    // Example 6: Send document approval email (using built-in method)
    echo "\nExample 6: Sending document approval email...\n";
    $success = $emailService->sendDocumentApprovalEmail(
        'student@example.com',
        'Manuel Colorado',
        'Memorandum of Agreement (MOA)'
    );

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

