<?php
session_start();

// Handle POST requests for AJAX calls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Require authentication for AJAX requests
    require_once __DIR__ . '/../../app/middleware/requireStudent.php';
    require_once __DIR__ . '/../../app/services/StudentService.php';
    require_once __DIR__ . '/../../app/services/UserService.php';

    $studentService = new \App\Services\StudentService();
    $userService = new \App\Services\UserService();
    $response = ['success' => false, 'error' => 'Invalid request'];

    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $data = [
                    'email' => $_POST['email'] ?? '',
                    'contact' => $_POST['contact'] ?? '',
                    'facebook_name' => $_POST['facebook'] ?? ''
                ];

                // Validate email
                if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $response['error'] = 'Invalid email format';
                    break;
                }

                // Start transaction if possible (or just sequential updates)
                $success = $studentService->updateStudentProfile($student_id, $data);

                // Handle Profile Picture Upload
                if ($success && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_picture'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    $maxSize = 5 * 1024 * 1024; // 5MB

                    if (!in_array($file['type'], $allowedTypes)) {
                        // Note: We don't fail the whole request if only image fails, but we could return a warning
                        // For now, let's treat it as a secondary action
                    } elseif ($file['size'] > $maxSize) {
                        // File too large
                    } else {
                        $uploadDir = __DIR__ . '/../../storage/uploads/profiles/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'profile_' . $student_id . '_' . time() . '.' . $extension;
                        $targetPath = $uploadDir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Update database path (relative to public/student directory where profile is viewed)
                            $dbPath = '../../storage/uploads/profiles/' . $filename;
                            $studentService->updateProfilePicture($student_id, $dbPath);
                        }
                    }
                }

                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Profile updated successfully';
                } else {
                    $response['error'] = 'Failed to update profile';
                }
                break;

            case 'update_workplace':
                // Check if this is initial setup or change request
                $hasWorkplace = $studentService->hasWorkplace($student_id);

                $workplaceData = [
                    'workplace_name' => $_POST['workplace_name'] ?? '',
                    'workplace_address' => $_POST['workplace_address'] ?? '',
                    'position' => $_POST['position'] ?? '',
                    'supervisor_name' => $_POST['supervisor_name'] ?? '',
                    'supervisor_position' => $_POST['supervisor_position'] ?? '',
                    'head_trainee' => $_POST['head_trainee'] ?? '',
                    'head_trainee_position' => $_POST['head_trainee_position'] ?? '',
                    'head_trainee_contact' => $_POST['head_trainee_contact'] ?? '',
                    'head_trainee_email' => $_POST['head_trainee_email'] ?? '',
                    'latitude' => $_POST['latitude'] ?? 0,
                    'longitude' => $_POST['longitude'] ?? 0
                ];

                // Validate required fields (except change_reason for initial setup)
                if (
                    empty($workplaceData['workplace_name']) || empty($workplaceData['workplace_address']) ||
                    empty($workplaceData['position']) || empty($workplaceData['supervisor_name']) ||
                    empty($workplaceData['latitude']) || empty($workplaceData['longitude'])
                ) {
                    $response['error'] = 'All fields are required';
                    break;
                }

                if ($hasWorkplace) {
                    // Requesting a change
                    $changeReason = $_POST['change_reason'] ?? '';
                    if (empty($changeReason)) {
                        $response['error'] = 'Reason for change is required';
                        break;
                    }

                    $workplaceData['change_reason'] = $changeReason;
                    $success = $studentService->submitWorkplaceChangeRequest($student_id, $workplaceData);

                    if ($success) {
                        $response['success'] = true;
                        $response['message'] = 'Workplace change request submitted successfully. Waiting for instructor approval.';
                    } else {
                        $response['error'] = 'Failed to submit workplace change request';
                    }
                } else {
                    // Initial workplace setup - direct insert
                    $data = [
                        'company_name' => $workplaceData['workplace_name'],
                        'company_address' => $workplaceData['workplace_address'],
                        'position_title' => $workplaceData['position'],
                        'company_head' => $workplaceData['supervisor_name'],
                        'supervisor_position' => $workplaceData['supervisor_position'],
                        'head_trainee' => $workplaceData['head_trainee'],
                        'head_trainee_position' => $workplaceData['head_trainee_position'],
                        'head_trainee_contact' => $workplaceData['head_trainee_contact'],
                        'head_trainee_email' => $workplaceData['head_trainee_email'],
                        'workplace_latitude' => $workplaceData['latitude'],
                        'workplace_longitude' => $workplaceData['longitude']
                    ];

                    $success = $studentService->updateActiveWorkplace($student_id, $data);

                    if ($success) {
                        $response['success'] = true;
                        $response['message'] = 'Workplace set successfully!';
                    } else {
                        $response['error'] = 'Failed to set workplace';
                    }
                }
                break;

            case 'update_actual_workplace':
                $wpData = [
                    'company_name' => $_POST['company_name'] ?? '',
                    'company_head' => $_POST['company_head'] ?? '',
                    'position_title' => $_POST['position_title'] ?? '',
                    'company_address' => $_POST['address'] ?? ''
                ];

                if (!empty($_POST['latitude']) && !empty($_POST['longitude'])) {
                    $wpData['workplace_latitude'] = $_POST['latitude'];
                    $wpData['workplace_longitude'] = $_POST['longitude'];
                }

                if ($studentService->updateActiveWorkplace($student_id, $wpData)) {
                    $response['success'] = true;
                    $response['message'] = 'Workplace details updated successfully';
                } else {
                    $response['error'] = 'Failed to update workplace details';
                }
                break;

            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                // Validate inputs
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $response['error'] = 'All password fields are required';
                    break;
                }

                if ($newPassword !== $confirmPassword) {
                    $response['error'] = 'New passwords do not match';
                    break;
                }

                if (strlen($newPassword) < 6) {
                    $response['error'] = 'New password must be at least 6 characters long';
                    break;
                }

                // Verify current password
                $db = $studentService->getDb();
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $student_id]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                    $response['error'] = 'Current password is incorrect';
                    break;
                }

                // Update password using UserService
                $result = $userService->updatePassword($student_id, $newPassword);

                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = 'Password changed successfully';
                } else {
                    $response['error'] = $result['message'] ?? 'Failed to change password';
                }
                break;
        }
    } catch (Exception $e) {
        $response['error'] = 'An error occurred: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Require student authentication for page access
require_once __DIR__ . '/../../app/middleware/requireStudent.php';

// Load student service
require_once __DIR__ . '/../../app/services/StudentService.php';

// Initialize student service
$studentService = new \App\Services\StudentService();

// Get student profile data from database
$student_profile = $studentService->getStudentProfile($student_id);

// If no profile data found, redirect to login
if (!$student_profile) {
    header('Location: ../login.php');
    exit();
}

// Check if student has already set up workplace
$hasWorkplace = $studentService->hasWorkplace($student_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - OJTRoute System</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="../css/student_style.css">
    <script src="../js/hours-validation.js"></script>
    <script>
        // Safety check to prevent progress bar overflow on load
        document.addEventListener('DOMContentLoaded', function () {
            const progressBar = document.querySelector('.progress-fill');
            const percentageText = document.querySelector('.progress-percentage');

            if (progressBar && percentageText) {
                // Get current width/value
                let currentWidth = parseFloat(progressBar.style.width) || 0;
                let currentText = parseFloat(percentageText.textContent) || 0;

                // Cap at 100% using safeCalculatePercentage logic
                if (currentWidth > 100) {
                    console.warn('Progress overflow detected:', currentWidth, '% - Capping at 100%');
                    progressBar.style.width = '100%';
                    percentageText.textContent = '100%';

                    // Add visual indicator of completion
                    progressBar.style.backgroundColor = '#32cd32'; // Green
                }
            }
        });
    </script>
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-header {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            background: var(--hover-clr);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-clr);
        }

        .profile-info {
            flex: 1;
            min-width: 250px;
        }

        .profile-name {
            font-size: 1.8rem;
            margin: 1.2rem 0 0.5rem 0;
            color: var(--accent-clr);
        }

        .profile-id {
            color: var(--secondary-text-clr);
            margin-bottom: 1rem;
            display: block;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(25rem, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .detail-card {
            background: var(--hover-clr);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .detail-card h3 {
            color: var(--accent-clr);
            margin-top: 0;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.75rem;
            align-items: center;
        }

        .detail-label {
            font-weight: 500;
            color: var(--secondary-text-clr);
            width: 120px;
            flex-shrink: 0;
        }

        .detail-value {
            flex: 1;
        }

        .contact-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent-clr);
            color: #111;
        }

        .btn-outline {
            border: 1px solid var(--accent-clr);
            color: var(--accent-clr);
        }

        /* OJT Progress Styles */
        .ojt-progress {
            padding: 1.5rem;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-header h4 {
            margin: 0;
            color: var(--text-clr);
            font-size: 1.1rem;
        }

        .progress-percentage {
            font-weight: 600;
            color: var(--accent-clr);
            font-size: 1.2rem;
        }

        .progress-bar {
            height: 12px;
            background: var(--base-clr);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--accent-clr);
            border-radius: 6px;
            transition: width 0.5s ease-in-out;
        }

        .progress-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
            margin-bottom: 1.5rem;
        }

        /* Weekly Progress */
        .weekly-progress {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--line-clr);
        }

        .weekly-progress h5 {
            margin: 0 0 1rem 0;
            color: var(--text-clr);
            font-size: 1rem;
        }

        .weeks-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .week-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .week-label {
            width: 70px;
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
        }

        .week-bar {
            flex: 1;
            height: 8px;
            background: var(--base-clr);
            border-radius: 4px;
            overflow: hidden;
        }

        .week-fill {
            height: 100%;
            background: var(--accent-clr);
            opacity: 0.7;
            transition: width 0.3s ease;
        }

        .week-hours {
            width: 70px;
            text-align: right;
            font-size: 0.85rem;
            color: var(--accent-clr);
            font-weight: 500;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 210, 28, 0.2);
        }

        .instructor-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }


        .instructor-pic,
        .admin-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .instructor-info,
        .admin-info {
            flex: 1;
        }

        .instructor-name,
        .admin-name {
            font-weight: 500;
            margin: 0 0 0.25rem 0;
        }

        .instructor-role,
        .admin-role {
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
        }

        @media (max-width: 850px) {
            .profile-container {
                width: 100%;
                margin: 1rem 0;
                padding: 0 1rem;
            }

            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-info {
                text-align: center;
                min-width: 0;
                width: 100%;
            }

            .contact-buttons {
                justify-content: center;
            }

            .profile-details {
                grid-template-columns: 1fr;
            }

            .detail-card {
                padding: 1rem;
            }

            .detail-label {
                width: 100px;
                font-size: 0.9rem;
            }

            .detail-value {
                font-size: 0.9rem;
                overflow-wrap: break-word;
            }

            /* Fix header alignment */
            .detail-card .header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .detail-label {
                width: 100%;
                color: var(--accent-clr);
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
            padding: 2rem 0;
        }

        .modal.show {
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: calc(100vh - 4rem);
            position: relative;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            overflow-y: auto;
            margin: auto;
            scroll-behavior: smooth;
        }

        /* Custom scrollbar for modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: var(--base-clr);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: var(--accent-clr);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #15a517;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-title {
            color: var(--accent-clr);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--secondary-text-clr);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            color: var(--text-clr);
            background-color: var(--base-clr);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-clr);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 5px;
            color: var(--text-clr);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-clr);
            box-shadow: 0 0 0 2px rgba(26, 210, 28, 0.2);
        }

        .form-input::placeholder {
            color: var(--secondary-text-clr);
        }

        .file-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-input {
            display: none;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 5px;
            color: var(--text-clr);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            border-color: var(--accent-clr);
            background-color: var(--hover-clr);
        }

        .file-name {
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line-clr);
        }

        .btn-cancel {
            background-color: var(--base-clr);
            color: var(--text-clr);
            border: 1px solid var(--line-clr);
        }

        .btn-cancel:hover {
            background-color: var(--hover-clr);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-save {
            background-color: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            font-weight: 600;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 210, 28, 0.3);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }

            .modal-footer {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Workplace Modal Styles */
        .workplace-modal-content {
            max-width: 600px;
            width: 95%;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }

        .workplace-map-container {
            margin-bottom: 1rem;
        }

        .map-header {
            text-align: center;
            margin-bottom: 0.75rem;
        }

        .map-header h3 {
            color: var(--accent-clr);
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
        }

        .map-header p {
            color: var(--secondary-text-clr);
            margin: 0;
            font-size: 0.8rem;
        }

        /* Location Search Styles */
        .location-search-container {
            margin-bottom: 1rem;
            position: relative;
        }

        .search-input-wrapper {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background-color: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 5px;
            color: var(--text-clr);
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-clr);
            box-shadow: 0 0 0 2px rgba(26, 210, 28, 0.2);
        }

        .search-input::placeholder {
            color: var(--secondary-text-clr);
        }

        .search-btn {
            padding: 0.75rem 1.2rem;
            background-color: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 210, 28, 0.3);
        }

        .search-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 5px;
            margin-top: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
        }

        .search-results.show {
            display: block;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid var(--line-clr);
            transition: background-color 0.2s ease;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background-color: var(--base-clr);
        }

        .search-result-name {
            font-weight: 500;
            color: var(--text-clr);
            margin-bottom: 0.25rem;
        }

        .search-result-address {
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
        }

        .search-loading,
        .search-no-results {
            padding: 1rem;
            text-align: center;
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
        }

        .workplace-map {
            width: 100%;
            height: 250px;
            border: 2px solid var(--line-clr);
            border-radius: 8px;
            background: #f0f0f0;
            position: relative;
            cursor: crosshair;
            margin-bottom: 1rem;
        }



        .map-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .coordinate-display {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        .coord-item {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .coord-item label {
            font-size: 0.8rem;
            color: var(--secondary-text-clr);
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .coord-item input {
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 4px;
            padding: 0.4rem;
            color: var(--text-clr);
            font-size: 0.85rem;
        }

        .change-notice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff4f0;
            border-left: 4px solid #ff6b35;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            color: #8b4513;
            font-size: 0.85rem;
        }

        .change-notice i {
            color: #ff6b35;
            font-size: 1.1rem;
        }

        .form-input[readonly] {
            background: var(--hover-clr);
            cursor: not-allowed;
            opacity: 0.7;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 60px;
        }

        @media (max-width: 768px) {
            .modal {
                padding: 1rem 0;
            }

            .modal-content {
                margin: 0 1rem;
                padding: 1.5rem;
                width: calc(100% - 2rem);
                max-height: calc(100vh - 2rem);
            }

            .workplace-modal-content {
                max-height: calc(100vh - 2rem);
                width: calc(100% - 2rem);
                margin: 0 1rem;
                padding: 1rem;
            }

            .modal-header {
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .modal-title {
                font-size: 1.1rem;
                flex: 1 1 100%;
            }

            .map-header h3 {
                font-size: 0.9rem;
            }

            .map-header p {
                font-size: 0.75rem;
            }

            /* Location Search Mobile Styles */
            .search-input-wrapper {
                flex-direction: column;
                gap: 0.5rem;
            }

            .search-input {
                width: 100%;
                font-size: 0.85rem;
                padding: 0.6rem 0.8rem;
            }

            .search-btn {
                width: 100%;
                justify-content: center;
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }

            .search-results {
                max-height: 150px;
            }

            .search-result-item {
                padding: 0.6rem 0.8rem;
            }

            .search-result-name {
                font-size: 0.85rem;
            }

            .search-result-address {
                font-size: 0.75rem;
            }

            .workplace-map {
                height: 150px;
                margin-bottom: 0.75rem;
            }

            .map-controls {
                flex-direction: column;
                gap: 0.75rem;
            }

            .coordinate-display {
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
            }

            .coord-item label {
                font-size: 0.75rem;
            }

            .coord-item input {
                padding: 0.35rem;
                font-size: 0.8rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                font-size: 0.85rem;
                margin-bottom: 0.4rem;
            }

            .form-input {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            textarea.form-input {
                min-height: 50px;
            }

            .change-notice {
                padding: 0.6rem;
                font-size: 0.8rem;
                margin-bottom: 0.75rem;
            }

            .modal-footer {
                margin-top: 1rem;
                padding-top: 0.75rem;
                gap: 0.5rem;
                flex-direction: column;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'student_nav.php'; ?>
    <main>
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($student_profile['profile_pic']); ?>" alt="Profile Picture"
                class="profile-picture">
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($student_profile['fullname']); ?></h1>
                <span class="profile-id">ID: <?php echo htmlspecialchars($student_profile['school_id']); ?></span>
                <div class="contact-buttons">
                    <a href="#" class="btn btn-outline" onclick="openEditProfileModal()" style="font-size: 13px;">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="#" class="btn btn-outline" onclick="openWorkplaceModal()" style="font-size: 13px;">
                        <i
                            class="fa-solid fa-location-crosshairs"></i><?php echo $hasWorkplace ? 'Request Change of Workplace' : 'Set Workplace'; ?>
                    </a>
                </div>


            </div>
            <!-- OJT Progress Bar -->
            <div class="ojt-progress">
                <div class="progress-header">
                    <h4>OJT Hours Progress</h4>
                    <span class="progress-percentage"><?php echo $student_profile['ojt_hours']['progress']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill"
                        style="width: <?php echo $student_profile['ojt_hours']['progress']; ?>%;"></div>
                </div>
                <div class="progress-details">
                    <span><?php echo $student_profile['ojt_hours']['completed']; ?> of
                        <?php echo $student_profile['ojt_hours']['total']; ?> hours completed | </span>
                    <span> Last updated: <?php echo $student_profile['ojt_hours']['last_updated']; ?></span>
                </div>
            </div>
        </div>

        <div class="profile-details">
            <div class="detail-card">
                <h3>Personal Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['fullname']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['contact']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Facebook:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['facebook']); ?></span>
                </div>
            </div>

            <div class="detail-card">
                <h3>Academic Information</h3>
                <div class="detail-item">
                    <span class="detail-label">Student ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['school_id']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['department']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Section:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['section']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Year:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($student_profile['year']); ?></span>
                </div>
            </div>

            <div class="detail-card">
                <h3>Instructor</h3>
                <div class="instructor-card">
                    <img src="<?php echo htmlspecialchars($student_profile['instructor_profile']); ?>" alt="Instructor"
                        class="instructor-pic">
                    <div class="instructor-info">
                        <div class="instructor-name"><?php echo htmlspecialchars($student_profile['instructor']); ?>
                        </div>
                        <div class="instructor-role">OJT Instructor</div>
                        <div class="instructor-email"
                            style="color: var(--secondary-text-clr); font-size: 0.85rem; margin-top: 0.25rem;">
                            <i class="fas fa-envelope" style="margin-right: 0.25rem;"></i>
                            <?php echo htmlspecialchars($student_profile['instructor_email']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h3>System Administrator</h3>
                <?php if (!empty($student_profile['admins'])): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                        <?php foreach ($student_profile['admins'] as $admin): ?>
                            <div class="admin-card" style="text-align: center;">
                                <img src="<?php echo htmlspecialchars($admin['profile_pic_path'] ?: '../../storage/images/default_profile.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($admin['full_name']); ?>" class="admin-pic"
                                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 0.5rem;">
                                <div class="admin-name" style="font-weight: 600; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($admin['full_name']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="admin-card">
                        <img src="../../storage/images/default_profile.jpg" alt="Admin" class="admin-pic">
                        <div class="admin-info">
                            <div class="admin-name">System Administrator</div>
                            <div class="admin-role">OJT Chairperson</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div><br>
        <div class="detail-card classmates-section" style="overflow: visible;">
            <h3>Your Classmates</h3>
            <style>
                #sidebar {
                    z-index: 1000;
                }

                @media (max-width: 850px) {
                    .classmates-section {
                        display: none;
                    }
                }

                .classmate-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                    gap: 1.5rem;
                    padding: 0.5rem;
                }

                .classmate-card {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                    cursor: pointer;
                }

                .classmate-pic {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 2px solid transparent;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                }

                .classmate-card:hover .classmate-pic {
                    border-color: var(--accent-clr);
                    transform: scale(1.1);
                }

                .classmate-info {
                    position: absolute;
                    bottom: 110%;
                    left: 50%;
                    transform: translateX(-50%) translateY(10px);
                    background: #2a2b3a;
                    padding: 0.8rem;
                    border-radius: 8px;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
                    text-align: center;
                    width: max-content;
                    max-width: 200px;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    z-index: 100;
                    border: 1px solid var(--line-clr);
                    pointer-events: none;
                }

                .classmate-card:hover .classmate-info {
                    opacity: 1;
                    visibility: visible;
                    transform: translateX(-50%) translateY(0);
                }

                .classmate-info::before {
                    content: '';
                    position: absolute;
                    bottom: -6px;
                    left: 50%;
                    transform: translateX(-50%);
                    border-width: 6px 6px 0 6px;
                    border-style: solid;
                    border-color: #2a2b3a transparent transparent transparent;
                }

                .classmate-name {
                    color: var(--text-clr);
                    font-weight: 600;
                    font-size: 0.9rem;
                    margin-bottom: 0.2rem;
                    display: block;
                }

                .classmate-hours {
                    color: var(--accent-clr);
                    font-size: 0.8rem;
                    font-weight: 500;
                }

                .show-more-btn {
                    grid-column: 1 / -1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    padding: 0.75rem 1.5rem;
                    background: var(--hover-clr);
                    border: 1px solid var(--line-clr);
                    border-radius: 8px;
                    color: var(--text-clr);
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-size: 0.9rem;
                    margin-top: 0.5rem;
                }

                .show-more-btn:hover {
                    background: var(--accent-clr);
                    color: #111;
                    border-color: var(--accent-clr);
                }

                .classmate-card.hidden-classmate {
                    display: none;
                }

                .classmate-card.hidden-classmate.show-all {
                    display: flex;
                }
            </style>
            <div class="classmate-grid">
                <?php if (!empty($student_profile['classmates'])): ?>
                    <?php
                    $totalClassmates = count($student_profile['classmates']);
                    $showLimit = 20;
                    ?>
                    <?php foreach ($student_profile['classmates'] as $index => $classmate): ?>
                        <div class="classmate-card <?php echo $index >= $showLimit ? 'hidden-classmate' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($classmate['profile_pic_path'] ?: '../../storage/images/default_profile.jpg'); ?>"
                                alt="<?php echo htmlspecialchars($classmate['full_name']); ?>" class="classmate-pic">
                            <div class="classmate-info">
                                <span class="classmate-name"><?php echo htmlspecialchars($classmate['full_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($totalClassmates > $showLimit): ?>
                        <button class="show-more-btn" onclick="toggleClassmates(this)">
                            <i class="fas fa-chevron-down"></i>
                            Show More (<?php echo $totalClassmates - $showLimit; ?> more)
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <div
                        style="grid-column: 1 / -1; text-align: center; color: var(--secondary-text-clr); font-style: italic;">
                        No classmates found.
                    </div>
                <?php endif; ?>
            </div>
        </div><br>

        <script>
            function toggleClassmates(btn) {
                const hiddenCards = document.querySelectorAll('.classmate-card.hidden-classmate');
                const isExpanded = btn.classList.contains('expanded');

                hiddenCards.forEach(card => {
                    card.classList.toggle('show-all');
                });

                if (isExpanded) {
                    btn.innerHTML = '<i class="fas fa-chevron-down"></i> Show More (<?php echo isset($totalClassmates) && isset($showLimit) ? $totalClassmates - $showLimit : 0; ?> more)';
                    btn.classList.remove('expanded');
                } else {
                    btn.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
                    btn.classList.add('expanded');
                }
            }
        </script>

    </main>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Profile</h2>
                <button class="close-btn" onclick="closeEditProfileModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="editProfileForm" onsubmit="saveProfile(event)">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                        value="<?php echo htmlspecialchars($student_profile['email']); ?>"
                        placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact" class="form-input"
                        value="<?php echo htmlspecialchars($student_profile['contact']); ?>"
                        placeholder="Enter your contact number">
                </div>

                <div class="form-group">
                    <label class="form-label" for="facebook">Facebook</label>
                    <input type="text" id="facebook" name="facebook" class="form-input"
                        value="<?php echo htmlspecialchars($student_profile['facebook']); ?>"
                        placeholder="Enter your Facebook name">
                </div>

                <input type="hidden" id="latitude" name="latitude" value="">
                <input type="hidden" id="longitude" name="longitude" value="">

                <div class="form-group">
                    <label class="form-label" for="profile_picture">Profile Picture</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="profile_picture" name="profile_picture" class="file-input"
                            accept="image/*" onchange="updateFileName(this)">
                        <label for="profile_picture" class="file-input-label">
                            <i class="fas fa-upload"></i>
                            Choose File
                        </label>
                        <span class="file-name" id="file-name">No file chosen</span>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--line-clr);">
                    <h3 style="color: var(--accent-clr); margin-bottom: 1rem; font-size: 1.1rem;">
                        <i class="fas fa-lock"></i> Change Password
                    </h3>
                    <p style="color: var(--secondary-text-clr); font-size: 0.9rem; margin-bottom: 1rem;">
                        Leave blank if you don't want to change your password
                    </p>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input"
                            placeholder="Enter your current password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input"
                            placeholder="Enter new password (min. 6 characters)">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                            placeholder="Re-enter new password">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeEditProfileModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Workplace Edit Modal -->
    <div id="workplaceModal" class="modal">
        <div class="modal-content workplace-modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="workplaceModalTitle">
                    <?php echo $hasWorkplace ? 'Request Change of Workplace' : 'Set Your Workplace'; ?>
                </h2>
                <button type="button" class="btn btn-outline" onclick="getCurrentLocation()">
                    <i class="fas fa-crosshairs"></i> Use Current Location
                </button>
            </div>

            <form id="workplaceForm" onsubmit="saveWorkplace(event)">
                <?php if ($hasWorkplace): ?>
                    <div class="change-notice">
                        <i class="fas fa-info-circle"></i>
                        <span>You have already set your workplace. Any changes require instructor approval.</span>
                    </div>
                <?php endif; ?>

                <div class="workplace-map-container">
                    <div class="map-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Click to select
                            <?php echo $hasWorkplace ? 'new' : 'your'; ?> Workplace Location
                        </h3>
                    </div>

                    <!-- Location Search -->
                    <div class="location-search-container">
                        <div class="search-input-wrapper">
                            <input type="text" id="locationSearch" class="search-input"
                                placeholder="Search for your workplace location (e.g., company name, address, city)..."
                                autocomplete="off">
                            <button type="button" class="search-btn" onclick="searchLocation()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div id="searchResults" class="search-results"></div>
                    </div>

                    <div id="workplaceMap" class="workplace-map"></div>


                </div>
                <div class="form-group">
                    <input type="text" id="workplace_name" name="workplace_name" class="form-input"
                        placeholder="Host Traning Establishment (Workplace OJT Name)"
                        value="<?php echo htmlspecialchars($student_profile['workplace'] !== 'Not assigned' ? $student_profile['workplace'] : ''); ?>"
                        required>
                </div>
                <div class="form-group">
                    <input type="text" id="position" name="position" class="form-input"
                        placeholder="STUDENT POSITION ON THE HTE"
                        value="<?php echo htmlspecialchars($student_profile['position'] !== 'Intern' ? $student_profile['position'] : ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <input type="text" id="workplace_address" name="workplace_address" class="form-input"
                        placeholder="HTE WORKPLACE ADDRESS"
                        value="<?php echo htmlspecialchars($student_profile['workplace_address'] !== 'Not available' ? $student_profile['workplace_address'] : ''); ?>"
                        required>
                </div>
                <div class="form-group">
                    <input type="text" id="supervisor_name" name="supervisor_name" class="form-input"
                        placeholder="HTE AGENCY HEAD (Supervisor)"
                        value="<?php echo htmlspecialchars($student_profile['supervisor'] !== 'Not assigned' ? $student_profile['supervisor'] : ''); ?>"
                        required>
                </div>
                <div class="form-group">
                    <input type="text" id="supervisor_position" name="supervisor_position" class="form-input"
                        placeholder="HTE AGENCY HEAD JOB POSITION/ DESIGNATION"
                        value="<?php echo htmlspecialchars($student_profile['supervisor_position'] ?? ''); ?>" required>
                </div>


                <label>IMMEDIATE SUPERVISING HEAD INFORMATION <svg xmlns="http://www.w3.org/2000/svg" height="15px"
                        viewBox="0 -960 960 960" width="15px" fill="#e3e3e3">
                        <path
                            d="M478-240q21 0 35.5-14.5T528-290q0-21-14.5-35.5T478-340q-21 0-35.5 14.5T428-290q0 21 14.5 35.5T478-240Zm-36-154h74q0-33 7.5-52t42.5-52q26-26 41-49.5t15-56.5q0-56-41-86t-97-30q-57 0-92.5 30T342-618l66 26q5-18 22.5-39t53.5-21q32 0 48 17.5t16 38.5q0 20-12 37.5T506-526q-44 39-54 59t-10 73Zm38 314q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
                    </svg></label>
                <div class="form-group">
                    <input type="text" id="head_trainee" name="head_trainee" class="form-input"
                        placeholder="NAME OF THE IMMEDIATE SUPERVISING HEAD OF THE TRAINEE IN HTE"
                        value="<?php echo htmlspecialchars($student_profile['head_trainee'] ?? ''); ?>" required>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <div class="form-group">
                        <input type="text" id="head_trainee_position" name="head_trainee_position" class="form-input"
                            placeholder="JOB POSITION"
                            value="<?php echo htmlspecialchars($student_profile['head_trainee_position'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="text" id="head_trainee_contact" name="head_trainee_contact" class="form-input"
                            placeholder="CONTACT NUMBER"
                            value="<?php echo htmlspecialchars($student_profile['head_trainee_contact'] ?? ''); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="text" id="head_trainee_email" name="head_trainee_email" class="form-input"
                            placeholder="EMAIL ADDRESS"
                            value="<?php echo htmlspecialchars($student_profile['head_trainee_email'] ?? ''); ?>"
                            required>
                    </div>
                </div>
                <?php if ($hasWorkplace): ?>
                    <div class="form-group">
                        <label class="form-label" for="change_reason">Reason for Change *</label>
                        <textarea id="change_reason" name="change_reason" class="form-input" rows="4"
                            placeholder="Please explain why you need to change your OJT workplace location..."
                            required></textarea>
                    </div>
                <?php endif; ?>

                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeWorkplaceModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-save" id="workplaceSubmitBtn">
                        <i class="fas fa-save"></i> <?php echo $hasWorkplace ? 'Submit Request' : 'Set Workplace'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Workplace Modal Functions
        let selectedLocation = null;
        let map = null;
        let marker = null;
        let radiusCircle = null; // 40-meter radius circle

        function openWorkplaceModal() {
            document.getElementById('workplaceModal').classList.add('show');
            document.body.style.overflow = 'hidden';

            // Initialize map after modal is visible
            setTimeout(initializeMap, 100);
        }

        function closeWorkplaceModal() {
            document.getElementById('workplaceModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Edit Actual Workplace Functions
        let editMap = null;
        let editMarker = null;
        let editSelectedLocation = null;
        let editRadiusCircle = null; // 40-meter radius circle for edit map

        function openEditWorkplaceModal() {
            document.getElementById('editActualWorkplaceModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            setTimeout(initEditWorkplaceMap, 100);
        }

        function initEditWorkplaceMap() {
            if (editMap) {
                editMap.invalidateSize();
                return;
            }

            // Get current location from PHP vars
            // Using loose values if PHP var is null/empty
            const lat = <?php echo json_encode(floatval($student_profile['latitude'] ?: 10.7323)); ?>;
            const lng = <?php echo json_encode(floatval($student_profile['longitude'] ?: 122.9669)); ?>;
            const hasLocation = <?php echo json_encode(!empty($student_profile['latitude'])); ?>;

            editMap = L.map('editActualWorkplaceMap').setView([lat, lng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(editMap);

            if (hasLocation) {
                editMarker = L.marker([lat, lng]).addTo(editMap);
                editSelectedLocation = { lat: lat, lng: lng };
                editMap.setView([lat, lng], 15);
            }

            editMap.on('click', function (e) {
                const newLat = e.latlng.lat.toFixed(6);
                const newLng = e.latlng.lng.toFixed(6);

                if (editMarker) {
                    editMarker.setLatLng([newLat, newLng]);
                } else {
                    editMarker = L.marker([newLat, newLng]).addTo(editMap);
                }

                // Add or update 40-meter radius circle
                if (editRadiusCircle) {
                    editRadiusCircle.setLatLng([newLat, newLng]);
                } else {
                    editRadiusCircle = L.circle([newLat, newLng], {
                        radius: 40, // 40 meters
                        color: '#1ad21c',
                        fillColor: '#1ad21c',
                        fillOpacity: 0.2,
                        weight: 2
                    }).addTo(editMap);

                    // Add tooltip to explain the radius
                    editRadiusCircle.bindPopup('<b>40-meter Radius</b><br>Attendance tracking boundary');
                }

                editSelectedLocation = { lat: newLat, lng: newLng };
            });
        }

        function closeEditActualWorkplaceModal() {
            document.getElementById('editActualWorkplaceModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function saveActualWorkplace(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'update_actual_workplace');

            if (editSelectedLocation) {
                formData.append('latitude', editSelectedLocation.lat);
                formData.append('longitude', editSelectedLocation.lng);
            }

            fetch('student_profile.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Workplace updated!', 'success');
                        closeEditActualWorkplaceModal();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.error || 'Update failed', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showNotification('An error occurred', 'error');
                });
        }

        function initializeMap() {
            const mapElement = document.getElementById('workplaceMap');
            if (!mapElement) return;

            if (!map) {
                // Default location (e.g., Talisay, Negros Occidental)
                const defaultLat = 10.7323;
                const defaultLng = 122.9669;

                map = L.map('workplaceMap').setView([defaultLat, defaultLng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                map.on('click', function (e) {
                    setMapLocation(e.latlng.lat, e.latlng.lng);
                });
            } else {
                map.invalidateSize();
            }
        }

        function setMapLocation(lat, lng) {
            lat = parseFloat(lat);
            lng = parseFloat(lng);
            selectedLocation = { lat: lat, lng: lng };

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }

            // Add or update 40-meter radius circle
            if (radiusCircle) {
                radiusCircle.setLatLng([lat, lng]);
            } else {
                radiusCircle = L.circle([lat, lng], {
                    radius: 40, // 40 meters
                    color: '#1ad21c', // Green color matching the theme
                    fillColor: '#1ad21c',
                    fillOpacity: 0.2,
                    weight: 2
                }).addTo(map);

                // Add tooltip to explain the radius
                radiusCircle.bindPopup('<b>40-meter Radius</b><br>Attendance tracking boundary');
            }

            // Pan to location
            map.flyTo([lat, lng], 15);
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setMapLocation(lat, lng);
                        showNotification('Location detected successfully!', 'success');
                    },
                    function (error) {
                        let errorMessage = 'Unable to get your location.';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location access denied. Please enable location services.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out.';
                                break;
                        }
                        showNotification(errorMessage, 'error');
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            } else {
                showNotification('Geolocation is not supported by this browser.', 'error');
            }
        }

        // Location Search Functionality
        function searchLocation() {
            const searchInput = document.getElementById('locationSearch');
            const searchQuery = searchInput.value.trim();
            const searchResults = document.getElementById('searchResults');
            const searchBtn = document.querySelector('.search-btn');

            if (!searchQuery) {
                showNotification('Please enter a location to search', 'error');
                return;
            }

            // Show loading state
            searchBtn.disabled = true;
            searchResults.innerHTML = '<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.classList.add('show');

            // Use our server-side proxy to avoid CORS issues
            const url = `geocode_proxy.php?q=${encodeURIComponent(searchQuery)}&limit=5&countrycodes=ph`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    searchBtn.disabled = false;

                    // Check for error response
                    if (data.error) {
                        searchResults.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-triangle"></i> ' + data.error + '</div>';
                        return;
                    }

                    if (!Array.isArray(data) || data.length === 0) {
                        searchResults.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-circle"></i> No results found. Try a different search term.</div>';
                        return;
                    }

                    // Display results
                    let resultsHTML = '';
                    data.forEach((result, index) => {
                        // Escape quotes in display name for onclick attribute
                        const escapedName = result.display_name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        resultsHTML += `
                            <div class="search-result-item" onclick="selectSearchResult(${result.lat}, ${result.lon}, '${escapedName}')">
                                <div class="search-result-name">${result.display_name.split(',')[0]}</div>
                                <div class="search-result-address">${result.display_name}</div>
                            </div>
                        `;
                    });

                    searchResults.innerHTML = resultsHTML;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchBtn.disabled = false;
                    searchResults.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-triangle"></i> Search failed. Please try again.</div>';
                    showNotification('Failed to search location. Please try again.', 'error');
                });
        }

        function selectSearchResult(lat, lng, displayName) {
            // Set the location on the map
            setMapLocation(lat, lng);

            // Hide search results
            const searchResults = document.getElementById('searchResults');
            searchResults.classList.remove('show');

            // Update search input with selected location
            const searchInput = document.getElementById('locationSearch');
            searchInput.value = displayName;

            // Show success notification
            showNotification('Location selected successfully!', 'success');
        }

        // Allow search on Enter key
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('locationSearch');
            if (searchInput) {
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchLocation();
                    }
                });

                // Hide search results when clicking outside
                document.addEventListener('click', function (e) {
                    const searchContainer = document.querySelector('.location-search-container');
                    if (searchContainer && !searchContainer.contains(e.target)) {
                        document.getElementById('searchResults').classList.remove('show');
                    }
                });
            }
        });

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--accent-clr)' : '#dc3545'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                max-width: 300px;
            `;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function saveWorkplace(event) {
            event.preventDefault();

            if (!selectedLocation) {
                showNotification('Please select a location on the map', 'error');
                return;
            }

            // Get form data
            const formData = new FormData(event.target);
            formData.append('action', 'update_workplace');
            formData.append('latitude', selectedLocation.lat);
            formData.append('longitude', selectedLocation.lng);



            // Send data to server via AJAX
            fetch('student_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    // DEBUG: Log response status and headers
                    console.log('Response Status:', response.status);
                    console.log('Response OK:', response.ok);
                    console.log('Response Headers:');
                    response.headers.forEach((value, key) => {
                        console.log(`  ${key}: ${value}`);
                    });

                    // Clone response to read text first for debugging
                    return response.clone().text().then(rawText => {
                        console.log('Raw Response Text:', rawText);
                        // Try to parse as JSON
                        try {
                            return JSON.parse(rawText);
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Response was not valid JSON. Raw content above.');
                            throw new Error('Server did not return valid JSON. Check console for raw response.');
                        }
                    });
                })
                .then(result => {
                    console.log('Parsed Result:', result);
                    if (result.success) {
                        showNotification(result.message || 'Success!', 'success');
                        closeWorkplaceModal();
                        // Reset form
                        document.getElementById('workplaceForm').reset();
                        selectedLocation = null;
                        if (marker) {
                            marker.remove();
                            marker = null;
                        }
                        // Reload page after 1.5 seconds to show updated data
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        console.log('Server returned error:', result.error);
                        showNotification(result.error || 'Failed to submit request', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch/Parse Error:', error);
                    showNotification('An error occurred while submitting request', 'error');
                });
        }

        // Add notification animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Close modal when clicking outside
        document.getElementById('workplaceModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeWorkplaceModal();
            }
        });

        // Modal functionality
        function openEditProfileModal() {
            document.getElementById('editProfileModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        }

        function saveProfile(event) {
            event.preventDefault();

            // Get form data
            const formData = new FormData(event.target);

            // Check if password fields are filled
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

            // If any password field is filled, validate all password fields
            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword || !newPassword || !confirmPassword) {
                    showNotification('Please fill all password fields or leave them all empty', 'error');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match', 'error');
                    return;
                }

                if (newPassword.length < 6) {
                    showNotification('New password must be at least 6 characters long', 'error');
                    return;
                }

                // First update the password
                const passwordData = new FormData();
                passwordData.append('action', 'change_password');
                passwordData.append('current_password', currentPassword);
                passwordData.append('new_password', newPassword);
                passwordData.append('confirm_password', confirmPassword);

                fetch('student_profile.php', {
                    method: 'POST',
                    body: passwordData
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // Password changed successfully, now update profile
                            updateProfileData(formData);
                        } else {
                            showNotification(result.error || 'Failed to change password', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while changing password', 'error');
                    });
            } else {
                // No password change, just update profile
                updateProfileData(formData);
            }
        }

        function updateProfileData(formData) {
            formData.append('action', 'update_profile');

            // Send data to server via AJAX
            fetch('student_profile.php', {
                method: 'POST',
                body: formData // Send formData directly to support file uploads
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showNotification('Profile updated successfully!', 'success');
                        closeEditProfileModal();
                        // Reload page to show updated data
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(result.error || 'Failed to update profile', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while updating profile', 'error');
                });
        }

        // Close modal when clicking outside
        document.getElementById('editProfileModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeEditProfileModal();
            }
        });

        // Add any interactive functionality here
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Profile page loaded');

            // Initialize Display Map
            const lat = <?php echo json_encode($student_profile['latitude'] ?? null); ?>;
            const lng = <?php echo json_encode($student_profile['longitude'] ?? null); ?>;
            const workplaceName = <?php echo json_encode($student_profile['workplace'] ?? 'Workplace'); ?>;

            if (lat && lng && document.getElementById('displayWorkplaceMap')) {
                const displayMap = L.map('displayWorkplaceMap').setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(displayMap);

                L.marker([lat, lng]).addTo(displayMap)
                    .bindPopup("<b>" + workplaceName + "</b>");

                // Ensure map renders correctly
                setTimeout(() => displayMap.invalidateSize(), 500);
            } else if (document.getElementById('displayWorkplaceMap')) {
                document.getElementById('displayWorkplaceMap').innerHTML =
                    '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#666; background:#f0f0f0; border-radius:8px;">Location not set</div>';
            }
        });
    </script>
</body>

</html>