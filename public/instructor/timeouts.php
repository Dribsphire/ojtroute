<?php
session_start();
require_once __DIR__ . '/../../app/middleware/requireInstructor.php';
require_once __DIR__ . '/../../app/services/StudentService.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    $action = $input['action'] ?? '';
    $recordId = $input['record_id'] ?? 0;
    $feedback = $input['feedback'] ?? '';

    $studentService = new \App\Services\StudentService();

    if ($action === 'approve') {
        $result = $studentService->approveTimeoutRequest($recordId, $feedback);
        echo json_encode($result);
        exit;
    } elseif ($action === 'reject') {
        $result = $studentService->rejectTimeoutRequest($recordId, $feedback);
        echo json_encode($result);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Fetch real timeout records from database
$timeout_records = [];
try {
    $config = require __DIR__ . '/../../config/database.php';
    $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['dbname'], $config['charset']);
    $db = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // Get instructor ID
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id FROM instructors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
    $instructor_id = $instructor ? $instructor['id'] : 0;

    $stmt = $db->prepare("
        SELECT 
            ar.id,
            ar.attendance_date,
            ar.block_type,
            ar.time_in,
            ar.forgot_timeout_reason as reason,
            ar.forgot_timeout_file as letter_file,
            ar.request_status as status,
            ar.instructor_response as feedback,
            u.full_name as student_name,
            u.school_id,
            s.section_name
        FROM attendance_records ar
        JOIN students st ON ar.student_id = st.id
        JOIN users u ON st.user_id = u.id
        JOIN sections s ON u.section_id = s.id
        WHERE s.instructor_id = :instructor_id
        AND (
            (ar.time_out IS NULL AND ar.attendance_date < CURDATE())
            OR ar.request_status IS NOT NULL
            OR ar.missing_timeout_flagged_at IS NOT NULL
        )
        ORDER BY 
            CASE ar.request_status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rejected' THEN 3
                ELSE 4
            END,
            ar.attendance_date DESC
    ");
    $stmt->execute(['instructor_id' => $instructor_id]);
    $timeout_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Instructor timeouts fetch error: ' . $e->getMessage());
}

require_once 'instructor_nav.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeout Exceptions - OJTRoute System</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <style>
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 0 0 1rem 0;
            flex-wrap: wrap;
        }

        .page-title {
            color: var(--accent-clr);
            margin: 0;
        }

        .table-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 0.75rem 0;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            min-width: 240px;
        }

        .search-box input {
            width: 100%;
            padding: 0.6rem 0.8rem;
            border-radius: .5em;
            border: 1px solid var(--line-clr);
            background: var(--hover-clr);
            color: var(--text-clr);
            outline: none;
        }

        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .table-container {
            background: transparent;
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            overflow: hidden;
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
            vertical-align: top;
        }

        th {
            background-color: var(--base-clr);
            color: var(--accent-clr);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: var(--hover-clr);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-pending {
            background-color: rgba(255, 165, 0, 0.2);
            color: orange;
        }

        .status-approved {
            background-color: rgba(26, 210, 28, 0.2);
            color: var(--accent-clr);
        }

        .status-rejected {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            border-radius: .5em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .icon-btn:hover {
            border-color: var(--accent-clr);
            color: var(--accent-clr);
            background: var(--hover-clr);
        }

        .icon-btn i {
            font-size: 0.9rem;
        }

        .feedback {
            max-width: 320px;
            white-space: normal;
            color: rgba(255, 255, 255, 0.85);
        }

        .doc-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-clr);
            text-decoration: none;
        }

        .doc-link:hover {
            color: var(--accent-clr);
        }

        .doc-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .doc-modal.open {
            display: flex;
        }

        .doc-modal-content {
            width: min(1000px, 95vw);
            height: min(85vh, 820px);
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .doc-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--line-clr);
            background: var(--base-clr);
        }

        .doc-modal-title {
            font-weight: 700;
            color: var(--text-clr);
            font-size: 1rem;
        }

        .doc-modal-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.55rem 0.9rem;
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

        .doc-modal-body {
            flex: 1;
            background: var(--hover-clr);
        }

        .doc-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: white;
        }
    </style>
</head>

<body>
    <main>
        <div class="page-header">
            <h2 class="page-title">Forgot to Timeout</h2>
        </div>

        <div class="table-tools">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input id="timeoutSearch" type="text" placeholder="Search student, block type, status...">
            </div>
        </div>

        <div class="table-container">
            <div class="responsive-table">
                <table id="timeoutsTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Date</th>
                            <th>Block Type</th>
                            <th>Time In</th>
                            <th>Reason</th>
                            <th>Letter Document</th>
                            <th>Status</th>
                            <th>Instructor Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timeout_records)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.6);">
                                    No timeout requests found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($timeout_records as $r): ?>
                                <tr data-id="<?php echo $r['id']; ?>"
                                    data-letter="<?php echo htmlspecialchars($r['letter_file'] ?? ''); ?>"
                                    data-time-in="<?php echo $r['time_in']; ?>" data-block="<?php echo $r['block_type']; ?>">
                                    <td>
                                        <div style="font-weight: 700;">
                                            <?php echo htmlspecialchars($r['student_name']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.7);">
                                            <?php echo htmlspecialchars($r['school_id']); ?> •
                                            <?php echo htmlspecialchars($r['section_name'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($r['attendance_date'])); ?></td>
                                    <td><?php echo ucfirst($r['block_type']); ?></td>
                                    <td><?php echo date('g:i A', strtotime($r['time_in'])); ?></td>
                                    <td style="max-width: 250px;">
                                        <?php echo $r['reason'] ? htmlspecialchars($r['reason']) : '<span style="opacity:0.65;">No reason provided</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['letter_file']): ?>
                                            <a class="doc-link" href="<?php echo htmlspecialchars($r['letter_file']); ?>"
                                                target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-file-pdf"></i>
                                                View Letter
                                            </a>
                                        <?php else: ?>
                                            <span style="opacity:0.65;">No document</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status']): ?>
                                            <span class="status-badge status-<?php echo $r['status']; ?>" data-status>
                                                <?php echo $r['status']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="opacity:0.65;">Not submitted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="feedback" data-feedback>
                                        <?php echo $r['feedback'] ? htmlspecialchars($r['feedback']) : '<span style="opacity:0.65;">No feedback</span>'; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <?php if ($r['letter_file']): ?>
                                                <button class="icon-btn" type="button" data-action="view" title="View Letter"
                                                    aria-label="View Letter">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <button class="icon-btn" type="button" data-action="approve" title="Approve"
                                                    aria-label="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="icon-btn" type="button" data-action="reject" title="Reject"
                                                    aria-label="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
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

    <div id="letterModal" class="doc-modal" aria-hidden="true">
        <div class="doc-modal-content" role="dialog" aria-modal="true" aria-labelledby="letterModalTitle">
            <div class="doc-modal-header">
                <div id="letterModalTitle" class="doc-modal-title">Letter Preview</div>
                <div class="doc-modal-actions">
                    <a id="letterDownloadBtn" class="btn primary" href="#" download>
                        <i class="fas fa-download"></i>
                        Download
                    </a>
                    <button id="letterCloseBtn" class="icon-btn" type="button" title="Close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="doc-modal-body">
                <iframe id="letterFrame" class="doc-frame" src="" title="Letter Preview"></iframe>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const search = document.getElementById('timeoutSearch');
            const table = document.getElementById('timeoutsTable');
            if (!search || !table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            const modal = document.getElementById('letterModal');
            const frame = document.getElementById('letterFrame');
            const closeBtn = document.getElementById('letterCloseBtn');
            const downloadBtn = document.getElementById('letterDownloadBtn');

            function openModal(fileUrl, title) {
                if (!modal || !frame || !downloadBtn) return;
                frame.src = fileUrl;
                downloadBtn.href = fileUrl;
                const titleEl = document.getElementById('letterModalTitle');
                if (titleEl && title) titleEl.textContent = title;
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                if (!modal || !frame) return;
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                frame.src = '';
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeModal();
            });

            function applySearch() {
                const q = (search.value || '').trim().toLowerCase();
                rows.forEach(row => {
                    const txt = row.textContent.toLowerCase();
                    row.style.display = (!q || txt.includes(q)) ? '' : 'none';
                });
            }

            search.addEventListener('input', applySearch);

            function setStatus(row, status, feedbackText) {
                const badge = row.querySelector('[data-status]');
                const feedbackEl = row.querySelector('[data-feedback]');
                if (!badge || !feedbackEl) return;

                badge.classList.remove('status-pending', 'status-approved', 'status-reject');
                badge.classList.add('status-' + status);
                badge.textContent = status;

                if (typeof feedbackText === 'string') {
                    feedbackEl.innerHTML = feedbackText.trim() !== ''
                        ? feedbackText
                        : '<span style="opacity:0.65;">No feedback</span>';
                }
            }

            async function updateRequest(action, recordId, feedback, row) {
                try {
                    const res = await fetch('timeouts.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: action,
                            record_id: recordId,
                            feedback: feedback
                        })
                    });

                    const data = await res.json();

                    if (data.success) {
                        alert(data.message);
                        setStatus(row, action === 'approve' ? 'approved' : 'rejected', feedback);
                        // Disable buttons
                        const buttons = row.querySelectorAll('.icon-btn');
                        buttons.forEach(btn => {
                            if (btn.getAttribute('data-action') === 'approve' || btn.getAttribute('data-action') === 'reject') {
                                btn.remove();
                            }
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (err) {
                    console.error(err);
                    alert('An error occurred. Please try again.');
                }
            }

            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const row = btn.closest('tr');
                if (!row) return;

                const action = btn.getAttribute('data-action');
                if (action === 'view') {
                    const fileUrl = row.getAttribute('data-letter') || '';
                    if (!fileUrl) return; // Should not happen if button exists
                    const studentName = row.children && row.children[0] ? row.children[0].textContent.trim() : 'Letter Preview';
                    openModal(fileUrl, studentName);
                    return;
                }

                const recordId = row.getAttribute('data-id');

                if (action === 'approve') {
                    // Calculate hours based on block end time
                    const timeIn = row.getAttribute('data-time-in');
                    const blockType = row.getAttribute('data-block');

                    if (!timeIn || !blockType) {
                        alert('Missing time information');
                        return;
                    }

                    // Block end times
                    const blockEndTimes = {
                        'morning': '12:00:00',
                        'afternoon': '18:00:00',
                        'overtime': '22:00:00'
                    };

                    const endTime = blockEndTimes[blockType];
                    if (!endTime) {
                        alert('Invalid block type');
                        return;
                    }

                    // Calculate hours
                    const timeInDate = new Date(timeIn);
                    const timeOutDate = new Date(timeIn.split(' ')[0] + ' ' + endTime);
                    const hoursDiff = (timeOutDate - timeInDate) / (1000 * 60 * 60);
                    const hoursFormatted = hoursDiff.toFixed(2);

                    // Show confirmation with hours
                    const studentName = row.querySelector('td:first-child div:first-child').textContent.trim();
                    const confirmMsg = `Approve timeout request for ${studentName}?\n\nThis will add ${hoursFormatted} hours to their total OJT hours.\n\nTime In: ${new Date(timeIn).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}\nAssumed Time Out: ${new Date(timeOutDate).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })} (${blockType} block end)\nHours: ${hoursFormatted} hrs`;

                    if (!confirm(confirmMsg)) return;

                    const fb = prompt('Instructor feedback (optional):', `Approved. ${hoursFormatted} hours added based on ${blockType} block end time.`);
                    if (fb === null) return;

                    updateRequest('approve', recordId, fb, row);
                    return;
                }

                if (action === 'reject') {
                    const fb = prompt('Reason for rejection:', 'Rejected.');
                    if (fb === null) return;
                    updateRequest('reject', recordId, fb, row);
                }
            });
        })();
    </script>
</body>

</html>