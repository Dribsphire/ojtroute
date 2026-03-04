<?php
session_start();
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/InstructorService.php';

$instructorService = new \App\Services\InstructorService();
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    header('Location: admin_userpage.php');
    exit();
}

$student = $instructorService->getStudentDetails($student_id);

if (!$student) {
    header('Location: admin_userpage.php');
    exit();
}

// Calculate total points earned from document submissions
$db = $instructorService->getDb();
$stmt = $db->prepare("
    SELECT COALESCE(SUM(points), 0) as total_points
    FROM document_submissions
    WHERE student_id = :student_id
    AND status = 'approved'
    AND points IS NOT NULL
");
$stmt->execute([':student_id' => $student['student_db_id']]);
$pointsData = $stmt->fetch();
$student['total_points'] = $pointsData['total_points'];

$approvedDocs = $instructorService->getApprovedPreReqDocuments($student['student_db_id']);

// Fetch attendance records for calendar
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');

if ($selectedMonth < 1)
    $selectedMonth = 1;
if ($selectedMonth > 12)
    $selectedMonth = 12;

$firstDayTs = strtotime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth));
$daysInMonth = (int) date('t', $firstDayTs);
$firstDow = (int) date('w', $firstDayTs);

$prevYear = $selectedYear;
$prevMonth = $selectedMonth - 1;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextYear = $selectedYear;
$nextMonth = $selectedMonth + 1;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthLabel = date('F Y', $firstDayTs);

// Fetch attendance records
$attendance = [];
try {
    $config = require __DIR__ . '/../../config/database.php';
    $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
    $endDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $daysInMonth);

    $stmt = $db->prepare("
        SELECT 
            attendance_date,
            block_type,
            photo_path,
            status,
            time_in,
            time_out,
            hours
        FROM attendance_records 
        WHERE student_id = ? 
        AND attendance_date BETWEEN ? AND ?
        ORDER BY attendance_date, block_type
    ");
    $stmt->execute([$student['student_db_id'], $startDate, $endDate]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        $date = $record['attendance_date'];

        if (!isset($attendance[$date])) {
            $attendance[$date] = [
                'status' => 'present',
                'blocks' => [
                    'regular' => null,
                    'overtime' => null
                ],
                'details' => [
                    'regular' => null,
                    'overtime' => null
                ]
            ];
        }

        $blockType = $record['block_type'];
        $attendance[$date]['blocks'][$blockType] = $record['photo_path'];
        $attendance[$date]['details'][$blockType] = [
            'time_in' => $record['time_in'],
            'time_out' => $record['time_out'],
            'hours' => $record['hours'],
            'status' => $record['status']
        ];
    }

    // Fetch excused dates from excused_dates table
    try {
        $stmt = $db->prepare("
            SELECT 
                excused_date,
                hours_added,
                reason,
                created_at
            FROM excused_dates
            WHERE student_id = ?
            AND excused_date BETWEEN ? AND ?
            ORDER BY excused_date
        ");
        $stmt->execute([$student['student_db_id'], $startDate, $endDate]);
        $excusedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($excusedRecords as $excuse) {
            $excusedDate = $excuse['excused_date'];

            // Add excuse data to the date (even if attendance exists)
            if (!isset($attendance[$excusedDate])) {
                // No attendance - mark as excused only
                $attendance[$excusedDate] = [
                    'status' => 'excused',
                    'blocks' => [
                        'regular' => null,
                        'overtime' => null
                    ],
                    'details' => [
                        'regular' => null,
                        'overtime' => null
                    ],
                    'excuse_reason' => $excuse['reason'],
                    'excuse_hours' => $excuse['hours_added'],
                    'excuse_date' => $excuse['created_at'],
                    'has_excuse' => true
                ];
            } else {
                // Attendance exists - add excuse data to existing record
                $attendance[$excusedDate]['excuse_reason'] = $excuse['reason'];
                $attendance[$excusedDate]['excuse_hours'] = $excuse['hours_added'];
                $attendance[$excusedDate]['excuse_date'] = $excuse['created_at'];
                $attendance[$excusedDate]['has_excuse'] = true;
            }
        }
    } catch (Exception $e) {
        error_log('Excused dates fetch error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Instructor calendar fetch error: ' . $e->getMessage());
}

// Pagination Logic
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$totalDocs = count($approvedDocs);
$totalPages = ceil($totalDocs / $perPage);
$offset = ($page - 1) * $perPage;
$displayDocs = array_slice($approvedDocs, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['full_name']); ?> - Student Information | Admin Panel</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <style>
        .section {
            background: #1e1f2d;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--accent-clr);
            border-bottom: 1px solid var(--line-clr);
            padding-bottom: 0.5rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-clr);
        }

        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            color: var(--text-clr);
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            margin-bottom: 0.5rem;
        }

        .detail-label {
            font-weight: 500;
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
        }

        .detail-value {
            color: var(--text-clr);
            word-break: break-all;
            overflow-wrap: break-word;
        }

        .workplace-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .minimap {
            background: #2a2b3a;
            border-radius: 8px;
            height: 200px;
            width: 100%;
            color: var(--secondary-text-clr);
            grid-column: 1 / -1;
        }

        #map {
            height: 100%;
            width: 100%;
            border-radius: 8px;
        }

        /* Calendar and other styles kept as is */
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
            padding: 0.25rem;
        }

        .calendar-header {
            text-align: center;
            font-weight: 600;
            padding: 0.5rem;
            background: #2a2b3a;
            border-radius: 4px;
        }

        .calendar-day {
            aspect-ratio: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            background: #2a2b3a;
            padding: 0.1rem;
            font-size: 0.8rem;
            min-height: 30px;
        }

        .calendar-day small {
            font-size: 0.65rem;
            line-height: 1;
            margin-top: 2px;
        }

        .present {
            background: #1ad21c33;
            color: var(--accent-clr);
        }

        .absent {
            background: #ff4d4d33;
            color: #ff4d4d;
        }

        .excused {
            background: #4da6ff33;
            color: #4da6ff;
        }

        /* Dates with both attendance and excuse - show blue border */
        .calendar-day.has-excuse.present {
            border-left: 4px solid #4da6ff;
            position: relative;
        }

        .calendar-day.has-excuse.present::after {
            content: '★';
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.6rem;
            color: #4da6ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        th {
            color: var(--secondary-text-clr);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status-pending {
            color: #ffc107;
        }

        .status-approved {
            color: #1ad21c;
        }

        .status-rejected {
            color: #ff4d4d;
        }
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>
    <div class="container">
        <button onclick="window.location.href='admin_userpage.php'"
            style="margin-bottom: 1rem; padding: 0.5rem 1rem; border: none; background: var(--accent-clr); color: white; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-arrow-left"></i> Back to User List
        </button>

        <!-- Student Information Section -->
        <div class="section">
            <h3 class="section-title">Student Information</h3>
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars(!empty($student['profile_pic_path']) ? $student['profile_pic_path'] : '../../storage/images/default_profile.jpg'); ?>"
                    alt="<?php echo htmlspecialchars($student['full_name']); ?>" class="profile-pic">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <p><?php echo htmlspecialchars($student['section_name']); ?></p>
                </div>
            </div>
            <div class="profile-details">
                <div class="detail-item">
                    <div class="detail-label">School ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['school_id']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['contact'] ?: 'Not provided'); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Total Hours Rendered</div>
                    <div class="detail-value"><?php echo number_format($student['total_hours'], 1); ?> hours</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Total Points Earned</div>
                    <div class="detail-value">
                        <?php echo number_format($student['total_points'], 1); ?> points
                    </div>
                </div>
            </div>
        </div>

        <!-- Workplace Information Section -->
        <div class="section">
            <h3 class="section-title">Workplace Information</h3>
            <div class="workplace-grid">
                <div>
                    <div class="detail-item">
                        <div class="detail-label">Company Name</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($student['company_name'] ?: 'Not assigned'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Company Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['company_address'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Supervisor's Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['company_head'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Supervisor Position</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($student['supervisor_position'] ?: 'N/A'); ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="detail-item">
                        <div class="detail-label">Immediate Head of Trainee</div>
                        <div class="detail-value"><?php echo htmlspecialchars($student['head_trainee'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Head of Trainee Position</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($student['head_trainee_position'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Head of Trainee Contact</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($student['head_trainee_contact'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Head of Trainee Email</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($student['head_trainee_email'] ?: 'N/A'); ?>
                        </div>
                    </div>
                </div>
                <div class="minimap">
                    <?php if ($student['latitude'] && $student['longitude']): ?>
                        <div id="map"></div>
                    <?php else: ?>
                        <div
                            style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
                            <i class="fas fa-map-marker-alt" style="font-size: 2rem; opacity: 0.5;"></i>
                            <span>No location data available</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Attendance Calendar Section -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?id=<?php echo $student_id; ?>&year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>"
                        style="padding: 0.5rem 1rem; border: 1px solid var(--line-clr); background: none; color: var(--text-clr); border-radius: 4px; cursor: pointer; text-decoration: none;">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                    <a href="?id=<?php echo $student_id; ?>&year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>"
                        style="padding: 0.5rem 1rem; border: 1px solid var(--line-clr); background: none; color: var(--text-clr); border-radius: 4px; cursor: pointer; text-decoration: none;">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <h3 class="section-title" style="margin: 0;">Attendance -
                    <?php echo htmlspecialchars($monthLabel); ?>
                </h3>
                <div style="width: 150px;"></div>
            </div>

            <div class="calendar">
                <?php
                $dowLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($dowLabels as $l) {
                    echo '<div class="calendar-header">' . htmlspecialchars($l) . '</div>';
                }

                for ($i = 0; $i < $firstDow; $i++) {
                    echo '<div class="calendar-day"></div>';
                }

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateKey = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                    $status = isset($attendance[$dateKey]) ? $attendance[$dateKey]['status'] : null;
                    $isPresent = ($status === 'present');
                    $isExcused = ($status === 'excused');
                    $hasExcuse = isset($attendance[$dateKey]['has_excuse']) && $attendance[$dateKey]['has_excuse'];

                    $classes = 'calendar-day';
                    if ($status)
                        $classes .= ' ' . $status;
                    if ($hasExcuse)
                        $classes .= ' has-excuse';
                    if ($isPresent || $isExcused)
                        $classes .= ' clickable';

                    $blocks = isset($attendance[$dateKey]) ? $attendance[$dateKey]['blocks'] : ['regular' => null, 'overtime' => null];
                    $details = isset($attendance[$dateKey]) ? $attendance[$dateKey]['details'] : ['regular' => null, 'overtime' => null];

                    $r = $blocks['regular'] ?? null;
                    $o = $blocks['overtime'] ?? null;

                    // Calculate total hours for the day
                    $totalHours = 0;
                    if (isset($details['regular']) && $details['regular'] && $details['regular']['hours']) {
                        $totalHours += floatval($details['regular']['hours']);
                    }
                    if (isset($details['overtime']) && $details['overtime'] && $details['overtime']['hours']) {
                        $totalHours += floatval($details['overtime']['hours']);
                    }

                    // Get excuse data if available
                    $excuseReason = isset($attendance[$dateKey]['excuse_reason']) ? $attendance[$dateKey]['excuse_reason'] : '';
                    $excuseHours = isset($attendance[$dateKey]['excuse_hours']) ? $attendance[$dateKey]['excuse_hours'] : 0;

                    echo '<div class="' . htmlspecialchars($classes) . '"'
                        . ' data-date="' . htmlspecialchars($dateKey) . '"'
                        . ' data-status="' . htmlspecialchars((string) $status) . '"'
                        . ' data-regular="' . htmlspecialchars((string) $r) . '"'
                        . ' data-overtime="' . htmlspecialchars((string) $o) . '"'
                        . ' data-total-hours="' . htmlspecialchars(number_format($totalHours, 2)) . '"'
                        . ' data-excuse-reason="' . htmlspecialchars($excuseReason) . '"'
                        . ' data-excuse-hours="' . htmlspecialchars((string) $excuseHours) . '"'
                        . ' data-has-excuse="' . ($hasExcuse ? 'true' : 'false') . '"'
                        . ' style="' . (($isPresent || $isExcused) ? 'cursor: pointer;' : '') . '">';

                    if ($status === 'present') {
                        echo (int) $day . '<br><small>Present</small>';
                    } elseif ($status === 'absent') {
                        echo (int) $day . '<br><small>Absent</small>';
                    } elseif ($status === 'excused') {
                        echo (int) $day . '<br><small>Excused</small>';
                    } else {
                        echo (int) $day;
                    }

                    echo '</div>';
                }
                ?>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1.5rem; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #1ad21c33; border-radius: 3px;"></div>
                    <span>Present</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #ff4d4d33; border-radius: 3px;"></div>
                    <span>Absent</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: #4da6ff33; border-radius: 3px;"></div>
                    <span>Excused</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #4da6ff; font-size: 1.1rem; line-height: 1;">★</span>
                    <span>Also Excused</span>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 class="section-title" style="margin: 0;">Submitted Documents</h3>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Points</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($displayDocs)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#888;">No approved pre-required
                                documents found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($displayDocs as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['name']); ?></td>
                                <td><?php echo strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION)); ?></td>
                                <td><?php echo date('M d, Y', strtotime($doc['submitted_at'])); ?></td>
                                <td><span class="status-approved">Approved</span></td>
                                <td>
                                    <?php echo $doc['points'] !== null ? number_format($doc['points'], 1) . ' pts' : '-'; ?>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="icon-btn"
                                        style="color: var(--accent-clr); margin-right: 10px;" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button type="button" class="icon-btn"
                                        style="color: #4da6ff; background:none; border:none; cursor:pointer;"
                                        onclick="window.open('<?php echo htmlspecialchars($doc['file_path']); ?>', '_blank')"
                                        title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem;">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $student_id; ?>&page=<?php echo $page - 1; ?>"
                            style="padding: 0.5rem 1rem; border: 1px solid var(--line-clr); background: none; color: var(--text-clr); border-radius: 4px; cursor: pointer; text-decoration: none;">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?id=<?php echo $student_id; ?>&page=<?php echo $i; ?>"
                            style="padding: 0.5rem 1rem; border: 1px solid <?php echo $i === $page ? 'var(--accent-clr)' : 'var(--line-clr)'; ?>; background: <?php echo $i === $page ? 'var(--accent-clr)' : 'none'; ?>; color: <?php echo $i === $page ? 'white' : 'var(--text-clr)'; ?>; border-radius: 4px; cursor: pointer; text-decoration: none;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $student_id; ?>&page=<?php echo $page + 1; ?>"
                            style="padding: 0.5rem 1rem; border: 1px solid var(--line-clr); background: none; color: var(--text-clr); border-radius: 4px; cursor: pointer; text-decoration: none;">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- Attendance Details Modal -->
        <div id="attendanceModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="attendanceModalTitle">Attendance Details - <span id="attendanceDate"></span></h3>

                <div id="excuseDetailsContainer"></div>

                <!-- Total Hours Summary -->
                <div id="dayHoursSummary" style="background: rgba(26, 210, 28, 0.1); border: 1px solid rgba(26, 210, 28, 0.3); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600; color: var(--accent-clr);"><i class="fas fa-clock"></i> Total Hours Worked</span>
                    <span id="dayTotalHours" style="font-size: 1.2rem; font-weight: 700; color: var(--accent-clr);">0.00 hrs</span>
                </div>

                <div class="attendance-blocks">
                    <div class="attendance-block">
                        <h4>Regular Block</h4>
                        <div class="attendance-image no-overtime">
                            <i class="fas fa-clock"></i>
                            <p>No Record</p>
                        </div>
                        <div class="attendance-time">-</div>
                        <div class="attendance-hours">Hours: -</div>
                        <div class="attendance-status">Status: <span class="muted">-</span></div>
                    </div>

                    <div class="attendance-block">
                        <h4>Overtime</h4>
                        <div class="attendance-image no-overtime">
                            <i class="fas fa-clock"></i>
                            <p>No Overtime Recorded</p>
                        </div>
                        <div class="attendance-time">-</div>
                        <div class="attendance-hours">Hours: -</div>
                        <div class="attendance-status">Status: <span class="muted">-</span></div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Modal Styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.7);
                overflow: auto;
            }

            .modal-content {
                background-color: #1e1f2d;
                margin: 5% auto;
                padding: 2rem;
                border-radius: 8px;
                width: 80%;
                max-width: 900px;
                color: var(--text-clr);
                position: relative;
            }

            .close {
                position: absolute;
                right: 1.5rem;
                top: 1rem;
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close:hover {
                color: var(--accent-clr);
            }

            .attendance-blocks {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
                margin-top: 1.5rem;
            }

            .attendance-block {
                background: #2a2b3a;
                border-radius: 8px;
                padding: 1rem;
                text-align: center;
            }

            .attendance-block h4 {
                margin-top: 0;
                color: var(--accent-clr);
                border-bottom: 1px solid var(--line-clr);
                padding-bottom: 0.5rem;
                margin-bottom: 1rem;
            }

            .attendance-image {
                height: 200px;
                background: #1e1f2d;
                border-radius: 4px;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .attendance-image img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }

            .no-overtime {
                flex-direction: column;
                color: var(--secondary-text-clr);
            }

            .no-overtime i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }

            .attendance-time,
            .attendance-hours,
            .attendance-status {
                margin: 0.5rem 0;
                font-size: 0.9rem;
            }

            .status-present {
                color: #1ad21c;
            }
        </style>

        <script>
            // Initialize the map
            document.addEventListener('DOMContentLoaded', function () {
                <?php if ($student['latitude'] && $student['longitude']): ?>
                    // Initialize map centered on the workplace location
                    const lat = <?php echo json_encode(floatval($student['latitude'])); ?>;
                    const lng = <?php echo json_encode(floatval($student['longitude'])); ?>;
                    const companyName = <?php echo json_encode($student['company_name']); ?>;

                    const map = L.map('map').setView([lat, lng], 15);

                    // Add OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);

                    // Add a marker with popup
                    const marker = L.circle([lat, lng], {
                        color: '#1ad21c',
                        fillColor: '#1ad21c',
                        fillOpacity: 0.5,
                        radius: 40
                    }).addTo(map);

                    const centerMarker = L.marker([lat, lng]).addTo(map); // Optional: add a pin center for clarity
                    centerMarker.bindPopup(companyName).openPopup();
                <?php endif; ?>

                // Attendance data from PHP
                const attendanceData = <?php echo json_encode($attendance, JSON_UNESCAPED_SLASHES); ?>;

                // Add any other JavaScript functionality here
                // Tooltips and click handlers for calendar
                const calendarDays = document.querySelectorAll('.calendar-day.clickable');
                const modal = document.getElementById('attendanceModal');
                const span = document.getElementsByClassName('close')[0];
                const attendanceDate = document.getElementById('attendanceDate');

                // Format date as "Month Day, Year"
                function formatDate(dateStr) {
                    const date = new Date(dateStr + 'T00:00:00');
                    const options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    return date.toLocaleDateString('en-US', options);
                }

                function formatTime(timeStr) {
                    if (!timeStr) return '-';
                    const date = new Date(timeStr);
                    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                }

                calendarDays.forEach(day => {
                    day.addEventListener('click', function () {
                        const dateKey = this.getAttribute('data-date');
                        const row = attendanceData[dateKey];

                        // Allow opening modal if status is present OR excused, or if it has an excuse
                        const status = this.getAttribute('data-status');
                        const hasExcuse = this.getAttribute('data-has-excuse') === 'true';

                        if (!row && !hasExcuse) return;

                        if (attendanceDate) {
                            attendanceDate.textContent = formatDate(dateKey);
                        }

                        const modalTitle = document.getElementById('attendanceModalTitle');
                        const excuseContainer = document.getElementById('excuseDetailsContainer');

                        // Reset title
                        if (modalTitle && attendanceDate) {
                            // Title is inside h3, reset it
                            modalTitle.innerHTML = 'Attendance Details - <span id="attendanceDate">' + formatDate(dateKey) + '</span>';
                        }

                        // Handle Excuse Data
                        const excuseReason = this.getAttribute('data-excuse-reason');
                        const excuseHours = this.getAttribute('data-excuse-hours');

                        if (excuseContainer) {
                            excuseContainer.innerHTML = ''; // Clear previous content

                            if (hasExcuse || status === 'excused') {
                                if (modalTitle) {
                                    modalTitle.innerHTML = 'Attendance Details - <span id="attendanceDate">' + formatDate(dateKey) + '</span> <span style="color: #4da6ff;">★ Associated Excuse</span>';
                                }

                                const bannerHtml = `
                                    <div style="line-height: 1.6; background: rgba(77, 166, 255, 0.15); padding: 0.75rem; border-radius: 6px; border: 1px solid rgba(77, 166, 255, 0.4); margin-bottom: 1.5rem;">
                                        <div style="font-size: 0.9rem; color: #4da6ff; font-weight: 600; margin-bottom: 0.25rem;">
                                            <i class="fas fa-star"></i> ${status === 'present' ? 'This date was also marked as excused' : 'Excused Absence'}
                                        </div>
                                        <div style="font-size: 0.85rem;"><strong>Hours Credited:</strong> ${parseFloat(excuseHours).toFixed(2)} hours</div>
                                        <div style="font-size: 0.85rem;"><strong>Reason:</strong> ${excuseReason}</div>
                                    </div>
                                `;
                                excuseContainer.innerHTML = bannerHtml;
                            }
                        }

                        // Get all attendance blocks
                        const allBlocks = document.querySelectorAll('.attendance-block');
                        if (allBlocks.length < 2) {
                            console.error('Modal structure incomplete');
                            return;
                        }

                        // Update total hours summary
                        const dayHoursSummary = document.getElementById('dayHoursSummary');
                        const dayTotalHours = document.getElementById('dayTotalHours');
                        let totalHrs = 0;

                        // Update modal content for each block
                        const blocks = ['regular', 'overtime'];
                        const blockLabels = { 'regular': 'Regular', 'overtime': 'Overtime' };
                        blocks.forEach((blockName, index) => {
                            const blockDiv = allBlocks[index];
                            if (!blockDiv) return;

                            const imgDiv = blockDiv.querySelector('.attendance-image');
                            const timeDiv = blockDiv.querySelector('.attendance-time');
                            const hoursDiv = blockDiv.querySelector('.attendance-hours');
                            const statusDiv = blockDiv.querySelector('.attendance-status');

                            if (!imgDiv || !timeDiv || !statusDiv) {
                                console.error(`Missing elements in ${blockName} block`);
                                return;
                            }

                            const photo = row ? row.blocks[blockName] : null;
                            const details = row ? row.details[blockName] : null;

                            if (photo && details) {
                                imgDiv.innerHTML = `<img src="${photo}" alt="${blockLabels[blockName]} Attendance" class="attendance-selfie">`;
                                imgDiv.classList.remove('no-overtime');
                                timeDiv.innerHTML = `Time In: ${formatTime(details.time_in)}<br>Time Out: ${formatTime(details.time_out)}`;
                                const hrs = details.hours ? parseFloat(details.hours).toFixed(2) : '0.00';
                                if (hoursDiv) hoursDiv.innerHTML = `Hours: <strong>${hrs} hrs</strong>`;
                                totalHrs += details.hours ? parseFloat(details.hours) : 0;
                                // Map status to display
                                let statusText = details.status || 'Present';
                                let statusClass = 'status-present';
                                if (statusText === 'ongoing') { statusText = 'Ongoing'; statusClass = 'status-pending'; }
                                else if (statusText === 'completed') { statusText = 'Completed'; statusClass = 'status-approved'; }
                                statusDiv.innerHTML = `Status: <span class="${statusClass}">${statusText}</span>`;
                            } else if (details && !photo) {
                                // Has record but no photo
                                imgDiv.innerHTML = '<i class="fas fa-user-clock"></i><p>No Photo</p>';
                                imgDiv.classList.add('no-overtime');
                                timeDiv.innerHTML = `Time In: ${formatTime(details.time_in)}<br>Time Out: ${formatTime(details.time_out)}`;
                                const hrs = details.hours ? parseFloat(details.hours).toFixed(2) : '0.00';
                                if (hoursDiv) hoursDiv.innerHTML = `Hours: <strong>${hrs} hrs</strong>`;
                                totalHrs += details.hours ? parseFloat(details.hours) : 0;
                                let statusText = details.status || 'Present';
                                let statusClass = 'status-present';
                                if (statusText === 'ongoing') { statusText = 'Ongoing'; statusClass = 'status-pending'; }
                                else if (statusText === 'completed') { statusText = 'Completed'; statusClass = 'status-approved'; }
                                statusDiv.innerHTML = `Status: <span class="${statusClass}">${statusText}</span>`;
                            } else {
                                const noLabel = blockName === 'overtime' ? 'No Overtime Recorded' : 'No Record';
                                imgDiv.innerHTML = `<i class="fas fa-clock"></i><p>${noLabel}</p>`;
                                imgDiv.classList.add('no-overtime');
                                timeDiv.innerHTML = '-';
                                if (hoursDiv) hoursDiv.innerHTML = 'Hours: -';
                                statusDiv.innerHTML = 'Status: <span class="muted">-</span>';
                            }
                        });

                        // Update total hours display
                        if (dayTotalHours) {
                            dayTotalHours.textContent = totalHrs.toFixed(2) + ' hrs';
                        }
                        if (dayHoursSummary) {
                            dayHoursSummary.style.display = (status === 'present') ? 'flex' : 'none';
                        }

                        if (modal) {
                            modal.style.display = 'block';
                        }
                    });
                });

                // Close modal when clicking the X
                if (span) {
                    span.onclick = function () {
                        modal.style.display = 'none';
                    }
                }

                // Close modal when clicking outside the modal content
                window.onclick = function (event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
            });
        </script>
</body>

</html>