<?php
session_start();
require_once __DIR__ . '/../../app/services/StudentService.php';

$studentService = new \App\Services\StudentService();
$userId = $_SESSION['user_id'] ?? 0;
$dbId = $studentService->getStudentDbId($userId);

// Month selection (defaults to current month)
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');

if ($selectedMonth < 1)
    $selectedMonth = 1;
if ($selectedMonth > 12)
    $selectedMonth = 12;

$firstDayTs = strtotime(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth));
$daysInMonth = (int) date('t', $firstDayTs);
$firstDow = (int) date('w', $firstDayTs); // 0=Sun

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

// Fetch attendance records for the selected month
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
    $stmt->execute([$dbId, $startDate, $endDate]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data by date
    foreach ($records as $record) {
        $date = $record['attendance_date'];

        if (!isset($attendance[$date])) {
            $attendance[$date] = [
                'status' => 'present', // If there's any record, mark as present
                'blocks' => [
                    'morning' => null,
                    'afternoon' => null,
                    'overtime' => null
                ],
                'details' => [
                    'morning' => null,
                    'afternoon' => null,
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
        if (!isset($db)) {
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
            $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        }

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
        $stmt->execute([$dbId, $startDate, $endDate]);
        $excusedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($excusedRecords as $excuse) {
            $excusedDate = $excuse['excused_date'];

            // Add excuse data to the date (even if attendance exists)
            if (!isset($attendance[$excusedDate])) {
                // No attendance - mark as excused only
                $attendance[$excusedDate] = [
                    'status' => 'excused',
                    'blocks' => [
                        'morning' => null,
                        'afternoon' => null,
                        'overtime' => null
                    ],
                    'details' => [
                        'morning' => null,
                        'afternoon' => null,
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
    error_log('Calendar fetch error: ' . $e->getMessage());
}

// Fetch student information for DTR
$studentInfo = [];
try {
    if (!isset($db)) {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
        $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    $stmt = $db->prepare("
        SELECT 
            u.full_name,
            u.school_id,
            s.section_name,
            sw.company_name,
            sw.company_head,
            COALESCE(os.manual_adjustment_hours, 0) as manual_hours
        FROM students st
        JOIN users u ON st.user_id = u.id
        LEFT JOIN sections s ON u.section_id = s.id
        LEFT JOIN student_workplaces sw ON st.id = sw.student_id
        LEFT JOIN ojt_summaries os ON st.id = os.student_id
        WHERE st.id = ?
        ORDER BY sw.id DESC
        LIMIT 1
    ");
    $stmt->execute([$dbId]);
    $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Student info fetch error: ' . $e->getMessage());
}

// Fetch all excused dates for this student (for DTR printing)
$allExcusedDates = [];
try {
    if (!isset($db)) {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
        $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    }

    $stmt = $db->prepare("
        SELECT 
            excused_date,
            hours_added,
            reason
        FROM excused_dates
        WHERE student_id = ?
        ORDER BY excused_date
    ");
    $stmt->execute([$dbId]);
    $allExcusedDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Excused dates fetch error for DTR: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - OJTRoute System</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CRITICAL FIX: Add hours validation utilities to prevent frontend crashes -->
    <script src="../js/hours-validation.js"></script>
    <style>
        .calendar-card {
            background: var(--hover-clr);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .calendar-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .calendar-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .calendar-actions {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .cal-btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: .55rem .85rem;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .cal-btn:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        .cal-btn.primary {
            border-color: var(--accent-clr);
            color: var(--accent-clr);
        }

        .legend {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 1.5rem 0 0;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: var(--text-clr);
            font-size: .95rem;
        }

        .legend-box {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        .legend-box.present {
            background: #1ad21c33;
        }

        .legend-box.absent {
            background: #ff4d4d33;
        }

        .legend-box.excused {
            background: #4da6ff33;
        }

        /* Calendar display style based on instructor/student_information.php */
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

        .calendar-day.present.clickable {
            cursor: pointer;
        }

        .calendar-day.present.clickable:hover {
            outline: 2px solid rgba(26, 210, 28, 0.45);
        }

        .calendar-day.excused.clickable {
            cursor: pointer;
        }

        .calendar-day.excused.clickable:hover {
            outline: 2px solid rgba(77, 166, 255, 0.45);
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

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            padding: 1.5rem;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            width: min(980px, 96vw);
            max-height: 90vh;
            overflow: auto;
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-title {
            margin: 0;
            font-size: 1.05rem;
        }

        .modal-close {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: .35rem .65rem;
            border-radius: 8px;
            cursor: pointer;
        }

        .modal-body {
            padding: 1.25rem;
        }

        .block-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media(max-width: 900px) {
            .block-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 1rem;
            }
        }

        .block-card {
            border: 1px solid var(--line-clr);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.03);
        }

        .block-card h4 {
            margin: 0;
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--line-clr);
            font-size: 0.95rem;
        }

        .block-img-wrap {
            padding: .75rem 1rem 1rem;
        }

        .block-img {
            width: 100%;
            height: 220px;
            border-radius: 10px;
            object-fit: cover;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .no-photo {
            width: 100%;
            height: 220px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-text-clr);
            border: 1px dashed rgba(255, 255, 255, 0.12);
            background: rgba(0, 0, 0, 0.18);
            font-size: .9rem;
        }

        .print-row {
            display: flex;
            align-items: flex-end;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .form-field label {
            color: var(--secondary-text-clr);
            font-size: .9rem;
        }

        .form-field select {
            min-width: 220px;
            padding: .65rem .75rem;
            border-radius: 8px;
            border: 1px solid var(--line-clr);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-clr);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .calendar-card {
                padding: 0.75rem;
                height: 34rem;
                width: 19rem;
                margin: 0 auto;
            }

            .calendar-topbar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 0.75rem;
            }

            .calendar-title {
                font-size: 0.95rem;
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .calendar-actions {
                justify-content: center;
                gap: 0.35rem;
            }

            .cal-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .calendar {
                gap: 0.1rem;
                padding: 0.1rem;
            }

            .calendar-header {
                font-size: 0.65rem;
                padding: 0.3rem 0.1rem;
            }

            .calendar-day {
                font-size: 0.7rem;
                min-height: 18px;
                padding: 0.05rem;
            }

            /* Hide status text (Present, Absent, Excused) on responsive */
            .calendar-day small {
                display: none;
            }

            .legend {
                gap: 0.5rem;
                margin: 0.75rem 0 0;
                font-size: 0.7rem;
            }

            .legend-item {
                gap: 0.3rem;
            }

            .legend-box {
                width: 12px;
                height: 12px;
            }
        }

        @media (max-width: 480px) {
            .calendar-card {
                padding: 0.75rem;
                height: 34rem;
                width: 19rem;
                margin: 0 auto;
            }

            .calendar-topbar {
                margin-bottom: 0.5rem;
            }

            .calendar-title {
                font-size: 0.9rem;
            }

            .cal-btn {
                padding: 0.35rem 0.5rem;
                font-size: 0.7rem;
            }

            .cal-btn i {
                font-size: 0.7rem;
            }

            .calendar {
                gap: 0.08rem;
                padding: 0.08rem;
            }

            .calendar-header {
                font-size: 0.6rem;
                padding: 0.25rem 0.05rem;
            }

            .calendar-day {
                font-size: 0.65rem;
                min-height: 16px;
                padding: 0.03rem;
            }

            /* Hide status text (Present, Absent, Excused) on responsive */
            .calendar-day small {
                display: none;
            }

            .legend {
                gap: 0.4rem;
                margin: 0.5rem 0 0;
                font-size: 0.65rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .legend-item {
                gap: 0.25rem;
            }

            .legend-box {
                width: 10px;
                height: 10px;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'student_nav.php'; ?>
    <main>
        <div class="calendar-container">
            <h1>Attendance Calendar</h1><br>

            <div class="calendar-card">
                <div class="calendar-topbar">
                    <div class="calendar-actions">
                        <a class="cal-btn" href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>"
                            aria-label="Previous Month">
                            <i class="fa-solid fa-chevron-left"></i>
                            Prev
                        </a>
                        <a class="cal-btn" href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>"
                            aria-label="Next Month">
                            Next
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>

                    <h2 class="calendar-title"><?php echo htmlspecialchars($monthLabel); ?></h2>

                    <div class="calendar-actions">
                        <button id="openPrintModal" class="cal-btn primary" type="button">
                            <i class="fa-solid fa-print"></i>
                            Print
                        </button>
                    </div>
                </div>

                <div class="calendar" role="grid" aria-label="Attendance Calendar">
                    <?php
                    $dowLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach ($dowLabels as $l) {
                        echo '<div class="calendar-header" role="columnheader">' . htmlspecialchars($l) . '</div>';
                    }

                    for ($i = 0; $i < $firstDow; $i++) {
                        echo '<div class="calendar-day" aria-hidden="true"></div>';
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

                        $blocks = isset($attendance[$dateKey]) ? $attendance[$dateKey]['blocks'] : ['morning' => null, 'afternoon' => null, 'overtime' => null];
                        $m = $blocks['morning'] ?? null;
                        $a = $blocks['afternoon'] ?? null;
                        $o = $blocks['overtime'] ?? null;

                        // Get excuse data if available
                        $excuseReason = isset($attendance[$dateKey]['excuse_reason']) ? $attendance[$dateKey]['excuse_reason'] : '';
                        $excuseHours = isset($attendance[$dateKey]['excuse_hours']) ? $attendance[$dateKey]['excuse_hours'] : 0;

                        echo '<div class="' . htmlspecialchars($classes) . '"'
                            . ' data-date="' . htmlspecialchars($dateKey) . '"'
                            . ' data-status="' . htmlspecialchars((string) $status) . '"'
                            . ' data-morning="' . htmlspecialchars((string) $m) . '"'
                            . ' data-afternoon="' . htmlspecialchars((string) $a) . '"'
                            . ' data-overtime="' . htmlspecialchars((string) $o) . '"'
                            . ' data-excuse-reason="' . htmlspecialchars($excuseReason) . '"'
                            . ' data-excuse-hours="' . htmlspecialchars((string) $excuseHours) . '"'
                            . ' data-has-excuse="' . ($hasExcuse ? 'true' : 'false') . '"'
                            . ' role="gridcell" tabindex="0">';

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

                <div class="legend" aria-label="Legend">
                    <div class="legend-item"><span class="legend-box present"></span><span>Present</span></div>
                    <div class="legend-item"><span class="legend-box excused"></span><span>Excused</span></div>
                    <div class="legend-item"><span style="color: #4da6ff; font-size: 1.1rem;">★</span><span> Present &
                            Excuse </span></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Attendance Photos Modal -->
    <div id="attendanceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="attendanceModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="attendanceModalTitle" class="modal-title">Attendance Photos</h3>
                <button type="button" class="modal-close" data-close-modal="attendanceModal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="block-grid">
                    <div class="block-card">
                        <h4>Morning Attendance</h4>
                        <div class="block-img-wrap" id="morningWrap"></div>
                        <div class="block-details" id="morningDetails"
                            style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--secondary-text-clr);"></div>
                    </div>
                    <div class="block-card">
                        <h4>Afternoon Attendance</h4>
                        <div class="block-img-wrap" id="afternoonWrap"></div>
                        <div class="block-details" id="afternoonDetails"
                            style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--secondary-text-clr);"></div>
                    </div>
                    <div class="block-card">
                        <h4>Overtime Attendance</h4>
                        <div class="block-img-wrap" id="overtimeWrap"></div>
                        <div class="block-details" id="overtimeDetails"
                            style="padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--secondary-text-clr);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Modal -->
    <div id="printModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="printModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="printModalTitle" class="modal-title">Print Monthly Attendance</h3>
                <button type="button" class="modal-close" data-close-modal="printModal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="print-row">
                    <div class="form-field">
                        <label for="printMonth">Select Month</label>
                        <select id="printMonth">
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $label = date('F', strtotime(sprintf('2025-%02d-01', $m)));
                                $selected = ($m === $selectedMonth) ? 'selected' : '';
                                echo '<option value="' . $m . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="printYear">Year</label>
                        <select id="printYear">
                            <?php
                            $years = [$selectedYear - 1, $selectedYear, $selectedYear + 1];
                            foreach ($years as $y) {
                                $selected = ((int) $y === (int) $selectedYear) ? 'selected' : '';
                                echo '<option value="' . (int) $y . '" ' . $selected . '>' . (int) $y . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <button id="printMonthlyBtn" class="cal-btn primary" type="button">
                        <i class="fa-solid fa-print"></i>
                        Print Monthly
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const attendanceData = <?php echo json_encode($attendance, JSON_UNESCAPED_SLASHES); ?>;
        const studentInfo = <?php echo json_encode($studentInfo, JSON_UNESCAPED_SLASHES); ?>;
        const allExcusedDates = <?php echo json_encode($allExcusedDates, JSON_UNESCAPED_SLASHES); ?>;

        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function createImageOrPlaceholder(src) {
            if (!src) {
                const empty = document.createElement('div');
                empty.className = 'no-photo';
                empty.textContent = 'No photo available';
                return empty;
            }
            const img = document.createElement('img');
            img.className = 'block-img';
            img.alt = 'Attendance photo';
            img.src = src;
            img.onerror = function () {
                const parent = img.parentElement;
                if (!parent) return;
                const empty = document.createElement('div');
                empty.className = 'no-photo';
                empty.textContent = 'No photo available';
                parent.replaceChildren(empty);
            };
            return img;
        }

        function openAttendancePhotos(dateKey, blocks, details) {
            const title = document.getElementById('attendanceModalTitle');
            if (title) title.textContent = `Attendance Details - ${dateKey}`;

            const morningWrap = document.getElementById('morningWrap');
            const afternoonWrap = document.getElementById('afternoonWrap');
            const overtimeWrap = document.getElementById('overtimeWrap');

            const morningDetails = document.getElementById('morningDetails');
            const afternoonDetails = document.getElementById('afternoonDetails');
            const overtimeDetails = document.getElementById('overtimeDetails');

            // Helper function to format time details
            function formatDetails(blockDetails) {
                if (!blockDetails) return '<em>No attendance record</em>';

                const timeIn = blockDetails.time_in ? new Date(blockDetails.time_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-';
                const timeOut = blockDetails.time_out ? new Date(blockDetails.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-';
                // CRITICAL FIX: Use safe parsing to prevent NaN/Infinity crashes
                const hours = blockDetails.hours ? formatHours(blockDetails.hours, 2) : '-';
                const status = blockDetails.status || '-';

                return `
                    <div style="line-height: 1.6;">
                        <div><strong>Time In:</strong> ${timeIn}</div>
                        <div><strong>Time Out:</strong> ${timeOut}</div>
                        <div><strong>Hours:</strong> ${hours} hrs</div>
                        <div><strong>Status:</strong> <span style="text-transform: capitalize;">${status}</span></div>
                    </div>
                `;
            }

            if (morningWrap) morningWrap.replaceChildren(createImageOrPlaceholder(blocks.morning));
            if (afternoonWrap) afternoonWrap.replaceChildren(createImageOrPlaceholder(blocks.afternoon));
            if (overtimeWrap) overtimeWrap.replaceChildren(createImageOrPlaceholder(blocks.overtime));

            if (morningDetails) morningDetails.innerHTML = formatDetails(details?.morning);
            if (afternoonDetails) afternoonDetails.innerHTML = formatDetails(details?.afternoon);
            if (overtimeDetails) overtimeDetails.innerHTML = formatDetails(details?.overtime);

            openModal('attendanceModal');
        }

        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                closeModal(closeBtn.getAttribute('data-close-modal'));
                return;
            }

            if (e.target.id === 'attendanceModal') {
                closeModal('attendanceModal');
                return;
            }
            if (e.target.id === 'printModal') {
                closeModal('printModal');
                return;
            }

            const dayEl = e.target.closest('.calendar-day.clickable');
            if (dayEl) {
                const dateKey = dayEl.getAttribute('data-date');
                const status = dayEl.getAttribute('data-status');

                if (status === 'excused') {
                    // Show excuse details
                    const excuseReason = dayEl.getAttribute('data-excuse-reason');
                    const excuseHours = dayEl.getAttribute('data-excuse-hours');

                    const title = document.getElementById('attendanceModalTitle');
                    if (title) title.textContent = `Excused Absence - ${dateKey}`;

                    // Clear all blocks
                    const morningWrap = document.getElementById('morningWrap');
                    const afternoonWrap = document.getElementById('afternoonWrap');
                    const overtimeWrap = document.getElementById('overtimeWrap');
                    const morningDetails = document.getElementById('morningDetails');
                    const afternoonDetails = document.getElementById('afternoonDetails');
                    const overtimeDetails = document.getElementById('overtimeDetails');

                    if (morningWrap) morningWrap.innerHTML = '<div class="no-photo">Excused Absence</div>';
                    if (afternoonWrap) afternoonWrap.innerHTML = '<div class="no-photo">Excused Absence</div>';
                    if (overtimeWrap) overtimeWrap.innerHTML = '<div class="no-photo">Excused Absence</div>';

                    const excuseInfo = `
                        <div style="line-height: 1.8; background: rgba(77, 166, 255, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(77, 166, 255, 0.3);">
                            <div style="font-size: 1rem; margin-bottom: 0.75rem; color: #4da6ff; font-weight: 600;">
                                <i class="fas fa-calendar-check"></i> Excused by Instructor
                            </div>
                            <div><strong>Hours Credited:</strong> ${formatHours(excuseHours, 2)} hours</div>
                            <div><strong>Reason:</strong> ${excuseReason}</div>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--secondary-text-clr);">
                                <i class="fas fa-info-circle"></i> This absence was approved and hours were automatically credited to your OJT record.
                            </div>
                        </div>
                    `;

                    if (morningDetails) morningDetails.innerHTML = excuseInfo;
                    if (afternoonDetails) afternoonDetails.innerHTML = '';
                    if (overtimeDetails) overtimeDetails.innerHTML = '';

                    openModal('attendanceModal');
                } else if (status === 'present') {
                    // Show attendance photos
                    const row = attendanceData[dateKey];
                    if (!row || row.status !== 'present') return;

                    const hasExcuse = dayEl.getAttribute('data-has-excuse') === 'true';
                    const excuseReason = dayEl.getAttribute('data-excuse-reason');
                    const excuseHours = dayEl.getAttribute('data-excuse-hours');

                    // If date has both attendance and excuse, show both
                    if (hasExcuse && excuseReason) {
                        const title = document.getElementById('attendanceModalTitle');
                        if (title) title.textContent = `Attendance Details - ${dateKey} ★ Also Excused`;

                        openAttendancePhotos(
                            dateKey,
                            row.blocks || { morning: null, afternoon: null, overtime: null },
                            row.details || { morning: null, afternoon: null, overtime: null }
                        );

                        // Add excuse banner at the top of morning details
                        const morningDetails = document.getElementById('morningDetails');
                        if (morningDetails) {
                            const excuseBanner = `
                                <div style="line-height: 1.6; background: rgba(77, 166, 255, 0.15); padding: 0.75rem; border-radius: 6px; border: 1px solid rgba(77, 166, 255, 0.4); margin-bottom: 0.75rem;">
                                    <div style="font-size: 0.9rem; color: #4da6ff; font-weight: 600; margin-bottom: 0.25rem;">
                                        <i class="fas fa-star"></i> This date was also marked as excused
                                    </div>
                                    <div style="font-size: 0.85rem;"><strong>Hours Credited:</strong> ${formatHours(excuseHours, 2)} hours</div>
                                    <div style="font-size: 0.85rem;"><strong>Reason:</strong> ${excuseReason}</div>
                                </div>
                            `;
                            morningDetails.innerHTML = excuseBanner + morningDetails.innerHTML;
                        }
                    } else {
                        // Regular attendance without excuse
                        openAttendancePhotos(
                            dateKey,
                            row.blocks || { morning: null, afternoon: null, overtime: null },
                            row.details || { morning: null, afternoon: null, overtime: null }
                        );
                    }
                }
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal('attendanceModal');
                closeModal('printModal');
            }

            if (e.key === 'Enter') {
                const focusedDay = document.activeElement;
                if (focusedDay && focusedDay.classList && focusedDay.classList.contains('calendar-day') && focusedDay.classList.contains('present') && focusedDay.classList.contains('clickable')) {
                    focusedDay.click();
                }
            }
        });

        document.getElementById('openPrintModal')?.addEventListener('click', () => {
            openModal('printModal');
        });

        function buildPrintHtml(year, month) {
            const monthStart = new Date(year, month - 1, 1);
            const monthLabel = monthStart.toLocaleString('en-US', { month: 'long', year: 'numeric' });
            const daysInMonth = new Date(year, month, 0).getDate();

            // Organize attendance by date
            const dateRecords = {};
            let totalHours = 0;

            for (let day = 1; day <= daysInMonth; day++) {
                const dateKey = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const item = attendanceData[dateKey];

                // Check if this date is excused
                const excusedDate = allExcusedDates ? allExcusedDates.find(excuse => {
                    const excuseDate = new Date(excuse.excused_date);
                    return excuseDate.getFullYear() === year &&
                        (excuseDate.getMonth() + 1) === month &&
                        excuseDate.getDate() === day;
                }) : null;

                dateRecords[day] = {
                    amIn: '',
                    amOut: '',
                    pmIn: '',
                    pmOut: '',
                    otIn: '',
                    otOut: '',
                    dailyHours: 0,
                    remarks: '',
                    isExcused: !!excusedDate,
                    excuseReason: excusedDate ? excusedDate.reason : '',
                    excuseHours: excusedDate ? safeParseHours(excusedDate.hours_added || 0, 24) : 0
                };

                // If date is excused, mark it and add hours
                if (excusedDate) {
                    const hours = safeParseHours(excusedDate.hours_added || 0, 24);
                    dateRecords[day].dailyHours = hours;
                    dateRecords[day].remarks = excusedDate.reason || 'Excused';
                    totalHours += hours;
                } else if (item && item.details) {
                    // Regular attendance processing (only if not excused)
                    // Morning block - only completed/approved
                    if (item.details.morning && (item.details.morning.status === 'completed' || item.details.morning.status === 'approved')) {
                        const morning = item.details.morning;
                        dateRecords[day].amIn = morning.time_in ? new Date(morning.time_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        dateRecords[day].amOut = morning.time_out ? new Date(morning.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        const hours = morning.hours ? safeParseHours(morning.hours, 12) : 0;
                        dateRecords[day].dailyHours += hours;
                        totalHours += hours;
                    }

                    // Afternoon block - only completed/approved
                    if (item.details.afternoon && (item.details.afternoon.status === 'completed' || item.details.afternoon.status === 'approved')) {
                        const afternoon = item.details.afternoon;
                        dateRecords[day].pmIn = afternoon.time_in ? new Date(afternoon.time_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        dateRecords[day].pmOut = afternoon.time_out ? new Date(afternoon.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        const hours = afternoon.hours ? safeParseHours(afternoon.hours, 12) : 0;
                        dateRecords[day].dailyHours += hours;
                        totalHours += hours;
                    }

                    // Overtime block - only completed/approved
                    if (item.details.overtime && (item.details.overtime.status === 'completed' || item.details.overtime.status === 'approved')) {
                        const overtime = item.details.overtime;
                        dateRecords[day].otIn = overtime.time_in ? new Date(overtime.time_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        dateRecords[day].otOut = overtime.time_out ? new Date(overtime.time_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
                        const hours = overtime.hours ? safeParseHours(overtime.hours, 12) : 0;
                        dateRecords[day].dailyHours += hours;
                        totalHours += hours;
                    }
                }
            }

            // Build table rows
            let dtrRows = '';
            for (let day = 1; day <= daysInMonth; day++) {
                const record = dateRecords[day];

                // If this date is excused, show "Excused" in AM/PM columns
                if (record.isExcused) {
                    dtrRows += `
                        <tr style="background-color: #e6f3ff;">
                            <td>${day}</td>
                            <td colspan="2" style="text-align: center;">Excused</td>
                            <td colspan="2" style="text-align: center;">Excused</td>
                            <td>${record.otIn}</td>
                            <td>${record.otOut}</td>
                            <td>${record.dailyHours > 0 ? record.dailyHours.toFixed(2) : ''}</td>
                            <td style="font-size: 8px;">${record.remarks}</td>
                        </tr>
                    `;
                } else {
                    // Regular attendance row
                    dtrRows += `
                        <tr>
                            <td>${day}</td>
                            <td>${record.amIn}</td>
                            <td>${record.amOut}</td>
                            <td>${record.pmIn}</td>
                            <td>${record.pmOut}</td>
                            <td>${record.otIn}</td>
                            <td>${record.otOut}</td>
                            <td>${record.dailyHours > 0 ? record.dailyHours.toFixed(2) : ''}</td>
                            <td>${record.remarks}</td>
                        </tr>
                    `;
                }
            }

            const html = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>DTR - ${monthLabel}</title>
                    <style>
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        body { font-family: Arial, sans-serif; padding: 20px; font-size: 11px; }
                        .header-img { width: 100%; max-width: 100%; margin: 0 auto 15px; display: block; height: auto; max-height: 150px; object-fit: contain; }
                        .container { max-width: 100%; margin: 0 auto; }
                        .title { text-align: center; margin-bottom: 12px; }
                        .title h1 { font-size: 16px; margin-bottom: 5px; font-weight: bold; }
                        .title h2 { font-size: 14px; font-weight: normal; }
                        .info-section { margin-bottom: 12px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
                        .info-row { display: flex; font-size: 10px; }
                        .info-label { width: 90px; font-weight: bold; }
                        .info-value { flex: 1; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9px; }
                        th, td { border: 1px solid #000; padding: 4px 2px; text-align: center; vertical-align: middle; }
                        th { background-color: #f0f0f0; font-weight: bold; font-size: 8px; line-height: 1.3; }
                        .header-group { background-color: #e0e0e0; font-weight: bold; }
                        .total-row { font-weight: bold; background-color: #f9f9f9; }
                        .signature-section { margin-top: 30px; display: flex; justify-content: space-around; }
                        .signature-box { text-align: center; font-size: 10px; }
                        .signature-line { border-top: 1px solid #000; width: 200px; margin: 40px auto 5px; }
                        .signature-label { font-weight: bold; margin-top: 3px; }
                        @media print {
                            @page { size: A4; margin: 0.5in; }
                            body { padding: 0; }
                            .header-img { max-height: 140px; page-break-inside: avoid; }
                            table { page-break-inside: auto; }
                            tr { page-break-inside: avoid; page-break-after: auto; }
                            thead { display: table-header-group; }
                            tfoot { display: table-footer-group; }
                            .signature-section { page-break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <img src="../../storage/uploads/header.png" alt="School Header" class="header-img" onerror="this.style.display='none'">
                    
                    <div class="container">
                        <div class="title">
                            <h1>DAILY TIME RECORD (DTR)</h1>
                            <h2>${monthLabel}</h2>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-row">
                                <div class="info-label">Name:</div>
                                <div class="info-value">${studentInfo.full_name || 'N/A'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Student ID:</div>
                                <div class="info-value">${studentInfo.school_id || 'N/A'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Section:</div>
                                <div class="info-value">${studentInfo.section_name || 'N/A'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Company:</div>
                                <div class="info-value">${studentInfo.company_name || 'N/A'}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Supervisor:</div>
                                <div class="info-value">${studentInfo.company_head || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th rowspan="2">DATE</th>
                                    <th colspan="2" class="header-group">AM</th>
                                    <th colspan="2" class="header-group">PM</th>
                                    <th colspan="2" class="header-group">OVERTIME</th>
                                    <th rowspan="2">TOTAL<br>DUTY<br>HOURS</th>
                                    <th rowspan="2">REMARKS</th>
                                </tr>
                                <tr>
                                    <th>AM IN</th>
                                    <th>AM OUT</th>
                                    <th>PM IN</th>
                                    <th>PM OUT</th>
                                    <th>OT IN</th>
                                    <th>OT OUT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${dtrRows || '<tr><td colspan="9">No attendance records for this month</td></tr>'}
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="7">TOTAL HOURS</td>
                                    <td>${totalHours.toFixed(2)}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div class="signature-section">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Signature over Printed Name</div>
                                <div style="margin-top: 5px;">${studentInfo.full_name || 'Student Name'}</div>
                                <div style="font-size: 8px; margin-top: 2px;">Student</div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">Signature over Printed Name</div>
                                <div style="margin-top: 5px;">${studentInfo.company_head || 'Supervisor Name'}</div>
                                <div style="font-size: 8px; margin-top: 2px;">Company Supervisor</div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `;

            return html;
        }

        document.getElementById('printMonthlyBtn')?.addEventListener('click', () => {
            const month = parseInt(document.getElementById('printMonth')?.value || '1', 10);
            const year = parseInt(document.getElementById('printYear')?.value || String(new Date().getFullYear()), 10);

            const win = window.open('', '_blank');
            if (!win) return;
            win.document.open();
            win.document.write(buildPrintHtml(year, month));
            win.document.close();
            win.focus();
            win.print();
        });
    </script>
</body>

</html>