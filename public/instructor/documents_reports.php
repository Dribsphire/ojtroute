<?php
session_start();
require_once '../../app/services/InstructorService.php';

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header('Location: ../login.php');
    exit;
}

$instructorService = new \App\Services\InstructorService();
$instructorId = $instructorService->getInstructorId($_SESSION['user_id']);

if (!$instructorId) {
    echo "Instructor profile not found.";
    exit;
}

$db = $instructorService->getDb();

// Get sections handled by this instructor
$stmt = $db->prepare("
    SELECT 
        id,
        section_code,
        section_name,
        department,
        year
    FROM sections
    WHERE instructor_id = :instructor_id
    AND is_active = 1
    ORDER BY section_code ASC
");
$stmt->execute([':instructor_id' => $instructorId]);
$sections = $stmt->fetchAll();

// Calculate statistics for each section
$sectionStats = [];
foreach ($sections as $section) {
    $sectionId = $section['id'];

    // Total students in section
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM users
        WHERE section_id = :section_id
        AND role = 'student'
        AND is_archived = 0
    ");
    $stmt->execute([':section_id' => $sectionId]);
    $totalStudents = $stmt->fetchColumn();

    // Students with no workplace
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT u.id) as count
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN student_workplaces sw ON s.id = sw.student_id AND sw.is_active = 1
        WHERE u.section_id = :section_id
        AND u.role = 'student'
        AND u.is_archived = 0
        AND sw.id IS NULL
    ");
    $stmt->execute([':section_id' => $sectionId]);
    $noWorkplace = $stmt->fetchColumn();

    // Students with no attendance for 3 consecutive days
    $stmt = $db->prepare("
        SELECT u.id, u.full_name, s.id as student_db_id
        FROM users u
        JOIN students s ON u.id = s.user_id
        WHERE u.section_id = :section_id
        AND u.role = 'student'
        AND u.is_archived = 0
    ");
    $stmt->execute([':section_id' => $sectionId]);
    $students = $stmt->fetchAll();

    $noAttendance3Days = 0;
    $noAttendanceStudents = [];

    foreach ($students as $student) {
        // Check last 3 days
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT attendance_date) as days_present
            FROM attendance_records
            WHERE student_id = :student_id
            AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            AND attendance_date <= CURDATE()
        ");
        $stmt->execute([':student_id' => $student['student_db_id']]);
        $daysPresent = $stmt->fetchColumn();

        if ($daysPresent == 0) {
            $noAttendance3Days++;
            $noAttendanceStudents[] = $student['full_name'];
        }
    }

    // Students with missing pre-required documents
    // First get all pre-required docs for this instructor
    $stmt = $db->prepare("
        SELECT id
        FROM document_types
        WHERE is_pre_required = 1
        AND is_active = 1
        AND (instructor_id = :instructor_id OR instructor_id IS NULL)
    ");
    $stmt->execute([':instructor_id' => $instructorId]);
    $preReqDocs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $totalPreReqDocs = count($preReqDocs);

    $missingPreReqStudents = 0;
    $missingPreReqStudentsList = [];

    if ($totalPreReqDocs > 0) {
        foreach ($students as $student) {
            // Count approved pre-req docs for this student
            $placeholders = implode(',', array_fill(0, count($preReqDocs), '?'));
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT document_type_id) as approved_count
                FROM document_submissions
                WHERE student_id = ?
                AND document_type_id IN ($placeholders)
                AND status = 'approved'
            ");
            $params = array_merge([$student['student_db_id']], $preReqDocs);
            $stmt->execute($params);
            $approvedCount = $stmt->fetchColumn();

            if ($approvedCount < $totalPreReqDocs) {
                $missingPreReqStudents++;
                $missingPreReqStudentsList[] = [
                    'name' => $student['full_name'],
                    'missing' => $totalPreReqDocs - $approvedCount
                ];
            }
        }
    }

    $sectionStats[] = [
        'section' => $section,
        'total_students' => $totalStudents,
        'no_workplace' => $noWorkplace,
        'no_attendance_3days' => $noAttendance3Days,
        'no_attendance_students' => $noAttendanceStudents,
        'missing_prereq' => $missingPreReqStudents,
        'missing_prereq_students' => $missingPreReqStudentsList
    ];
}

// Calculate overall totals
$totalStudentsAll = array_sum(array_column($sectionStats, 'total_students'));
$totalNoWorkplace = array_sum(array_column($sectionStats, 'no_workplace'));
$totalNoAttendance = array_sum(array_column($sectionStats, 'no_attendance_3days'));
$totalMissingPreReq = array_sum(array_column($sectionStats, 'missing_prereq'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <style>
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 0 0 2rem 0;
            flex-wrap: wrap;
        }

        .page-title {
            color: var(--accent-clr);
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e1f2d 0%, #2a2b3a 100%);
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            padding: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .stat-card.warning {
            border-left: 4px solid #f39c12;
        }

        .stat-card.danger {
            border-left: 4px solid #e74c3c;
        }

        .stat-card.success {
            border-left: 4px solid var(--accent-clr);
        }

        .stat-card.info {
            border-left: 4px solid #4da6ff;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.warning {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .stat-icon.danger {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .stat-icon.success {
            background: rgba(26, 210, 28, 0.2);
            color: var(--accent-clr);
        }

        .stat-icon.info {
            background: rgba(77, 166, 255, 0.2);
            color: #4da6ff;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-clr);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title {
            color: var(--accent-clr);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--line-clr);
            font-size: 1.3rem;
        }

        .table-container {
            background: transparent;
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .responsive-table {
            overflow-x: auto;
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
            background-color: var(--base-clr);
            color: var(--accent-clr);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: var(--hover-clr);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.warning {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }

        .badge.danger {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .badge.success {
            background: rgba(26, 210, 28, 0.2);
            color: var(--accent-clr);
        }

        .btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.5rem 0.9rem;
            border-radius: .5em;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            border-color: var(--accent-clr);
            color: var(--accent-clr);
            background: var(--hover-clr);
        }

        .btn.primary {
            background: var(--accent-clr);
            border-color: var(--accent-clr);
            color: white;
        }

        .btn.primary:hover {
            opacity: 0.9;
            color: white;
        }

        .expandable {
            cursor: pointer;
        }

        .details-row {
            display: none;
            background: var(--hover-clr);
        }

        .details-row.show {
            display: table-row;
        }

        .student-list {
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .student-list-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background: var(--base-clr);
            border-radius: 0.5em;
            border-left: 3px solid #e74c3c;
        }

        @media print {
            .btn {
                display: none;
            }

            .stat-card {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'instructor_nav.php'; ?>
    <main>
        <div class="page-header">
            <h2 class="page-title">Student Overview Reports</h2>
            <button class="btn primary" onclick="window.print()">
                <i class="fas fa-print"></i>
                Print Report
            </button>
        </div>

        <!-- Overall Statistics -->
        <h3 class="section-title">Overall Statistics</h3>
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">
                    <?php echo $totalStudentsAll; ?>
                </div>
                <div class="stat-label">Total Students</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon warning">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-value">
                    <?php echo $totalNoWorkplace; ?>
                </div>
                <div class="stat-label">No Workplace Set</div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon danger">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-value">
                    <?php echo $totalNoAttendance; ?>
                </div>
                <div class="stat-label">No Attendance (3 Days)</div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon danger">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div class="stat-value">
                    <?php echo $totalMissingPreReq; ?>
                </div>
                <div class="stat-label">Missing Pre-Required Docs</div>
            </div>
        </div>

        <!-- Section Breakdown -->
        <h3 class="section-title">Section Breakdown</h3>
        <div class="table-container">
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Total Students</th>
                            <th>No Workplace</th>
                            <th>No Attendance (3 Days)</th>
                            <th>Missing Pre-Req Docs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sectionStats)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.6);">
                                    No sections assigned.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sectionStats as $index => $stat): ?>
                                <tr class="expandable" onclick="toggleDetails(<?php echo $index; ?>)">
                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($stat['section']['section_code']); ?>
                                        </strong>
                                        <br>
                                        <small style="color: rgba(255,255,255,0.7);">
                                            <?php echo htmlspecialchars($stat['section']['section_name']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge success">
                                            <?php echo $stat['total_students']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($stat['no_workplace'] > 0): ?>
                                            <span class="badge warning">
                                                <?php echo $stat['no_workplace']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.5);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($stat['no_attendance_3days'] > 0): ?>
                                            <span class="badge danger">
                                                <?php echo $stat['no_attendance_3days']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.5);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($stat['missing_prereq'] > 0): ?>
                                            <span class="badge danger">
                                                <?php echo $stat['missing_prereq']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: rgba(255,255,255,0.5);">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-chevron-down" id="icon-<?php echo $index; ?>"></i>
                                    </td>
                                </tr>
                                <tr class="details-row" id="details-<?php echo $index; ?>">
                                    <td colspan="6">
                                        <div
                                            style="padding: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                                            <?php if (!empty($stat['no_attendance_students'])): ?>
                                                <div>
                                                    <h4 style="color: var(--accent-clr); margin-bottom: 0.5rem;">
                                                        <i class="fas fa-calendar-times"></i> No Attendance (3 Days)
                                                    </h4>
                                                    <div class="student-list">
                                                        <?php foreach ($stat['no_attendance_students'] as $student): ?>
                                                            <div class="student-list-item">
                                                                <?php echo htmlspecialchars($student); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($stat['missing_prereq_students'])): ?>
                                                <div>
                                                    <h4 style="color: var(--accent-clr); margin-bottom: 0.5rem;">
                                                        <i class="fas fa-file-excel"></i> Missing Pre-Required Documents
                                                    </h4>
                                                    <div class="student-list">
                                                        <?php foreach ($stat['missing_prereq_students'] as $student): ?>
                                                            <div class="student-list-item">
                                                                <?php echo htmlspecialchars($student['name']); ?>
                                                                <small style="color: #e74c3c; display: block; margin-top: 0.25rem;">
                                                                    Missing
                                                                    <?php echo $student['missing']; ?> document(s)
                                                                </small>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleDetails(index) {
            const detailsRow = document.getElementById('details-' + index);
            const icon = document.getElementById('icon-' + index);

            if (detailsRow.classList.contains('show')) {
                detailsRow.classList.remove('show');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                detailsRow.classList.add('show');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    </script>
</body>

</html>