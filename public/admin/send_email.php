<?php
/**
 * Send Email Handler
 * Handles email sending from admin panel
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/controller/EmailController.php';

use App\Controller\EmailController;

try {
    $controller = new EmailController();
    $controller->sendEmail();
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

