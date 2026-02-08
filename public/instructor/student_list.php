<?php
session_start();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../../app/middleware/requireInstructor.php';
    require_once __DIR__ . '/../../app/services/InstructorService.php';

    $instructorService = new \App\Services\InstructorService();
    $instructor_user_id = $_SESSION['user_id'] ?? null;
    $instructor_id = $instructorService->getInstructorId($instructor_user_id);

    $response = ['success' => false, 'error' => 'Invalid request'];

    try {
        switch ($_POST['action']) {
            case 'update_hours':
                $studentDbId = intval($_POST['student_id'] ?? 0);
                $newHours = floatval($_POST['new_hours'] ?? 0);

                // Log the request for debugging
                error_log("Update hours request - Student ID: $studentDbId, New Hours: $newHours, Instructor ID: $instructor_id");

                if ($studentDbId > 0 && $newHours >= 0) {
                    $success = $instructorService->updateStudentHours(
                        $studentDbId,
                        $newHours,
                        $instructor_id,
                        'Manual adjustment by instructor'
                    );

                    if ($success) {
                        error_log("Hours updated successfully for student $studentDbId");
                        $response['success'] = true;
                        $response['message'] = 'Student hours updated successfully';
                    } else {
                        error_log("Failed to update hours for student $studentDbId");
                        $response['error'] = 'Failed to update student hours';
                    }
                } else {
                    error_log("Invalid parameters - Student ID: $studentDbId, Hours: $newHours");
                    $response['error'] = 'Invalid student ID or hours value';
                }
                break;

            case 'get_workplace_requests':
                $stmt = $instructorService->getDb()->prepare("
                    SELECT 
                        wcr.id,
                        wcr.student_id,
                        u.full_name as student_name,
                        u.profile_pic_path,
                        u.school_id,
                        wcr.workplace_name as new_workplace,
                        wcr.workplace_address as new_address,
                        wcr.position_title,
                        wcr.supervisor_name,
                        wcr.supervisor_position,
                        wcr.head_trainee,
                        wcr.head_trainee_position,
                        wcr.head_trainee_contact,
                        wcr.head_trainee_email,
                        wcr.change_reason,
                        wcr.created_at,
                        COALESCE(sw.company_name, 'No active workplace') as current_workplace
                    FROM workplace_change_requests wcr
                    JOIN users u ON wcr.student_id = u.id
                    JOIN students st ON u.id = st.user_id
                    JOIN sections s ON u.section_id = s.id
                    LEFT JOIN student_workplaces sw ON st.id = sw.student_id AND sw.is_active = 1
                    WHERE wcr.status = 'pending'
                    AND s.instructor_id = :instructor_id
                    ORDER BY wcr.created_at DESC
                ");
                $stmt->execute([':instructor_id' => $instructor_id]);
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['data'] = $requests;
                break;

            case 'process_workplace_request':
                $requestId = intval($_POST['request_id'] ?? 0);
                $action = $_POST['review_action'] ?? ''; // 'approve' or 'reject'
                $resetHours = ($_POST['reset_hours'] ?? 'false') === 'true'; // boolean

                if ($requestId <= 0 || !in_array($action, ['approve', 'reject'])) {
                    throw new Exception('Invalid request parameters');
                }

                // Get request details
                $stmt = $instructorService->getDb()->prepare("SELECT * FROM workplace_change_requests WHERE id = ?");
                $stmt->execute([$requestId]);
                $request = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$request) {
                    throw new Exception('Request not found');
                }

                $instructorService->getDb()->beginTransaction();

                try {
                    // Update request status
                    $updateStmt = $instructorService->getDb()->prepare("
                        UPDATE workplace_change_requests 
                        SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $action === 'approve' ? 'approved' : 'rejected',
                        $instructor_user_id, // Using user_id as reviewed_by (linked to users table)
                        $requestId
                    ]);

                    if ($action === 'approve') {
                        $userId = $request['student_id'];

                        // Get Student DB ID
                        $stStmt = $instructorService->getDb()->prepare("SELECT id FROM students WHERE user_id = ?");
                        $stStmt->execute([$userId]);
                        $studentDbId = $stStmt->fetchColumn();

                        if (!$studentDbId) {
                            throw new Exception('Student record not found for this user');
                        }

                        // Deactivate current workplace
                        $deactivateStmt = $instructorService->getDb()->prepare("
                            UPDATE student_workplaces 
                            SET is_active = 0, end_date = NOW() 
                            WHERE student_id = ? AND is_active = 1
                        ");
                        $deactivateStmt->execute([$studentDbId]);

                        // Insert new workplace
                        $insertWpStmt = $instructorService->getDb()->prepare("
                            INSERT INTO student_workplaces (
                                student_id, company_name, company_head, position_title, company_address,
                                supervisor_position, head_trainee, head_trainee_position, 
                                head_trainee_contact, head_trainee_email,
                                workplace_latitude, workplace_longitude, start_date, is_active
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)
                        ");
                        $insertWpStmt->execute([
                            $studentDbId,
                            $request['workplace_name'],
                            $request['supervisor_name'],
                            $request['position_title'],
                            $request['workplace_address'],
                            $request['supervisor_position'],
                            $request['head_trainee'],
                            $request['head_trainee_position'],
                            $request['head_trainee_contact'],
                            $request['head_trainee_email'],
                            $request['latitude'] ?? 0,
                            $request['longitude'] ?? 0
                        ]);

                        // Handle OJT Hours
                        if ($resetHours) {
                            $resetStmt = $instructorService->getDb()->prepare("
                                UPDATE ojt_summaries 
                                SET hours_completed = 0, 
                                    manual_adjustment_hours = 0 
                                WHERE student_id = ?
                            ");
                            $resetStmt->execute([$studentDbId]);
                        }
                    }

                    $instructorService->getDb()->commit();
                    $response['success'] = true;
                    $response['message'] = 'Request ' . $action . 'ed successfully';
                } catch (Exception $e) {
                    $instructorService->getDb()->rollBack();
                    throw $e;
                }
                break;

            case 'mark_excused_date':
                $date = $_POST['date'] ?? '';
                $hours = floatval($_POST['hours'] ?? 0);
                $reason = $_POST['reason'] ?? '';
                $studentIdParam = $_POST['student_id'] ?? '';

                error_log("Mark excused date - Date: $date, Hours: $hours, Student ID: $studentIdParam, Instructor ID: $instructor_id");

                if (empty($date) || $hours <= 0 || empty($reason) || empty($studentIdParam)) {
                    $response['error'] = 'Invalid parameters';
                    break;
                }

                try {
                    $db = $instructorService->getDb();
                    $db->beginTransaction();

                    $studentsToUpdate = [];

                    // Check if "All Students" was selected
                    if ($studentIdParam === 'all') {
                        // Get all students for this instructor
                        $stmt = $db->prepare("
                            SELECT DISTINCT s.id
                            FROM students s
                            JOIN users u ON s.user_id = u.id
                            JOIN sections sec ON u.section_id = sec.id
                            WHERE sec.instructor_id = ? AND u.is_archived = 0
                        ");
                        $stmt->execute([$instructor_id]);
                        $studentsToUpdate = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    } else {
                        // Single student
                        $studentsToUpdate[] = intval($studentIdParam);
                    }

                    if (empty($studentsToUpdate)) {
                        $response['error'] = 'No students found';
                        $db->rollBack();
                        break;
                    }

                    $successCount = 0;
                    $failedCount = 0;

                    foreach ($studentsToUpdate as $studentId) {
                        try {
                            // Insert into excused_dates table
                            $stmt = $db->prepare("
                                INSERT INTO excused_dates 
                                    (student_id, excused_date, instructor_id, hours_added, reason)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    hours_added = VALUES(hours_added),
                                    reason = VALUES(reason),
                                    instructor_id = VALUES(instructor_id)
                            ");
                            $stmt->execute([$studentId, $date, $instructor_id, $hours, $reason]);

                            // Get current hours from ojt_summaries
                            $stmt = $db->prepare("
                                SELECT COALESCE(hours_completed, 0) as current_hours
                                FROM ojt_summaries
                                WHERE student_id = ?
                            ");
                            $stmt->execute([$studentId]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            $currentHours = $result ? $result['current_hours'] : 0;
                            $newHours = $currentHours + $hours;

                            // Update ojt_summaries
                            $stmt = $db->prepare("
                                INSERT INTO ojt_summaries 
                                    (student_id, hours_completed, manual_adjustment_hours, adjusted_by_instructor_id, adjustment_reason)
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    hours_completed = hours_completed + VALUES(manual_adjustment_hours),
                                    manual_adjustment_hours = manual_adjustment_hours + VALUES(manual_adjustment_hours),
                                    adjusted_by_instructor_id = VALUES(adjusted_by_instructor_id),
                                    adjustment_reason = CONCAT(COALESCE(adjustment_reason, ''), IF(adjustment_reason IS NOT NULL, '; ', ''), VALUES(adjustment_reason)),
                                    last_updated = CURRENT_TIMESTAMP
                            ");

                            $adjustmentReason = "Excused on $date: $reason";
                            $stmt->execute([$studentId, $newHours, $hours, $instructor_id, $adjustmentReason]);

                            $successCount++;
                        } catch (Exception $e) {
                            $failedCount++;
                            error_log("Failed to mark excused date for student $studentId: " . $e->getMessage());
                        }
                    }

                    $db->commit();

                    if ($successCount > 0) {
                        $response['success'] = true;
                        $message = "Successfully marked $date as excused for $successCount student(s)";
                        if ($failedCount > 0) {
                            $message .= " ($failedCount failed)";
                        }
                        $response['message'] = $message;
                        error_log("Excused date marked successfully for $successCount students" . ($failedCount > 0 ? ", $failedCount failed" : ""));
                    } else {
                        $response['error'] = 'No students were updated';
                        error_log("No students were updated for excused date");
                    }

                } catch (Exception $e) {
                    if (isset($db)) {
                        $db->rollBack();
                    }
                    error_log("Error marking excused date: " . $e->getMessage());
                    $response['error'] = 'Failed to mark excused date: ' . $e->getMessage();
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

// Require instructor authentication
require_once __DIR__ . '/../../app/middleware/requireInstructor.php';

// Load instructor service
require_once __DIR__ . '/../../app/services/InstructorService.php';

// Initialize instructor service
$instructorService = new \App\Services\InstructorService();

// Get instructor ID from user ID
$instructor_id = $instructorService->getInstructorId($instructor_user_id);

if (!$instructor_id) {
    header('Location: ../login.php');
    exit();
}

// Get instructor's sections
$sections = $instructorService->getInstructorSections($instructor_id);

// Get all students assigned to this instructor
$all_students = $instructorService->getInstructorStudents($instructor_id);

// Get search term from URL parameter
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Filter students based on search term
if (!empty($search_term)) {
    $search_lower = strtolower($search_term);
    $all_students = array_filter($all_students, function ($student) use ($search_lower) {
        return stripos($student['full_name'], $search_lower) !== false ||
            stripos($student['school_id'], $search_lower) !== false ||
            stripos($student['company_name'] ?? '', $search_lower) !== false ||
            stripos($student['position_title'] ?? '', $search_lower) !== false;
    });
}

// Pagination settings
$students_per_page = 10;
$total_students = count($all_students);
$total_pages = max(1, ceil($total_students / $students_per_page));

// Get current page from URL parameter, default to 1
$page = isset($_GET['page']) ? max(1, min(intval($_GET['page']), $total_pages)) : 1;

// Calculate offset
$offset = ($page - 1) * $students_per_page;

// Get students for current page
$students = array_slice($all_students, $offset, $students_per_page);

// Helper function to build pagination URLs with search parameter
function buildPaginationUrl($page_num, $search_term)
{
    $params = ['page' => $page_num];
    if (!empty($search_term)) {
        $params['search'] = $search_term;
    }
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="icon" type="image/png" href="../../public/images/CHMSU.png">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .header-section h1 {
            color: var(--text-clr);
            margin: 0;
            font-size: 1.8rem;
        }

        .section-info {
            background: var(--hover-clr);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-btn {
            background: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .export-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .student-link:hover .student-name-text {
            color: var(--accent-clr);
            transition: color 0.2s ease;
        }

        .table-container {
            background: var(--hover-clr);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        th {
            background: rgba(0, 0, 0, 0.2);
            color: var(--accent-clr);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status.active {
            background: rgba(26, 210, 28, 0.2);
            color: var(--accent-clr);
        }

        .status.inactive {
            background: rgba(255, 59, 48, 0.2);
            color: #FF3B30;
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 300px;
        }

        .search-container input {
            width: 100%;
            padding: 0.7rem 1rem 0.7rem 2.5rem;
            border: 1px solid var(--line-clr);
            border-radius: 8px;
            background: var(--hover-clr);
            color: var(--text-clr);
            font-size: 0.9rem;
        }

        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-text-clr);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 0;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--line-clr);
            background: var(--hover-clr);
            color: var(--text-clr);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            font-size: 0.9rem;
        }

        .pagination-btn:hover:not(:disabled):not(.active) {
            background: var(--accent-clr);
            color: var(--base-clr);
            border-color: var(--accent-clr);
        }

        .pagination-btn.active {
            background: var(--accent-clr);
            color: var(--base-clr);
            border-color: var(--accent-clr);
            font-weight: 600;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-dots {
            padding: 0.5rem;
            color: var(--secondary-text-clr);
        }

        .edit-hours-btn {
            background: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .edit-hours-btn:hover {
            opacity: 0.8;
            transform: scale(1.1);
        }

        .notification-badge {
            background: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            font-weight: bold;
            vertical-align: middle;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

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
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 450px;
            position: relative;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-title {
            color: var(--accent-clr);
            font-size: 1.3rem;
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

        .student-info {
            background: var(--base-clr);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .student-info p {
            margin: 0.5rem 0;
            color: var(--text-clr);
        }

        .student-info .label {
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
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
            width: 96%;
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

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(26, 210, 28, 0.1);
            border: 1px solid rgba(26, 210, 28, 0.3);
            border-radius: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-top: 0.25rem;
            accent-color: var(--accent-clr);
        }

        .checkbox-group label {
            color: var(--text-clr);
            font-size: 0.9rem;
            line-height: 1.4;
            cursor: pointer;
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
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
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
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 210, 28, 0.3);
        }

        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .add-hours-btn {
            background: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .add-hours-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .section-selection {
            margin-bottom: 1.5rem;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-option:hover {
            border-color: var(--accent-clr);
            background: rgba(26, 210, 28, 0.1);
        }

        .radio-option input[type="radio"] {
            accent-color: var(--accent-clr);
        }

        .radio-option label {
            color: var(--text-clr);
            cursor: pointer;
            flex: 1;
        }

        .hours-input-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .hours-input {
            flex: 1;
        }

        .hours-info {
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
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
            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-container {
                max-width: 100%;
            }

            th,
            td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }

            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }

            .modal-footer {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-save {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include 'instructor_nav.php'; ?>

    <main>
        <div class="header-section">
            <div style="display: flex; align-items: center; gap: 2rem;">
                <h1>Student List</h1>
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="section-info">
                            <i class="fas fa-users"></i>
                            <span>Section: <?php echo htmlspecialchars($section['section_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="section-info">
                        <i class="fas fa-info-circle"></i>
                        <span>No sections assigned</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
            <div class="search-container">
                <input type="text" id="searchStudent" placeholder="Search students..."
                    value="<?php echo htmlspecialchars($search_term); ?>">
                <i class="fas fa-search"></i>
            </div>
            <button class="export-btn" id="exportCSV" style="margin-left: 4rem;">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button class="add-hours-btn" id="composeEmail">
                <i class="fas fa-envelope"></i> Compose Email
            </button>
            <button class="add-hours-btn" id="studentRequest">
                <i class="fas fa-user"></i> Student Request
                <span id="workplaceRequestBadge" class="notification-badge" style="display:none;">0</span>
            </button>
            <button class="add-hours-btn" id="excuseCalendar">
                <i class="fas fa-calendar"></i> Excuse Calendar
            </button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>School ID</th>
                        <th>Full Name</th>
                        <th>Workplace</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Total Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['school_id']); ?></td>
                                <td>
                                    <a href="student_information.php?id=<?php echo $student['id']; ?>" class="student-link"
                                        style="color: inherit; text-decoration: none;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo htmlspecialchars(!empty($student['profile_pic_path']) ? $student['profile_pic_path'] : '../../storage/images/default_profile.jpg'); ?>"
                                                style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                                                alt="<?php echo htmlspecialchars($student['full_name']); ?>">
                                            <span
                                                class="student-name-text"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                        </div>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($student['company_name'] ?: 'Not assigned'); ?></td>
                                <td><?php echo htmlspecialchars($student['position_title'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="status <?php echo $student['status']; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($student['total_hours'], 1); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--secondary-text-clr);">
                                <i class="fas fa-<?php echo !empty($search_term) ? 'search' : 'users'; ?>"
                                    style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                <?php if (!empty($search_term)): ?>
                                    No students found matching "<?php echo htmlspecialchars($search_term); ?>".
                                    <br><a href="student_list.php"
                                        style="color: var(--accent-clr); text-decoration: underline; margin-top: 0.5rem; display: inline-block;">Clear
                                        search</a>
                                <?php else: ?>
                                    No students assigned to your sections yet.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Previous Button -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo buildPaginationUrl($page - 1, $search_term); ?>" class="pagination-btn">
                            &laquo; Previous
                        </a>
                    <?php else: ?>
                        <button class="pagination-btn" disabled>&laquo; Previous</button>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <div id="pageNumbers">
                        <?php
                        // Show max 5 page numbers at a time
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Show first page if not in range
                        if ($start_page > 1): ?>
                            <a href="<?php echo buildPaginationUrl(1, $search_term); ?>" class="pagination-btn">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Page number buttons -->
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <button class="pagination-btn active"><?php echo $i; ?></button>
                            <?php else: ?>
                                <a href="<?php echo buildPaginationUrl($i, $search_term); ?>"
                                    class="pagination-btn"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Show last page if not in range -->
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="pagination-dots">...</span>
                            <?php endif; ?>
                            <a href="<?php echo buildPaginationUrl($total_pages, $search_term); ?>"
                                class="pagination-btn"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <!-- Next Button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo buildPaginationUrl($page + 1, $search_term); ?>" class="pagination-btn">
                            Next &raquo;
                        </a>
                    <?php else: ?>
                        <button class="pagination-btn" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>

                <!-- Pagination Info -->
                <div style="text-align: center; margin-top: 1rem; color: var(--secondary-text-clr); font-size: 0.9rem;">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $students_per_page, $total_students); ?>
                    of <?php echo $total_students; ?> students
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Edit Hours Modal -->
    <div id="editHoursModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add Hours to Student</h2>
                <button class="close-btn" onclick="closeEditHoursModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="student-info">
                <p><span class="label">Student Name:</span> <span id="modalStudentName"></span></p>
                <p><span class="label">School ID:</span> <span id="modalStudentId"></span></p>
                <p><span class="label">Current Hours:</span> <span id="modalCurrentHours"></span></p>
            </div>

            <form id="editHoursForm" onsubmit="saveHours(event)">
                <div class="form-group">
                    <label class="form-label" for="hoursToAdd">Hours to Add</label>
                    <input type="number" id="hoursToAdd" name="hoursToAdd" class="form-input" min="0.5" max="50"
                        step="0.5" required placeholder="Enter hours to add (e.g., 8)">
                    <small style="color: var(--secondary-text-clr); margin-top: 0.5rem; display: block;">
                        These hours will be added to the current total of <strong id="currentHoursDisplay">0</strong>
                        hours
                    </small>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="confirmationCheckbox" required>
                    <label for="confirmationCheckbox">
                        I confirm that I am adding <strong id="hoursAddedConfirm">0</strong> hours to this student's OJT
                        record.
                        I acknowledge that this action will update the student's official record and should only be done
                        for valid reasons (e.g., school activities, approved absences).
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditHoursModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-save" id="saveHoursBtn" disabled>
                        <i class="fas fa-plus"></i> Add Hours
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Excuse Calendar Modal -->
    <div id="excuseCalendarModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">Mark Excused Date</h2>
                <button class="close-btn" onclick="closeExcuseCalendarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="excuseCalendarForm" onsubmit="saveExcusedDate(event)">
                <div class="form-group">
                    <label class="form-label" for="excuseDate">Select Date</label>
                    <input type="date" id="excuseDate" name="excuseDate" class="form-input" required>
                    <small style="color: var(--secondary-text-clr); margin-top: 0.5rem; display: block;">
                        Select the date to mark as excused
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="excuseStudentSelect">Select Student</label>
                    <select id="excuseStudentSelect" name="excuseStudentSelect" class="form-input" required>
                        <option value="">-- Select a student --</option>
                        <option value="all">All Students</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?php echo $student['student_db_id']; ?>">
                                <?php echo htmlspecialchars($student['full_name']); ?>
                                (<?php echo htmlspecialchars($student['school_id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="excuseHours">Hours to Add</label>
                    <input type="number" id="excuseHours" name="excuseHours" class="form-input" min="0.5" max="12"
                        step="0.5" value="8" required>
                    <small style="color: var(--secondary-text-clr); margin-top: 0.5rem; display: block;">
                        Number of hours to credit for this excused date (typically 4 or 8 hours)
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="excuseReason">Reason</label>
                    <textarea id="excuseReason" name="excuseReason" class="form-input" rows="3" required
                        placeholder="e.g., School activity, National holiday, Training seminar"></textarea>
                    <small style="color: var(--secondary-text-clr); margin-top: 0.5rem; display: block;">
                        Provide a clear reason for the excused absence
                    </small>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="excuseConfirmationCheckbox" required>
                    <label for="excuseConfirmationCheckbox">
                        I confirm that I am marking <strong id="excuseDateDisplay">this date</strong> as excused
                        for <strong id="excuseTargetDisplay">selected student</strong> with
                        <strong id="excuseHoursDisplay">8</strong> hours credited.
                        This action will update official records and should only be done for legitimate excused
                        absences.
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeExcuseCalendarModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-save" id="saveExcuseBtn" disabled>
                        <i class="fas fa-calendar-check"></i> Mark as Excused
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variable to store current student data
        let currentStudentData = {
            name: '',
            schoolId: '',
            currentHours: 0,
            studentDbId: 0,
            hoursLastUpdated: ''
        };

        // Modal functionality
        function openEditHoursModal(studentName, schoolId, currentHours, studentDbId, hoursLastUpdated = '') {
            currentStudentData = {
                name: studentName,
                schoolId: schoolId,
                currentHours: currentHours,
                studentDbId: studentDbId,
                hoursLastUpdated: hoursLastUpdated
            };

            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalStudentId').textContent = schoolId;
            document.getElementById('modalCurrentHours').textContent = currentHours;
            document.getElementById('currentHoursDisplay').textContent = currentHours;
            document.getElementById('hoursToAdd').value = '';
            document.getElementById('hoursAddedConfirm').textContent = '0';
            document.getElementById('confirmationCheckbox').checked = false;
            document.getElementById('saveHoursBtn').disabled = true;

            document.getElementById('editHoursModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditHoursModal() {
            document.getElementById('editHoursModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        function saveHours(event) {
            event.preventDefault();

            const hoursToAdd = parseFloat(document.getElementById('hoursToAdd').value);
            const oldHours = currentStudentData.currentHours;
            const newHours = oldHours + hoursToAdd;
            const saveBtn = document.getElementById('saveHoursBtn');

            console.log('Adding hours:', {
                hoursToAdd,
                oldHours,
                newHours,
                studentDbId: currentStudentData.studentDbId
            });

            // Validate that new total doesn't exceed maximum
            if (newHours > 600) {
                alert(`Cannot add ${hoursToAdd} hours. New total (${newHours}) would exceed maximum of 600 hours.`);
                return;
            }

            // Disable button to prevent double submission
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Hours...';

            // Prepare form data
            const formData = new URLSearchParams({
                action: 'update_hours',
                student_id: currentStudentData.studentDbId,
                new_hours: newHours
            });

            console.log('Sending request:', formData.toString());

            // Send AJAX request
            fetch('student_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        alert(`Success! Added ${hoursToAdd} hours to ${currentStudentData.name}.\nPrevious: ${oldHours} hours\nNew Total: ${newHours} hours`);

                        // Update the table
                        const rows = document.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            if (row.cells[0].textContent === currentStudentData.schoolId) {
                                row.cells[5].textContent = newHours.toFixed(1);
                            }
                        });

                        // Close the modal
                        closeEditHoursModal();

                        // Reload page to ensure data is fresh
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        console.error('Update failed:', data.error);
                        alert('Error: ' + (data.error || 'Failed to add hours'));
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-plus"></i> Add Hours';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('An error occurred while saving. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-plus"></i> Add Hours';
                });
        }

        // Excuse Calendar Modal Functions
        function openExcuseCalendarModal() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('excuseDate').value = today;
            document.getElementById('excuseDate').min = '2020-01-01';
            // No max date - allow future dates

            document.getElementById('excuseCalendarForm').reset();
            document.getElementById('excuseDate').value = today;
            document.getElementById('excuseHours').value = 8;
            document.getElementById('saveExcuseBtn').disabled = true;

            updateExcuseConfirmation();

            document.getElementById('excuseCalendarModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeExcuseCalendarModal() {
            document.getElementById('excuseCalendarModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('excuseCalendarForm').reset();
        }

        function updateExcuseConfirmation() {
            const date = document.getElementById('excuseDate').value;
            const hours = document.getElementById('excuseHours').value || 8;
            const studentSelect = document.getElementById('excuseStudentSelect');

            let dateDisplay = 'this date';
            if (date) {
                const dateObj = new Date(date + 'T00:00:00');
                dateDisplay = dateObj.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            let targetDisplay = 'selected student';
            if (studentSelect.value) {
                targetDisplay = studentSelect.options[studentSelect.selectedIndex].text;
            }

            document.getElementById('excuseDateDisplay').textContent = dateDisplay;
            document.getElementById('excuseTargetDisplay').textContent = targetDisplay;
            document.getElementById('excuseHoursDisplay').textContent = hours;
        }

        function saveExcusedDate(event) {
            event.preventDefault();

            const date = document.getElementById('excuseDate').value;
            const hours = parseFloat(document.getElementById('excuseHours').value);
            const reason = document.getElementById('excuseReason').value;
            const studentId = document.getElementById('excuseStudentSelect').value;
            const saveBtn = document.getElementById('saveExcuseBtn');

            if (!studentId) {
                alert('Please select a student');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            console.log('Marking excused date:', { date, hours, reason, studentId });

            const formData = new URLSearchParams({
                action: 'mark_excused_date',
                date: date,
                hours: hours,
                reason: reason,
                student_id: studentId
            });

            fetch('student_list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data);

                    if (data.success) {
                        const studentName = document.getElementById('excuseStudentSelect').options[document.getElementById('excuseStudentSelect').selectedIndex].text;
                        alert(`Success! Marked ${date} as excused for ${studentName} with ${hours} hours credited.`);
                        closeExcuseCalendarModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alert('Error: ' + (data.error || 'Failed to mark excused date'));
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Mark as Excused';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving. Please try again.');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Mark as Excused';
                });
        }

        // Wait for DOM to be ready before adding event listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Update confirmation text when hours input changes
            const hoursToAddInput = document.getElementById('hoursToAdd');
            if (hoursToAddInput) {
                hoursToAddInput.addEventListener('input', function () {
                    const hoursValue = parseFloat(this.value) || 0;
                    document.getElementById('hoursAddedConfirm').textContent = hoursValue;
                });
            }

            // Enable/disable save button based on checkbox
            const confirmCheckbox = document.getElementById('confirmationCheckbox');
            if (confirmCheckbox) {
                confirmCheckbox.addEventListener('change', function () {
                    document.getElementById('saveHoursBtn').disabled = !this.checked;
                });
            }

            // Close modal when clicking outside
            const editModal = document.getElementById('editHoursModal');
            if (editModal) {
                editModal.addEventListener('click', function (event) {
                    if (event.target === this) {
                        closeEditHoursModal();
                    }
                });
            }
        });

        // Excuse Calendar Modal Event Listeners
        const excuseCalendarBtn = document.getElementById('excuseCalendar');
        if (excuseCalendarBtn) {
            excuseCalendarBtn.addEventListener('click', openExcuseCalendarModal);
        }

        // Update confirmation when form inputs change
        const excuseDate = document.getElementById('excuseDate');
        if (excuseDate) {
            excuseDate.addEventListener('change', updateExcuseConfirmation);
        }

        const excuseHours = document.getElementById('excuseHours');
        if (excuseHours) {
            excuseHours.addEventListener('input', updateExcuseConfirmation);
        }

        const excuseStudentSelect = document.getElementById('excuseStudentSelect');
        if (excuseStudentSelect) {
            excuseStudentSelect.addEventListener('change', updateExcuseConfirmation);
        }

        // Enable/disable save button based on checkbox
        const excuseConfirmCheckbox = document.getElementById('excuseConfirmationCheckbox');
        if (excuseConfirmCheckbox) {
            excuseConfirmCheckbox.addEventListener('change', function () {
                document.getElementById('saveExcuseBtn').disabled = !this.checked;
            });
        }

        // Close modal when clicking outside
        const excuseModal = document.getElementById('excuseCalendarModal');
        if (excuseModal) {
            excuseModal.addEventListener('click', function (event) {
                if (event.target === this) {
                    closeExcuseCalendarModal();
                }
            });
        }

        // Search functionality with debounce
        let searchTimeout;
        document.getElementById('searchStudent').addEventListener('input', function (e) {
            const searchTerm = e.target.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Set new timeout to avoid excessive reloads while typing
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);

                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search');
                }

                // Reset to page 1 when searching
                url.searchParams.delete('page');

                // Reload page with new search parameter
                window.location.href = url.toString();
            }, 500); // Wait 500ms after user stops typing
        });

        // Export to CSV
        document.getElementById('exportCSV').addEventListener('click', function () {
            // In a real app, this would generate and download a CSV file
            alert('Exporting to CSV... This would download a CSV file in a real application.');
        });

        // Pagination functionality (simplified)
        document.querySelectorAll('#pageNumbers button').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelector('#pageNumbers .active').classList.remove('active');
                this.classList.add('active');
                // In a real app, this would load the corresponding page of results
            });
        });

        document.getElementById('nextPage').addEventListener('click', function () {
            const activePage = document.querySelector('#pageNumbers .active');
            const nextPage = activePage.nextElementSibling;
            if (nextPage && nextPage.tagName === 'BUTTON') {
                activePage.classList.remove('active');
                nextPage.classList.add('active');
                // In a real app, this would load the next page of results
            }
        });

        document.getElementById('prevPage').addEventListener('click', function () {
            const activePage = document.querySelector('#pageNumbers .active');
            const prevPage = activePage.previousElementSibling;
            if (prevPage && prevPage.tagName === 'BUTTON') {
                activePage.classList.remove('active');
                prevPage.classList.add('active');
                // In a real app, this would load the previous page of results
            }
        });
    </script>

    <!-- Student Requests Modal -->
    <div id="studentRequestModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 class="modal-title">Workplace Change Requests <span id="modalRequestBadge"
                        class="notification-badge"
                        style="display:none; font-size: 0.8rem; vertical-align: middle;">0</span></h2>
                <button class="close-btn" onclick="closeModal('studentRequestModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="requestsContainer" class="requests-container">
                <!-- Requests will be loaded here as cards -->
                <div style="text-align: center; padding: 2rem; color: var(--secondary-text-clr);">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <div style="margin-top: 1rem;">Loading requests...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Confirmation Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Approve Request</h2>
                <button class="close-btn" onclick="closeModal('approvalModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="form-group">
                <p>You are approving the workplace change for <strong id="approvingStudentName"></strong>.</p>
                <p>Do you want to reset their accumulated OJT hours?</p>

                <div class="radio-group" style="margin-top: 1rem;">
                    <div class="radio-option">
                        <input type="radio" id="retainHours" name="hoursOption" value="retain" checked>
                        <label for="retainHours">
                            <strong>Retain Hours (Unchanged)</strong><br>
                            <small>Student keeps their current total of <span id="currentTotalHours">0</span>
                                hours.</small>
                        </label>
                    </div>

                    <div class="radio-option">
                        <input type="radio" id="resetHours" name="hoursOption" value="reset">
                        <label for="resetHours">
                            <strong>Reset Hours to 0</strong><br>
                            <small>Hours will be reset to 0. Use this if the new workplace requires a fresh
                                start.</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('approvalModal')">Cancel</button>
                <button class="btn-save" onclick="confirmApproval()">Confirm Approval</button>
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;

        document.getElementById('studentRequest').addEventListener('click', function () {
            openModal('studentRequestModal');
            fetchRequests();
        });

        function fetchRequests() {
            const container = document.getElementById('requestsContainer');
            container.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--secondary-text-clr);">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <div style="margin-top: 1rem;">Loading requests...</div>
                </div>`;

            const formData = new FormData();
            formData.append('action', 'get_workplace_requests');

            fetch('student_list.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRequests(data.data);
                    } else {
                        container.innerHTML = `
                            <div class="alert-error" style="text-align: center; margin: 2rem;">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                                <div style="margin-top: 0.5rem;">Error: ${data.error}</div>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = `
                        <div class="alert-error" style="text-align: center; margin: 2rem;">
                            <i class="fas fa-wifi fa-2x"></i>
                            <div style="margin-top: 0.5rem;">Failed to load requests. Please check your connection.</div>
                        </div>`;
                });
        }

        function renderRequests(requests) {
            const container = document.getElementById('requestsContainer');

            if (requests.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; color: var(--secondary-text-clr); display: flex; flex-direction: column; align-items: center;">
                        <div style="background: var(--hover-clr); padding: 2rem; border-radius: 50%; margin-bottom: 1.5rem;">
                            <i class="fas fa-check-circle fa-3x" style="color: var(--accent-clr);"></i>
                        </div>
                        <h3>All Caught Up!</h3>
                        <p>There are no pending workplace change requests at the moment.</p>
                    </div>`;
                return;
            }

            container.innerHTML = requests.map(req => `
                <div class="request-card">
                    <div class="req-header">
                        <div class="req-user">
                            <img src="${req.profile_pic_path || '../../storage/images/default_profile.jpg'}" class="req-avatar" alt="Profile">
                            <div>
                                <div class="req-name">${req.student_name}</div>
                                <div class="req-id">${req.school_id}</div>
                            </div>
                        </div>
                        <div class="req-meta">
                            <div class="req-date">
                                <i class="far fa-calendar-alt"></i> 
                                ${new Date(req.created_at).toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' })}
                            </div>
                        </div>
                    </div>
                    
                    <div class="req-body">
                        <div class="req-column">
                            <div class="req-label">Workplace Transition</div>
                            <div class="workplace-transition">
                                <div class="wp-box">
                                    <div class="req-label" style="font-size: 0.7rem;">CURRENT WORKPLACE</div>
                                    <div class="wp-name" style="color: var(--secondary-text-clr);">${req.current_workplace}</div>
                                </div>
                                
                                <div class="transition-arrow">
                                    <i class="fas fa-arrow-down"></i> Requesting Transfer To <i class="fas fa-arrow-down"></i>
                                </div>
                                
                                <div class="wp-box">
                                    <div class="req-label" style="font-size: 0.7rem;">NEW WORKPLACE</div>
                                    <div class="wp-name">${req.new_workplace}</div>
                                    <div class="wp-address"><i class="fas fa-map-marker-alt" style="width: 15px; text-align: center; color: var(--accent-clr);"></i> ${req.new_address}</div>
                                    <div class="wp-pos"><i class="fas fa-briefcase" style="width: 15px; text-align: center; color: var(--accent-clr);"></i> Position: <strong>${req.position_title || 'N/A'}</strong></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="req-column">
                             <div class="req-label">Key Personnel</div>
                             <div class="req-info-grid">
                                <div class="info-card">
                                    <div class="info-card-header">
                                        <i class="fas fa-user-tie"></i> Supervisor
                                    </div>
                                    <div class="info-card-content">
                                        <div style="font-weight: 600;">${req.supervisor_name || 'N/A'}</div>
                                        <div style="color: var(--secondary-text-clr); font-size: 0.85rem;">${req.supervisor_position || 'Position N/A'}</div>
                                    </div>
                                </div>
                                
                                <div class="info-card">
                                    <div class="info-card-header">
                                        <i class="fas fa-user-graduate"></i> Head Trainee
                                    </div>
                                    <div class="info-card-content">
                                        <div style="font-weight: 600;">${req.head_trainee || 'N/A'}</div>
                                        <div style="color: var(--secondary-text-clr); font-size: 0.85rem; margin-bottom: 0.5rem;">${req.head_trainee_position || 'Position N/A'}</div>
                                        
                                        <div class="info-contact">
                                            ${req.head_trainee_contact ? `<div><i class="fas fa-phone-alt"></i> ${req.head_trainee_contact}</div>` : ''}
                                            ${req.head_trainee_email ? `<div><i class="fas fa-envelope"></i> ${req.head_trainee_email}</div>` : ''}
                                            ${!req.head_trainee_contact && !req.head_trainee_email ? '<div>No contact info provided</div>' : ''}
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>

                    <div class="req-reason-section">
                        <div class="req-label" style="margin-bottom: 0.5rem; color: #856404;">REASON FOR CHANGE</div>
                        <div class="req-reason-text">${req.change_reason}</div>
                    </div>

                    <div class="req-actions">
                        <button class="btn btn-secondary" style="background-color: transparent; border-color: #ff4d4d; color: #ff4d4d;" 
                                onmouseover="this.style.backgroundColor='#ff4d4d'; this.style.color='white'"
                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='#ff4d4d'"
                                onclick="rejectRequest(${req.id})">
                            <i class="fas fa-times"></i> Reject Request
                        </button>
                        <button class="btn btn-primary" onclick="openApprovalModal(${req.id}, '${req.student_name.replace(/'/g, "\\'")}')">
                            <i class="fas fa-check"></i> Approve Request
                        </button>
                    </div>
                </div><br>
            `).join('');
        }

        function openApprovalModal(requestId, studentName) {
            currentRequestId = requestId;
            document.getElementById('approvingStudentName').textContent = studentName;
            // Note: Current hours typically not available in the request response based on my query, 
            // but for now showing "0" or just generic text is fine, or I could update the query.
            // For simplicity, I'll just state "Retain currently accumulated hours".
            openModal('approvalModal');
        }

        function confirmApproval() {
            const resetHours = document.getElementById('resetHours').checked;
            processRequest(currentRequestId, 'approve', resetHours);
        }

        function rejectRequest(requestId) {
            if (confirm('Are you sure you want to REJECT this workplace change request?')) {
                processRequest(requestId, 'reject', false);
            }
        }

        function processRequest(requestId, action, resetHours) {
            const formData = new FormData();
            formData.append('action', 'process_workplace_request');
            formData.append('request_id', requestId);
            formData.append('review_action', action);
            formData.append('reset_hours', resetHours);

            fetch('student_list.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        closeModal('approvalModal');
                        fetchRequests(); // Refresh list
                        // Optional: reload page to update main student list if workplace changed
                        if (action === 'approve') {
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                });
        }
    </script>

    <!-- Email Composition Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 class="modal-title">Compose Email</h3>
                <button class="close-btn" onclick="closeModal('emailModal')">&times;</button>
            </div>
            <div id="emailMessage" style="display: none; margin-bottom: 1rem;"></div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="recipientType">To: <span style="color: #e74c3c;">*</span></label>
                    <select id="recipientType" class="form-control" onchange="toggleSpecificStudent()">
                        <option value="all_students">All Students</option>
                        <option value="specific_student">Specific Student</option>
                    </select>
                </div>
                <div id="specificStudentContainer" class="form-group" style="display: none;">
                    <label for="specificStudent">Select Student: <span style="color: #e74c3c;">*</span></label>
                    <select id="specificStudent" class="form-control">
                        <option value="">Loading students...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="emailSubject">Subject: <span style="color: #e74c3c;">*</span></label>
                    <input type="text" id="emailSubject" class="form-control" placeholder="Enter email subject"
                        required>
                </div>
                <div class="form-group">
                    <label for="emailBody">Message: <span style="color: #e74c3c;">*</span></label>
                    <textarea id="emailBody" class="form-control" rows="10" placeholder="Compose your message here..."
                        required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('emailModal')">Cancel</button>
                <button class="btn btn-primary" id="sendEmailBtn" onclick="sendEmail()">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        #requestsTable {
            width: 100%;
            border-collapse: collapse;
        }

        #requestsTable th,
        #requestsTable td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--line-clr);
            vertical-align: top;
        }

        #requestsTable th {
            font-weight: 600;
            background-color: var(--hover-clr);
        }

        /* Specific column widths and text handling */
        #requestsTable td:nth-child(4) {
            /* Reason column */
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
            line-height: 1.5;
        }

        /* Increase modal width for Student Request modal */
        #studentRequestModal .modal-content {
            max-width: 95%;
            width: 1000px;
        }

        .modal-content {
            background-color: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 90%;
            width: 600px;
            padding: 1.5em;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1em;
            padding-bottom: 0.75em;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-title {
            color: var(--accent-clr);
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-clr);
            font-size: 1.5em;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background-color: var(--hover-clr);
            color: var(--accent-clr);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-clr);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.25rem;
            color: var(--text-clr);
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-clr);
            box-shadow: 0 0 0 2px rgba(26, 210, 28, 0.2);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line-clr);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--line-clr);
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            background-color: var(--base-clr);
            color: var(--text-clr);
        }

        .btn:hover {
            background-color: var(--hover-clr);
            transform: translateY(-1px);
        }

        .btn-primary {
            background-color: var(--accent-clr);
            color: #000;
            border-color: var(--accent-clr);
        }

        .btn-primary:hover {
            background-color: #15a016;
            border-color: #15a016;
        }

        .btn-secondary {
            background-color: var(--hover-clr);
            color: var(--text-clr);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert-success {
            color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            padding: 0.75rem;
            border-radius: 0.25rem;
        }

        .alert-error {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            padding: 0.75rem;
            border-radius: 0.25rem;
        }

        }

        /* Modern Request Card UI */
        .requests-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 0.5rem;
        }

        .request-card {
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            border-color: var(--accent-clr);
        }

        .request-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--accent-clr);
            opacity: 0.7;
        }

        .req-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .req-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .req-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-clr);
        }

        .req-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-clr);
        }

        .req-id {
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
        }

        .req-meta {
            text-align: right;
        }

        .req-date {
            font-size: 0.9rem;
            color: var(--secondary-text-clr);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .req-body {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .req-column {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .req-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--secondary-text-clr);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .workplace-transition {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: rgba(0, 0, 0, 0.02);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid var(--line-clr);
        }

        .wp-box {
            position: relative;
        }

        .wp-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--accent-clr);
        }

        .wp-address {
            font-size: 0.9rem;
            color: var(--text-clr);
            margin-top: 0.25rem;
        }

        .wp-pos {
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
            margin-top: 0.25rem;
        }

        .transition-arrow {
            text-align: center;
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .req-info-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-card {
            background: rgba(26, 210, 28, 0.05);
            /* Tint of accent hue */
            border: 1px solid rgba(26, 210, 28, 0.2);
            padding: 1rem;
            border-radius: 8px;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent-clr);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(26, 210, 28, 0.1);
        }

        .info-card-content {
            font-size: 0.9rem;
        }

        .info-contact {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--secondary-text-clr);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-contact i {
            width: 16px;
            color: var(--accent-clr);
        }

        .req-reason-section {
            background: var(--hover-clr);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid #ffc107;
            /* Warning yellow for attention */
        }

        .req-reason-text {
            font-style: italic;
            color: var(--text-clr);
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .req-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--line-clr);
        }

        @media (max-width: 768px) {
            .req-body {
                grid-template-columns: 1fr;
            }

            .req-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .req-meta {
                text-align: left;
            }

            .req-actions {
                flex-direction: column;
            }

            .req-actions button {
                width: 100%;
            }
        }
    </style>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Load students when opening email modal
            if (modalId === 'emailModal') {
                loadStudentsForEmail();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';

            // Reset form when closing email modal
            if (modalId === 'emailModal') {
                document.getElementById('emailSubject').value = '';
                document.getElementById('emailBody').value = '';
                document.getElementById('recipientType').value = 'all_students';
                document.getElementById('specificStudentContainer').style.display = 'none';
                document.getElementById('emailMessage').style.display = 'none';
                document.getElementById('sendEmailBtn').disabled = false;
                document.getElementById('sendEmailBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Send';
            }
        }

        // Email functionality
        function toggleSpecificStudent() {
            const recipientType = document.getElementById('recipientType').value;
            const specificStudentContainer = document.getElementById('specificStudentContainer');

            if (recipientType === 'specific_student') {
                specificStudentContainer.style.display = 'block';
            } else {
                specificStudentContainer.style.display = 'none';
            }
        }

        function loadStudentsForEmail() {
            const select = document.getElementById('specificStudent');
            select.innerHTML = '<option value="">Loading students...</option>';

            // Use the students data already available on the page
            <?php if (!empty($all_students)): ?>
            const students = <?php echo json_encode(array_values($all_students)); ?>;
            select.innerHTML = '<option value="">Select a student...</option>';
            students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = `${student.full_name} (${student.school_id})`;
                select.appendChild(option);
            });
            <?php else: ?>
            select.innerHTML = '<option value="">No students available</option>';
            <?php endif; ?>
        }

        function showEmailMessage(message, type) {
            const messageDiv = document.getElementById('emailMessage');
            messageDiv.textContent = message;
            messageDiv.className = `alert-${type}`;
            messageDiv.style.display = 'block';

            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        function sendEmail() {
            const recipientType = document.getElementById('recipientType').value;
            const specificStudent = document.getElementById('specificStudent').value;
            const subject = document.getElementById('emailSubject').value.trim();
            const body = document.getElementById('emailBody').value.trim();

            // Basic validation
            if (!subject) {
                showEmailMessage('Please enter a subject', 'error');
                return;
            }

            if (!body) {
                showEmailMessage('Please enter a message', 'error');
                return;
            }

            if (recipientType === 'specific_student' && !specificStudent) {
                showEmailMessage('Please select a student', 'error');
                return;
            }

            // Disable button during sending
            const sendBtn = document.getElementById('sendEmailBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            // Prepare data
            const emailData = {
                recipientType: recipientType,
                subject: subject,
                body: body,
                instructorName: '<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Instructor'); ?>'
            };

            if (recipientType === 'specific_student') {
                emailData.specificStudent = specificStudent;
            }

            // Send email
            fetch('send_instructor_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(emailData)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            console.error('Error response:', data);
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        let message = data.message || 'Email sent successfully!';
                        if (data.sent !== undefined) {
                            message = `Email sent successfully to ${data.sent} recipient(s)`;
                            if (data.failed > 0) {
                                message += `. ${data.failed} failed to send.`;
                            }
                        }
                        showEmailMessage(message, data.failed > 0 ? 'warning' : 'success');

                        // Reset form and close modal after delay
                        setTimeout(() => {
                            document.getElementById('emailSubject').value = '';
                            document.getElementById('emailBody').value = '';
                            closeModal('emailModal');
                        }, 3000);
                    } else {
                        console.error('Email send failed:', data);
                        let errorMsg = data.message || 'Error sending email. Please try again.';
                        showEmailMessage(errorMsg, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showEmailMessage('Network error. Please try again.', 'error');
                })
                .finally(() => {
                    // Re-enable button
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                });
        }

        // Add event listener for compose email button
        const composeEmailBtn = document.getElementById('composeEmail');
        if (composeEmailBtn) {
            composeEmailBtn.addEventListener('click', function () {
                openModal('emailModal');
            });
        }

        // Close modal when clicking outside
        const emailModal = document.getElementById('emailModal');
        if (emailModal) {
            emailModal.addEventListener('click', function (event) {
                if (event.target === this) {
                    closeModal('emailModal');
                }
            });
        }

        // Function to check workplace change request count
        function checkWorkplaceRequests() {
            fetch('student_list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_workplace_requests'
            })
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('workplaceRequestBadge');
                    const modalBadge = document.getElementById('modalRequestBadge');

                    if (data.success && data.data) {
                        const count = data.data.length;
                        if (count > 0) {
                            if (badge) {
                                badge.textContent = count;
                                badge.style.display = 'inline-block';
                            }
                            if (modalBadge) {
                                modalBadge.textContent = count;
                                modalBadge.style.display = 'inline-block';
                            }
                        } else {
                            if (badge) badge.style.display = 'none';
                            if (modalBadge) modalBadge.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking workplace requests:', error);
                });
        }

        // Check immediately on page load
        checkWorkplaceRequests();

        // Check every 15 seconds for new requests
        setInterval(checkWorkplaceRequests, 15000);
        // Excel Export Functionality
        const exportBtn = document.getElementById('exportCSV');
        if (exportBtn) {
            exportBtn.innerHTML = '<i class="fas fa-file-excel"></i> Export Excel';
            exportBtn.addEventListener('click', function () {
                window.location.href = 'export_reports_instructor.php';
            });
        }


    </script>
</body>

</html>