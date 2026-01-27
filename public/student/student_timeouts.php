<?php
session_start();
require_once __DIR__ . '/../../app/services/StudentService.php';

$studentService = new \App\Services\StudentService();
$userId = $_SESSION['user_id'] ?? 0;
$dbId = $studentService->getStudentDbId($userId);

// Handle POST Request for submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $recordId = $_POST['record_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $file = $_FILES['letter'] ?? null;

    $result = $studentService->submitTimeoutRequest($dbId, $recordId, $reason, $file);
    echo json_encode($result);
    exit;
}

// Fetch Real Data
$timeout_data = $studentService->getMissingTimeouts($dbId);

require_once 'student_nav.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missing Timeouts - OJTRoute System</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .timeouts-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .timeouts-card {
            background: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            padding: 1.5rem;
        }

        .header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .header-row h1 {
            margin: 0;
            font-size: 1.35rem;
        }

        .header-row p {
            margin: .45rem 0 0;
            color: var(--secondary-text-clr);
        }

        .primary-btn {
            background: var(--accent-clr);
            color: #0b0d12;
            border: 1px solid transparent;
            padding: 0.75rem 1rem;
            border-radius: .75em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-weight: 700;
            transition: transform 150ms ease, filter 150ms ease;
            white-space: nowrap;
        }

        .primary-btn:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .primary-btn:active {
            transform: translateY(0);
        }

        .primary-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .table-wrap {
            margin-top: 1.25rem;
            overflow: auto;
            border-radius: 0.9em;
            border: 1px solid var(--line-clr);
            background: rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            min-width: 980px;
        }

        th,
        td {
            padding: 0.95rem;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        th {
            background: rgba(0, 0, 0, 0.25);
            color: var(--secondary-text-clr);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: .04em;
        }

        td {
            color: var(--text-clr);
            vertical-align: top;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-pending {
            background: #ffd70033;
            color: #ffd700;
            border: 1px solid #ffd70066;
        }

        .status-approved {
            background: #32cd3233;
            color: #32cd32;
            border: 1px solid #32cd3266;
        }

        .status-rejected {
            background: #ff333333;
            color: #ff3333;
            border: 1px solid #ff333366;
        }

        .file-link {
            color: #4da6ff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .icon-btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--secondary-text-clr);
            padding: 0.45rem 0.7rem;
            border-radius: .75em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 150ms ease, transform 150ms ease;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-1px);
        }

        .icon-btn:active {
            transform: translateY(0);
        }

        .icon-btn.view {
            border-color: #4da6ff66;
            color: #4da6ff;
        }

        .icon-btn.edit {
            border-color: #ff8c0066;
            color: #ff8c00;
        }

        .icon-btn.attach {
            border-color: rgba(26, 210, 28, 0.55);
            color: var(--accent-clr);
        }

        .muted {
            color: var(--secondary-text-clr);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 1.5rem;
            overflow: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            width: min(980px, 96vw);
            background: var(--base-clr);
            color: var(--text-clr);
            border-radius: 1em;
            padding: 1.25rem;
            position: relative;
            border: 1px solid var(--line-clr);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.05rem;
        }

        .modal-close {
            background: none;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.35rem 0.65rem;
            border-radius: .75em;
            cursor: pointer;
        }

        .modal-body {
            padding-top: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            table {
                min-width: 860px;
            }
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .field label {
            color: var(--secondary-text-clr);
            font-size: 0.9rem;
        }

        .field input,
        .field select,
        .field textarea {
            padding: 0.75rem;
            border-radius: .75em;
            border: 1px solid var(--line-clr);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-clr);
        }

        .field input[readonly] {
            background: rgba(255, 255, 255, 0.02);
            cursor: not-allowed;
        }

        .field textarea {
            min-height: 120px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .secondary-btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.75rem 1rem;
            border-radius: .75em;
            cursor: pointer;
            transition: background 150ms ease, transform 150ms ease;
        }

        .secondary-btn:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-1px);
        }

        .secondary-btn:active {
            transform: translateY(0);
        }

        .preview-frame {
            width: 100%;
            height: 70vh;
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            background: #0f111a;
        }

        .preview-image {
            width: 100%;
            height: 70vh;
            object-fit: contain;
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            background: #0f111a;
        }

        .download-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.75rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .timeouts-container {
                max-width: 100%;
                padding: 0 1rem;
            }
        }

        @media (max-width: 768px) {
            .timeouts-container {
                padding: 0 0.75rem;
            }

            .timeouts-card {
                padding: 1rem;
                border-radius: 0.75em;
            }

            .header-row {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .header-row h1 {
                font-size: 1.5rem;
                text-align: center;
            }

            .primary-btn {
                width: 100%;
                justify-content: center;
            }

            /* Make table scrollable horizontally */
            .table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            table {
                min-width: 800px;
            }

            th,
            td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }

            .status {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }

            .icon-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.85rem;
            }

            .modal-content {
                width: 95%;
                max-width: none;
                margin: 1rem;
            }

            .modal-header {
                padding: 0.75rem 1rem;
            }

            .modal-header h3 {
                font-size: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field label {
                font-size: 0.85rem;
            }

            .field input,
            .field textarea,
            .field select {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .modal-actions {
                flex-direction: column-reverse;
            }

            .modal-actions button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .timeouts-container {
                padding: 0 0.5rem;
            }

            .timeouts-card {
                padding: 0.75rem;
                border-radius: 0.5em;
                width: 18rem;
                height: 38rem;
            }

            .header-row h1 {
                font-size: 1.3rem;
            }

            .primary-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .primary-btn i {
                font-size: 0.9rem;
            }

            table {
                min-width: 700px;
            }

            th,
            td {
                padding: 0.6rem 0.4rem;
                font-size: 0.8rem;
            }

            th {
                font-size: 0.7rem;
            }

            .status {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
                min-width: 70px;
            }

            .icon-btn {
                padding: 0.35rem 0.5rem;
                font-size: 0.8rem;
                margin-right: 0.25rem;
            }

            .icon-btn i {
                font-size: 0.85rem;
            }

            .modal-content {
                width: 100%;
                max-height: 95vh;
                margin: 0.5rem;
            }

            .modal-header {
                padding: 0.6rem 0.75rem;
            }

            .modal-header h3 {
                font-size: 0.95rem;
            }

            .modal-close {
                padding: 0.3rem 0.5rem;
                font-size: 0.9rem;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .field label {
                font-size: 0.8rem;
            }

            .field input,
            .field textarea,
            .field select {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .field textarea {
                min-height: 100px;
            }

            .secondary-btn,
            .primary-btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }

            .file-preview {
                padding: 0.6rem;
            }

            .download-row {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="timeouts-container">
            <div class="timeouts-card">
                <div class="header-row">
                    <div>
                        <h1>Missing Timeouts</h1>
                        <p>These are attendance records where you timed in but forgot to time out. Attach a supporting
                            document and optional reason for each record.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Block Type</th>
                                <th>Time In</th>
                                <th>Document</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Instructor Response</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="timeoutsTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attach Document Modal -->
        <div id="attachModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="attachModalTitle">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="attachModalTitle">Attach Document</h3>
                    <button class="modal-close" type="button" data-close-modal="attachModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="attachForm">
                        <input type="hidden" id="recordId" name="record_id">
                        <div class="form-grid">
                            <div class="field">
                                <label>Date</label>
                                <input id="recordDate" type="text" readonly>
                            </div>
                            <div class="field">
                                <label>Block Type</label>
                                <input id="recordBlockType" type="text" readonly>
                            </div>
                            <div class="field">
                                <label>Time In</label>
                                <input id="recordTimeIn" type="text" readonly>
                            </div>

                            <div class="field" style="grid-column: 1 / -1;">
                                <label for="reason">Reason (Optional)</label>
                                <textarea id="reason" name="reason"
                                    placeholder="Write your reason for missing timeout..."></textarea>
                            </div>
                            <div class="field" style="grid-column: 1 / -1;">
                                <label for="letterFile">Upload Letter / Supporting Document</label>
                                <input id="letterFile" name="letter" type="file" accept=".pdf,image/*">
                                <span class="muted" style="font-size: .85rem;">Accepted: PDF or Image</span>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button class="secondary-btn" type="button" data-close-modal="attachModal">Cancel</button>
                            <button id="attachBtn" class="primary-btn" type="submit">
                                <i class="fas fa-paperclip"></i>
                                Attach Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Letter Preview Modal -->
        <div id="previewModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="previewModalTitle">Letter Preview</h3>
                    <button class="modal-close" type="button" data-close-modal="previewModal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <iframe id="letterPreviewFrame" class="preview-frame" style="display:none;"></iframe>
                    <img id="letterPreviewImage" class="preview-image" style="display:none;" alt="Letter Preview">
                    <div class="download-row">
                        <a id="downloadLetter" class="primary-btn" href="#" download>
                            <i class="fas fa-download"></i>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        const initialTimeouts = <?php echo json_encode($timeout_data, JSON_UNESCAPED_SLASHES); ?>;
        let timeouts = initialTimeouts.map((r) => ({
            ...r,
            reason: r.reason || '',
            letter_file_name: r.letter_file_name || (r.letter_file_path ? r.letter_file_path.split('/').pop() : ''),
        }));

        const tbody = document.getElementById('timeoutsTbody');
        const attachModal = document.getElementById('attachModal');
        const previewModal = document.getElementById('previewModal');
        let currentRecordId = null;

        function openModal(modal) {
            if (!modal) return;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(modal) {
            if (!modal) return;
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function getStatusBadge(status) {
            const s = (status || '').toLowerCase();
            if (s === 'approved') return '<span class="status-badge status-approved">approved</span>';
            if (s === 'rejected') return '<span class="status-badge status-rejected">rejected</span>';
            return '<span class="status-badge status-pending">pending</span>';
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function formatTime(timeStr) {
            if (!timeStr) return '-';

            // Handle both "HH:MM:SS" and "YYYY-MM-DD HH:MM:SS" formats
            let timePart = timeStr;

            // If it contains a space, it's a DATETIME format, extract the time part
            if (timeStr.includes(' ')) {
                timePart = timeStr.split(' ')[1];
            }

            // Now split the time part (HH:MM:SS)
            const [hours, minutes] = timePart.split(':');
            const hour = parseInt(hours, 10);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        function renderTable() {
            if (!tbody) return;

            if (!timeouts.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="muted" style="padding: 1.25rem; text-align: center;">No missing timeout records found.</td></tr>';
                return;
            }

            tbody.innerHTML = timeouts.map((r) => {
                const fileLabel = r.letter_file_name || 'View file';
                const fileHref = r.letter_file_path || '#';
                const hasDocument = !!r.letter_file_path;

                return `
                    <tr data-id="${r.id}">
                        <td>${formatDate(r.attendance_date)}</td>
                        <td>${r.block_type || '-'}</td>
                        <td>${formatTime(r.time_in)}</td>
                        <td>
                            ${hasDocument ? `
                                <a href="#" class="file-link" data-action="view" data-file="${fileHref}" data-filename="${fileLabel}">
                                    <i class="fas fa-file"></i>
                                    <span>${fileLabel}</span>
                                </a>
                            ` : '<span class="muted">No document attached</span>'}
                        </td>
                        <td>${r.reason ? `<span title="${r.reason}">${r.reason.length > 30 ? r.reason.substring(0, 30) + '...' : r.reason}</span>` : '<span class="muted">-</span>'}</td>
                        <td>${r.status ? getStatusBadge(r.status) : '<span class="muted">Not submitted</span>'}</td>
                        <td>${r.instructor_response ? r.instructor_response : '<span class="muted">-</span>'}</td>
                        <td>
                            <div class="action-buttons">
                                ${hasDocument ? `
                                    <button class="icon-btn view" type="button" title="View Document" aria-label="View Document" data-action="view" data-file="${fileHref}" data-filename="${fileLabel}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                ` : ''}
                                <button class="icon-btn attach" type="button" title="${hasDocument ? 'Update Document' : 'Attach Document'}" aria-label="${hasDocument ? 'Update Document' : 'Attach Document'}" data-action="attach" data-id="${r.id}">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function setPreview(fileUrl, fileName) {
            const frame = document.getElementById('letterPreviewFrame');
            const img = document.getElementById('letterPreviewImage');
            const dl = document.getElementById('downloadLetter');
            const title = document.getElementById('previewModalTitle');

            if (title) title.textContent = `Letter Preview - ${fileName || ''}`.trim();
            if (dl) {
                dl.href = fileUrl;
                dl.setAttribute('download', fileName || 'letter');
            }

            const lower = (fileName || fileUrl || '').toLowerCase();
            const isPdf = lower.endsWith('.pdf');
            const isImage = lower.endsWith('.png') || lower.endsWith('.jpg') || lower.endsWith('.jpeg') || lower.endsWith('.gif') || lower.endsWith('.webp');

            if (frame) frame.style.display = 'none';
            if (img) img.style.display = 'none';

            if (isPdf && frame) {
                frame.src = fileUrl;
                frame.style.display = 'block';
            } else if (isImage && img) {
                img.src = fileUrl;
                img.style.display = 'block';
            } else if (frame) {
                frame.src = fileUrl;
                frame.style.display = 'block';
            }
        }

        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                const id = closeBtn.getAttribute('data-close-modal');
                closeModal(document.getElementById(id));
                return;
            }

            if (e.target === attachModal) closeModal(attachModal);
            if (e.target === previewModal) closeModal(previewModal);

            const actionEl = e.target.closest('[data-action]');
            if (!actionEl) return;

            const action = actionEl.getAttribute('data-action');
            if (action === 'view') {
                e.preventDefault();
                const file = actionEl.getAttribute('data-file');
                const name = actionEl.getAttribute('data-filename') || 'letter';
                if (!file || file === '#') return;
                setPreview(file, name);
                openModal(previewModal);
                return;
            }

            if (action === 'attach') {
                const id = actionEl.getAttribute('data-id');
                const item = timeouts.find(t => String(t.id) === String(id));
                if (!item) return;

                currentRecordId = item.id;
                const title = document.getElementById('attachModalTitle');
                if (title) title.textContent = item.letter_file_path ? 'Update Document' : 'Attach Document';

                const form = document.getElementById('attachForm');
                form?.reset();

                document.getElementById('recordId').value = item.id;
                document.getElementById('recordDate').value = formatDate(item.attendance_date);
                document.getElementById('recordBlockType').value = item.block_type || '';
                document.getElementById('recordTimeIn').value = formatTime(item.time_in);
                document.getElementById('reason').value = item.reason || '';

                openModal(attachModal);
                return;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal(attachModal);
                closeModal(previewModal);
            }
        });

        document.getElementById('attachForm')?.addEventListener('submit', (e) => {
            e.preventDefault();

            const recordId = document.getElementById('recordId')?.value;
            const reason = document.getElementById('reason')?.value;
            const fileInput = document.getElementById('letterFile');
            const file = fileInput?.files?.[0];

            const formData = new FormData();
            formData.append('record_id', recordId);
            formData.append('reason', reason);
            if (file) formData.append('letter', file);

            const btn = document.getElementById('attachBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            btn.disabled = true;

            fetch('student_timeouts.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;

                    if (data.success) {
                        alert(data.message);
                        closeModal(attachModal);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('An error occurred. Please try again.');
                });
        });

        renderTable();
    </script>
</body>

</html>