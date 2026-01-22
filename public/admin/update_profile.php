<?php
/**
 * Update Admin Profile Handler
 * Updates admin profile information
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/ProfileService.php';

use App\Services\ProfileService;

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
    // Get current user ID from session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get POST data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    // Fallback to $_POST if JSON parsing fails
    if (!$data || !is_array($data)) {
        $data = $_POST;
    }

    // Validate required fields
    if (empty($data['full_name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Full name is required'
        ]);
        exit;
    }

    $profileService = new ProfileService();
    $result = $profileService->updateAdminProfile($userId, [
        'full_name' => $data['full_name'] ?? null,
        'email' => $data['email'] ?? null,
        'gender' => $data['gender'] ?? null,
        'contact' => $data['contact'] ?? null,
        'facebook_name' => $data['facebook_name'] ?? null
    ]);

    if ($result['success']) {
        // Update session data
        if (isset($data['full_name'])) {
            $_SESSION['full_name'] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $_SESSION['email'] = $data['email'];
        }

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

