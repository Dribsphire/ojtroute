<?php
/**
 * Export Student Reports to CSV
 * Generates a detailed CSV report matching the ITEOJT format
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/points_breakdown_helper.php';

use App\Services\ReportsService;

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ITEOJT_Reports_' . date('Y-m-d_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $config = require __DIR__ . '/../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // Open output stream
    $output = fopen('php://output', 'w');

    // Get current academic year (e.g., "2025 - 2026")
    $currentYear = date('Y');
    $nextYear = $currentYear + 1;
    $academicYear = "{$currentYear} - {$nextYear}";

    // Write header rows
    fputcsv($output, ['', '', "Second Semester, {$academicYear}", '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['', '', 'ITEOJT (IT) - IT Internship On-the-Job Training', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

    // Write column headers
    $headers = [
        'fullname',
        'workplace',
        'Memorandum of agreement (MOA)',
        'Internship Agreement',
        'parents consent',
        'ENDORSEMENT LETTER (Signed/received by the company)',
        'pledge of good conduct',
        'resume',
        'application letter',
        'medical certificate',
        'weekly acomplisment report',
        'January total hours',
        'February Total Hours',
        'March Total hours',
        'April Total Hours',
        'May Total Hours',
        'Bonus Points',
        'Accuracy & Quality Points',
        'Professional Presentation Points',
        'Timeliness Points',
        'Total Points'
    ];
    fputcsv($output, $headers);

    // Get section filter if provided
    $sectionId = isset($_GET['section_id']) ? (int) $_GET['section_id'] : null;

    // Build WHERE clause
    $whereConditions = [
        "u.role = 'student'",
        "u.is_archived = 0"
    ];
    $params = [];

    if ($sectionId !== null && $sectionId > 0) {
        $whereConditions[] = "u.section_id = :section_id";
        $params[':section_id'] = $sectionId;
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get all students grouped by instructor and section
    $sql = "
        SELECT 
            u.id as user_id,
            u.school_id,
            u.full_name,
            u.section_id,
            s.section_code,
            s.section_name,
            s.instructor_id,
            st.id as student_id,
            wp.company_name,
            wp.start_date,
            wp.end_date,
            inst_user.full_name as instructor_name
        FROM users u
        INNER JOIN students st ON u.id = st.user_id
        LEFT JOIN sections s ON u.section_id = s.id
        LEFT JOIN student_workplaces wp ON st.id = wp.student_id AND wp.is_active = 1
        LEFT JOIN instructors inst ON s.instructor_id = inst.id
        LEFT JOIN users inst_user ON inst.user_id = inst_user.id
        WHERE {$whereClause}
        ORDER BY inst_user.full_name ASC, s.section_name ASC, u.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group students by instructor and section
    $groupedStudents = [];
    foreach ($students as $student) {
        $instructorName = $student['instructor_name'] ?? 'No Instructor';
        $sectionName = $student['section_name'] ?? $student['section_code'] ?? 'No Section';

        if (!isset($groupedStudents[$instructorName])) {
            $groupedStudents[$instructorName] = [];
        }
        if (!isset($groupedStudents[$instructorName][$sectionName])) {
            $groupedStudents[$instructorName][$sectionName] = [];
        }
        $groupedStudents[$instructorName][$sectionName][] = $student;
    }

    // Process each group
    foreach ($groupedStudents as $instructorName => $sections) {
        foreach ($sections as $sectionName => $sectionStudents) {
            // Write instructor row
            fputcsv($output, ["Instructor: {$instructorName}", '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

            // Write section row
            fputcsv($output, ["Section: {$sectionName}", '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);

            // Process each student in this section
            foreach ($sectionStudents as $student) {
                $studentId = $student['student_id'];
                $studentRow = [];

                // 1. Full name
                $studentRow[] = $student['full_name'];

                // 2. Workplace
                $studentRow[] = $student['company_name'] ?? 'Not assigned';

                // 3-10. Document submissions (checkmarks for approved)
                $documentChecks = getDocumentStatuses($pdo, $studentId);
                $studentRow[] = $documentChecks['MOA'];
                $studentRow[] = $documentChecks['Internship Agreement'];
                $studentRow[] = $documentChecks['parents consent'];
                $studentRow[] = $documentChecks['Endorsement']; // This will be a date
                $studentRow[] = $documentChecks['pledge of good conduct'];
                $studentRow[] = $documentChecks['resume'];
                $studentRow[] = $documentChecks['application letter'];
                $studentRow[] = $documentChecks['medical certificate'];

                // 11. Weekly accomplishment report (latest submission)
                $studentRow[] = $documentChecks['weekly report'];

                // 12-16. Monthly hours (calculated from start_date)
                $monthlyHours = getMonthlyHours($pdo, $studentId, $student['start_date']);
                for ($i = 0; $i < 5; $i++) {
                    $studentRow[] = isset($monthlyHours[$i]) && $monthlyHours[$i] > 0 ? number_format($monthlyHours[$i], 2) : '';
                }

                // 17-21. Points breakdown
                $pointsBreakdown = getPointsBreakdown($pdo, $studentId);
                $studentRow[] = $pointsBreakdown['bonus_points'] > 0 ? number_format($pointsBreakdown['bonus_points'], 1) : '';
                $studentRow[] = $pointsBreakdown['accuracy_quality_points'] > 0 ? number_format($pointsBreakdown['accuracy_quality_points'], 1) : '';
                $studentRow[] = $pointsBreakdown['professional_presentation_points'] > 0 ? number_format($pointsBreakdown['professional_presentation_points'], 1) : '';
                $studentRow[] = $pointsBreakdown['timeliness_points'] != 0 ? number_format($pointsBreakdown['timeliness_points'], 0) : '0';
                $studentRow[] = $pointsBreakdown['total_points'] > 0 ? number_format($pointsBreakdown['total_points'], 1) : '';

                fputcsv($output, $studentRow);
            }

            // Add blank row after each section for better readability
            fputcsv($output, ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']);
        }
    }

    fclose($output);

} catch (Exception $e) {
    error_log('CSV Export Error: ' . $e->getMessage());
    echo "Error generating CSV: " . $e->getMessage();
}

/**
 * Get document submission statuses for a student
 */
function getDocumentStatuses($pdo, $studentId)
{
    $statuses = [
        'MOA' => '',
        'Internship Agreement' => '',
        'parents consent' => '',
        'Endorsement' => '',
        'pledge of good conduct' => '',
        'resume' => '',
        'application letter' => '',
        'medical certificate' => '',
        'weekly report' => ''
    ];

    // Map document names to check
    $documentMap = [
        'MOA' => ['Memorandum of Agreement (MOA)', 'MOA'],
        'Internship Agreement' => ['Internship Agreement'],
        'parents consent' => ['Parent Consent Form', 'parents consent', 'parental consent'],
        'Endorsement' => ['Endorsement Letter', 'Endorsement'],
        'pledge of good conduct' => ['Pledge of Good Conduct', 'pledge of good conduct'],
        'resume' => ['Resume/Curriculum Vitae', 'Resume', 'CV', 'resume'],
        'application letter' => ['Application Letter', 'application letter'],
        'medical certificate' => ['Medical Certificate', 'medical certificate', 'Medical Certificate (Excuse)'],
        'weekly report' => ['Weekly OJT Report', 'weekly report', 'Weekly Report']
    ];

    // Get all document submissions for this student
    $sql = "
        SELECT 
            dt.name,
            dt.code,
            ds.status,
            ds.submitted_at
        FROM document_submissions ds
        INNER JOIN document_types dt ON ds.document_type_id = dt.id
        WHERE ds.student_id = :student_id
        ORDER BY ds.submitted_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($submissions as $submission) {
        $docName = strtolower($submission['name']);
        $docCode = strtolower($submission['code'] ?? '');
        $status = $submission['status'];
        $submittedAt = $submission['submitted_at'];

        // Check each document type
        foreach ($documentMap as $key => $possibleNames) {
            // Skip if already found (except for weekly report which we want the latest)
            if ($statuses[$key] !== '' && $key !== 'weekly report') {
                continue;
            }

            foreach ($possibleNames as $possibleName) {
                $possibleNameLower = strtolower($possibleName);

                // Check if document name or code contains the possible name
                if (strpos($docName, $possibleNameLower) !== false || strpos($docCode, $possibleNameLower) !== false) {
                    // For Endorsement, show the submitted date if approved
                    if ($key === 'Endorsement' && $status === 'approved') {
                        $date = new DateTime($submittedAt);
                        $statuses[$key] = $date->format('F j, Y'); // Format: January 3, 2025
                        break 2;
                    }
                    // For weekly report, show "done" if latest is submitted
                    elseif ($key === 'weekly report') {
                        if ($statuses[$key] === '') { // Only set once (latest)
                            $statuses[$key] = ($status === 'approved' || $status === 'pending') ? 'done' : '';
                        }
                        break 2;
                    }
                    // For others, show "done" if approved
                    elseif ($status === 'approved') {
                        $statuses[$key] = 'done';
                        break 2;
                    }
                }
            }
        }
    }

    return $statuses;
}

/**
 * Get monthly hours calculated from start_date
 * Returns array of hours for up to 5 months
 */
function getMonthlyHours($pdo, $studentId, $startDate)
{
    if (!$startDate) {
        return [];
    }

    // Get all attendance records for this student
    $sql = "
        SELECT 
            attendance_date,
            hours
        FROM attendance_records
        WHERE student_id = :student_id
        AND status = 'completed'
        AND hours IS NOT NULL
        ORDER BY attendance_date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also get excused hours
    $sqlExcused = "
        SELECT 
            excused_date,
            hours_added
        FROM excused_dates
        WHERE student_id = :student_id
        ORDER BY excused_date ASC
    ";

    $stmtExcused = $pdo->prepare($sqlExcused);
    $stmtExcused->execute([':student_id' => $studentId]);
    $excusedRecords = $stmtExcused->fetchAll(PDO::FETCH_ASSOC);

    // Combine all records
    $allRecords = [];
    foreach ($records as $record) {
        $allRecords[] = [
            'date' => $record['attendance_date'],
            'hours' => floatval($record['hours'])
        ];
    }
    foreach ($excusedRecords as $record) {
        $allRecords[] = [
            'date' => $record['excused_date'],
            'hours' => floatval($record['hours_added'])
        ];
    }

    // Sort by date
    usort($allRecords, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    // Calculate monthly totals based on 30-day periods from start_date
    $monthlyHours = [];
    $startDateTime = new DateTime($startDate);

    foreach ($allRecords as $record) {
        try {
            $recordDate = new DateTime($record['date']);

            // Calculate days difference
            if ($recordDate < $startDateTime) {
                // If record is before start date, skip it
                continue;
            }

            $interval = $startDateTime->diff($recordDate);
            $daysDiff = $interval->days;

            // Handle false return from days property
            if ($daysDiff === false) {
                continue;
            }

            // Calculate which month (0-indexed) - each month is 30 days
            $monthIndex = floor($daysDiff / 30);

            if ($monthIndex < 5) { // Only track up to 5 months
                if (!isset($monthlyHours[$monthIndex])) {
                    $monthlyHours[$monthIndex] = 0;
                }
                $monthlyHours[$monthIndex] += $record['hours'];
            }
        } catch (Exception $e) {
            // Skip invalid dates
            continue;
        }
    }

    return $monthlyHours;
}

/**
 * Get total points from document submissions
 */
function getTotalPoints($pdo, $studentId)
{
    $sql = "
        SELECT COALESCE(SUM(points), 0) as total_points
        FROM document_submissions
        WHERE student_id = :student_id
        AND points IS NOT NULL
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return intval($result['total_points']);
}
