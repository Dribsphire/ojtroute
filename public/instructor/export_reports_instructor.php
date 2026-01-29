<?php
/**
 * Export Student Reports to Excel (.xls)
 * Allows for colored rows and custom styling not possible in CSV
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireInstructor.php';
require_once __DIR__ . '/../admin/points_breakdown_helper.php';

use App\Services\ReportsService;
use App\Services\InstructorService;

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="ITEOJT_Reports_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    $config = require __DIR__ . '/../../config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // Get Current Instructor ID
    $instructorService = new InstructorService();
    $instructorUserId = $_SESSION['user_id'];
    $instructorId = $instructorService->getInstructorId($instructorUserId);

    if (!$instructorId) {
        die("Error: Instructor not found or invalid session.");
    }

    // Get current academic year
    $currentYear = date('Y');
    $nextYear = $currentYear + 1;
    $academicYear = "{$currentYear} - {$nextYear}";

    // Filter by Section if provided
    $sectionId = isset($_GET['section_id']) ? (int) $_GET['section_id'] : null;

    // 1. Fetch all students eligible for report (Strictly filtered by Instructor)
    $whereConditions = ["u.role = 'student'", "u.is_archived = 0", "s.instructor_id = :current_instructor_id"];
    $params = [':current_instructor_id' => $instructorId];

    if ($sectionId) {
        $whereConditions[] = "u.section_id = :section_id";
        $params[':section_id'] = $sectionId;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $sql = "
        SELECT 
            u.id as user_id,
            u.full_name,
            s.id as section_id_val,
            s.section_name,
            s.instructor_id,
            inst_u.full_name as instructor_name,
            st.id as student_id,
            wp.company_name,
            wp.start_date
        FROM users u
        INNER JOIN students st ON u.id = st.user_id
        LEFT JOIN sections s ON u.section_id = s.id
        LEFT JOIN instructors inst ON s.instructor_id = inst.id
        LEFT JOIN users inst_u ON inst.user_id = inst_u.id
        LEFT JOIN student_workplaces wp ON st.id = wp.student_id AND wp.is_active = 1
        WHERE {$whereClause}
        ORDER BY inst_u.full_name ASC, s.section_name ASC, u.full_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Group by Instructor
    $groupedByInstructor = [];
    foreach ($students as $student) {
        $instId = $student['instructor_id'] ?: 0; // 0 for no instructor
        $instName = $student['instructor_name'] ?: 'No Instructor Assignee';

        if (!isset($groupedByInstructor[$instId])) {
            $groupedByInstructor[$instId] = [
                'name' => $instName,
                'sections' => [],
                'students' => []
            ];
        }

        $sectionName = $student['section_name'] ?: 'No Section';
        if (!in_array($sectionName, $groupedByInstructor[$instId]['sections'])) {
            $groupedByInstructor[$instId]['sections'][] = $sectionName;
        }

        $groupedByInstructor[$instId]['students'][] = $student;
    }

    // --- Start HTML Output ---
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<!--[if gte mso 9]><xml>';
    echo '<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>OJT Reports</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
    echo '</xml><![endif]-->';
    echo '<style>
        body { font-family: "Arial Narrow", Arial, sans-serif; font-size: 10px; color: #000000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 0.5pt solid #000000; padding: 2px; text-align: left; vertical-align: middle; }
        .instructor-row { background-color: #ff0000; color: #ffffffff; font-weight: bold; font-size: 10pt;}
        .section-row { background-color: #00b050; color: #fffafaff; font-weight: bold; font-size: 10pt; }
        .header-row { font-weight: bold; text-align: center; background-color: #d9e2f3; }
    </style>';
    echo '</head>';
    echo '<body>';
    echo '<table>';

    // File Headers
    echo '<tr><td colspan="5" style="border:none; font-size: 8pt; font-weight: bold;">Second Semester, ' . $academicYear . '</td></tr>';
    echo '<tr><td colspan="5" style="border:none; font-size: 8pt; font-weight: bold;">ITEOJT (IT) - IT Internship On-the-Job Training</td></tr>';
    echo '<tr><td style="border:none;">&nbsp;</td></tr>'; // Blank row

    // 3. Process each Instructor Block
    $firstBlock = true;
    foreach ($groupedByInstructor as $instId => $data) {
        if (!$firstBlock) {
            echo '<tr><td style="border:none; height: 20px;"></td></tr>'; // Blank row between instructors
        }
        $firstBlock = false;

        // Fetch Requirements
        $docSql = "
            SELECT id, name, category 
            FROM document_types 
            WHERE is_active = 1 
            AND is_pre_required = 1
            AND (instructor_id = :inst_id OR instructor_id IS NULL)
            ORDER BY name ASC
        ";
        $docStmt = $pdo->prepare($docSql);
        $docStmt->execute([':inst_id' => $instId]);
        $docTypes = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Month Headers
        $studentIds = array_column($data['students'], 'student_id');
        $monthHeaders = [];
        if (!empty($studentIds)) {
            $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
            $monthSql = "
                SELECT DISTINCT DATE_FORMAT(attendance_date, '%Y-%m') as ym, DATE_FORMAT(attendance_date, '%M') as month_name
                FROM attendance_records 
                WHERE student_id IN ($placeholders) 
                AND status = 'completed' AND hours > 0
                ORDER BY ym ASC
            ";
            $monthStmt = $pdo->prepare($monthSql);
            $monthStmt->execute($studentIds);
            $monthHeaders = $monthStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Calculate total columns for colspan
        $totalCols = 2 + count($docTypes) + count($monthHeaders) + 5;

        // --- Write Headers ---
        // Row 1: Instructor Name (RED)
        echo '<tr>';
        echo '<td colspan="' . $totalCols . '" class="instructor-row">';
        echo 'Instructor Name: ' . htmlspecialchars($data['name']);
        echo '</td>';
        echo '</tr>';

        // Row 2: Section(s) (GREEN)
        echo '<tr>';
        echo '<td colspan="' . $totalCols . '" class="section-row">';
        echo 'Section: ' . htmlspecialchars(implode(', ', $data['sections']));
        echo '</td>';
        echo '</tr>';

        // Row 3: Column Headers
        echo '<tr class="header-row">';
        echo '<th>Full Name</th>';
        echo '<th>HTE</th>';

        foreach ($docTypes as $dt) {
            echo '<th>' . htmlspecialchars($dt['name']) . '</th>';
        }

        foreach ($monthHeaders as $m) {
            echo '<th>' . htmlspecialchars($m['month_name']) . ' Hours</th>';
        }

        echo '<th>Completeness of<br style="mso-data-placement:same-cell;" />Documents</th>';
        echo '<th>Accuracy</th>';
        echo '<th>Presentation</th>';
        echo '<th>Timeliness</th>';
        echo '<th>Total Points</th>';
        echo '</tr>';

        // --- Write Student Rows ---
        foreach ($data['students'] as $student) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($student['company_name'] ?: 'Not Assigned') . '</td>';

            // Document Columns
            $submissions = getStudentSubmissions($pdo, $student['student_id']);
            foreach ($docTypes as $dt) {
                $status = '';
                if (isset($submissions[$dt['id']])) {
                    $sub = $submissions[$dt['id']];
                    $isEndorsement = stripos($dt['name'], 'endorsement') !== false;

                    if ($sub['status'] === 'approved') {
                        if ($isEndorsement) {
                            $status = date('Y-m-d', strtotime($sub['submitted_at']));
                        } else {
                            $status = 'Done';
                        }
                    } elseif ($sub['status'] === 'pending') {
                        $status = 'Pending';
                    } else {
                        $status = ucfirst($sub['status']);
                    }
                }
                echo '<td>' . htmlspecialchars($status) . '</td>';
            }

            // Monthly Hours Columns
            $studentMonths = getStudentMonthlyHours($pdo, $student['student_id']);
            foreach ($monthHeaders as $m) {
                $ym = $m['ym'];
                $hours = isset($studentMonths[$ym]) ? number_format($studentMonths[$ym], 2) : '0';
                echo '<td>' . $hours . '</td>';
            }

            // Points
            $points = getPointsBreakdown($pdo, $student['student_id']);
            echo '<td>' . ($points['bonus_points'] > 0 ? $points['bonus_points'] : '') . '</td>';
            echo '<td>' . ($points['accuracy_quality_points'] > 0 ? $points['accuracy_quality_points'] : '') . '</td>';
            echo '<td>' . ($points['professional_presentation_points'] > 0 ? $points['professional_presentation_points'] : '') . '</td>';
            echo '<td>' . ($points['timeliness_points'] != 0 ? $points['timeliness_points'] : '0') . '</td>';
            echo '<td>' . ($points['total_points'] > 0 ? $points['total_points'] : '') . '</td>';

            echo '</tr>';
        }
    }

    echo '</table>';
    echo '</body>';
    echo '</html>';

} catch (Exception $e) {
    echo "Error generating Report: " . $e->getMessage();
}

/**
 * Get submissions indexed by document_type_id
 */
function getStudentSubmissions($pdo, $studentId)
{
    $sql = "SELECT document_type_id, status, submitted_at FROM document_submissions WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId]);
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[$row['document_type_id']] = $row;
    }
    return $results;
}

/**
 * Get monthly hours indexed by Y-m
 */
function getStudentMonthlyHours($pdo, $studentId)
{
    $sql = "
        SELECT DATE_FORMAT(attendance_date, '%Y-%m') as ym, SUM(hours) as total
        FROM attendance_records 
        WHERE student_id = ? AND status = 'completed' AND hours > 0
        GROUP BY ym
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId]);
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[$row['ym']] = $row['total'];
    }
    return $data;
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
