<?php

namespace App\Controller;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../middleware/requireAdmin.php'; // Require admin authentication

use App\Services\UserService;

class UserController
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Handle CSV upload and registration
     */
    public function handleCSVUpload()
    {
        header('Content-Type: application/json');

        // Check if file was uploaded
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => 'No file uploaded or upload error occurred'
            ]);
            exit;
        }

        $file = $_FILES['csvFile'];

        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($fileExtension !== 'csv') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Please upload a CSV file.'
            ]);
            exit;
        }

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds 5MB limit'
            ]);
            exit;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../storage/csv/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = 'csv_upload_' . time() . '_' . uniqid() . '.csv';
        $filePath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save uploaded file'
            ]);
            exit;
        }

        try {
            // Process CSV
            $results = $this->userService->registerUsersFromCSV($filePath);

            // Clean up uploaded file
            @unlink($filePath);

            // Return results
            echo json_encode($results);
            exit;

        } catch (\Exception $e) {
            // Clean up uploaded file on error
            @unlink($filePath);
            
            echo json_encode([
                'success' => false,
                'message' => 'Error processing CSV: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Generate CSV template for download
     */
    public function downloadCSVTemplate()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_registration_template.csv"');

        $output = fopen('php://output', 'w');

        // Write header row
        fputcsv($output, [
            'school_id',
            'full_name',
            'email',
            'role',
            'gender',
            'section',
            'contact',
            'facebook_name',
            'password',
            'year'
        ]);

        // Write example row
        fputcsv($output, [
            'STU12345600',
            'John Doe',
            'john.doe@example.com',
            'student',
            'male',
            'BSIT-4A',
            '09123456789',
            'John Doe',
            'STU12345600',
            '2025'
        ]);

        fclose($output);
        exit;
    }
}

