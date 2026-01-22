<?php
session_start();
require_once '../../app/services/StudentService.php';

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$studentService = new \App\Services\StudentService();
$userId = $_SESSION['user_id'];

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    $docTypeId = $_POST['document_type_id'] ?? null;
    $file = $_FILES['document_file'] ?? null;

    if ($docTypeId && $file) {
        $result = $studentService->uploadDocument($userId, $docTypeId, $file);
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    }
    header('Location: student_documents.php');
    exit;
}

// Fetch Documents
$documents = $studentService->getStudentDocuments($userId);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Submission - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .document-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .document-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .nav-pills {
            display: flex;
            gap: 1rem;
            padding: 0;
            margin: 1.5rem 0;
            list-style: none;
            border-bottom: 1px solid #3e3f4d;
        }

        .nav-link {
            padding: 0.75rem 1.25rem;
            color: #b8b8b8;
            text-decoration: none;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
        }

        .nav-link.active {
            color: var(--accent-clr);
            border-bottom: 2px solid var(--accent-clr);
        }

        .search-container {
            margin: 1.5rem 0;
            max-width: 400px;
        }

        .search-container input {
            width: 100%;
            padding: 0.75rem;
            border-radius: 5px;
            border: 1px solid #3e3f4d;
            background: #2a2b3a;
            color: #fff;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #2a2b3a;
            border-radius: 8px;
            overflow: hidden;
        }

        .documents-table th,
        .documents-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #3e3f4d;
        }

        .documents-table th {
            background: #37394a;
            color: #b8b8b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }

        .status-pending {
            background-color: rgba(255, 165, 0, 0.15);
            color: orange;
            border: 1px solid rgba(255, 165, 0, 0.3);
        }

        .status-approved {
            background-color: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .status-revise {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }

        .status-rejected {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .status-missing {
            background-color: rgba(149, 165, 166, 0.15);
            color: #95a5a6;
            border: 1px dashed rgba(149, 165, 166, 0.3);
        }

        .action-btn {
            background: none;
            border: 1px solid #3e3f4d;
            color: #b8b8b8;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 0.5rem;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .action-btn.view {
            color: var(--accent-clr);
            border-color: var(--accent-clr);
        }

        .action-btn.download {
            color: #32cd32;
            border-color: #32cd32;
        }

        .action-btn.upload {
            color: orange;
            border-color: orange;
        }

        .document-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .pre-req-badge {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-left: 8px;
            border: 1px solid rgba(231, 76, 60, 0.4);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background: #2a2b3a;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #3e3f4d;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #3e3f4d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #222330;
        }

        .modal-header h3 {
            margin: 0;
            color: #fff;
            font-size: 1.1rem;
        }

        .close-modal {
            cursor: pointer;
            color: #888;
            font-size: 1.5rem;
        }

        .close-modal:hover {
            color: #fff;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .upload-area {
            border: 2px dashed #3e3f4d;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }

        .upload-area:hover {
            border-color: var(--accent-clr);
            background: rgba(74, 144, 226, 0.05);
        }

        .upload-area i {
            font-size: 2.5rem;
            color: var(--accent-clr);
            margin-bottom: 1rem;
        }

        .btn-submit {
            background: var(--accent-clr);
            color: #fff;
            border: none;
            padding: 0.8rem;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-submit:hover {
            opacity: 0.9;
        }

        /* Viewer Modal */
        .viewer-modal-content {
            width: 95%;
            height: 90vh;
            max-width: 1000px;
            display: flex;
            flex-direction: column;
        }

        .viewer-body {
            flex: 1;
            background: #fff;
            overflow: hidden;
        }

        .viewer-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .document-container {
                padding: 1rem;
            }

            .nav-pills {
                gap: 0.5rem;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 0.5rem;
            }

            .nav-link {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
                white-space: nowrap;
            }

            .search-container {
                max-width: 90%;
            }

            /* Make table scrollable horizontally */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 19rem;
            }

            .documents-table {
                min-width: 18rem;
            }

            .documents-table th,
            .documents-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }

            .status {
                min-width: 80px;
                font-size: 0.75rem;
                padding: 0.2rem 0.6rem;
            }

            .action-btn {
                padding: 0.35rem 0.65rem;
                font-size: 0.85rem;
            }

            .modal-content {
                width: 95%;
                max-width: none;
            }

            .modal-header {
                padding: 0.75rem 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .upload-area {
                padding: 1.5rem 1rem;
            }

            .upload-area i {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .document-container {
                padding: 0.75rem;
                width: 19rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .nav-pills {
                gap: 0.35rem;
            }

            .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .search-container input {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .documents-table {
                min-width: 700px;
            }

            .documents-table th,
            .documents-table td {
                padding: 0.6rem 0.4rem;
                font-size: 0.8rem;
            }

            .document-name {
                gap: 5px;
                font-size: 0.85rem;
            }

            .pre-req-badge {
                font-size: 0.65rem;
                padding: 1px 4px;
            }

            .status {
                min-width: 70px;
                font-size: 0.7rem;
                padding: 0.15rem 0.5rem;
            }

            .action-btn {
                padding: 0.3rem 0.5rem;
                font-size: 0.8rem;
                margin-right: 0.25rem;
            }

            .action-btn i {
                font-size: 0.85rem;
            }

            .modal-header h3 {
                font-size: 1rem;
            }

            .upload-area {
                padding: 1rem 0.75rem;
            }

            .upload-area i {
                font-size: 1.75rem;
                margin-bottom: 0.75rem;
            }

            .upload-area p {
                font-size: 0.9rem;
            }

            .btn-submit {
                padding: 0.7rem;
                font-size: 0.9rem;
            }

            .viewer-modal-content {
                width: 100%;
                height: 95vh;
            }
        }
    </style>
</head>

<body>
    <?php require_once 'student_nav.php'; ?>
    <main>
        <div class="document-container">
            <h1>Document Submission</h1>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div
                    style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid rgba(46, 204, 113, 0.3);">
                    <?php echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div
                    style="background: rgba(231, 76, 60, 0.2); color: #e74c3c; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid rgba(231, 76, 60, 0.3);">
                    <?php echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <ul class="nav-pills">
                <li><a class="nav-link active" data-filter="all">All Documents</a></li>
                <li><a class="nav-link" data-filter="pre_required">Pre-Required</a></li>
                <li><a class="nav-link" data-filter="pending">For Approval</a></li>
                <li><a class="nav-link" data-filter="approved">Approved</a></li>
                <li><a class="nav-link" data-filter="revise">Revise</a></li>
            </ul>

            <!-- Search -->
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search documents...">
            </div>

            <div class="table-responsive">
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Document Requirements</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Deadline</th>
                            <th>Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documentsTableBody">
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#888;">No document requirements found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($documents as $doc):
                            $status = $doc['status'] ?: 'Missing';
                            $statusClass = 'status-' . strtolower($status);
                            $isPreReq = $doc['is_pre_required'];
                            ?>
                            <tr data-status="<?php echo strtolower($status); ?>"
                                data-prereq="<?php echo $isPreReq ? 'true' : 'false'; ?>">
                                <td>
                                    <div class="document-name">
                                        <i class="fas fa-file-alt"></i>
                                        <?php echo htmlspecialchars($doc['name']); ?>
                                        <?php if ($isPreReq): ?>
                                            <span class="pre-req-badge"
                                                title="You must approve this before starting OJT">Required</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:#666; margin-left:26px; margin-top:4px;">
                                        <?php echo htmlspecialchars($doc['code']); ?>
                                    </div>
                                </td>
                                <td style="text-transform: capitalize;">
                                    <?php echo str_replace('_', ' ', $doc['category']); ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Check if deadline exists and is valid
                                    if ($doc['deadline'] && $doc['deadline'] != '0000-00-00' && $doc['deadline'] != '0000-00-00 00:00:00') {
                                        try {
                                            $deadline = new DateTime($doc['deadline']);
                                            $today = new DateTime();
                                            $daysLeft = $today->diff($deadline)->days;
                                            $isPast = $today > $deadline;

                                            echo '<span style="';
                                            if ($isPast && $status === 'Missing') {
                                                echo 'color: #e74c3c; font-weight: bold;';
                                            } elseif ($daysLeft <= 3 && !$isPast && $status === 'Missing') {
                                                echo 'color: #f39c12; font-weight: bold;';
                                            }
                                            echo '">';
                                            echo date('M d, Y', strtotime($doc['deadline']));
                                            if ($isPast && $status === 'Missing') {
                                                echo ' <i class="fas fa-exclamation-triangle" title="Overdue"></i>';
                                            } elseif ($daysLeft <= 3 && !$isPast && $status === 'Missing') {
                                                echo ' (' . $daysLeft . ' days left)';
                                            }
                                            echo '</span>';
                                        } catch (Exception $e) {
                                            echo '<span style="color: #888;">No deadline</span>';
                                        }
                                    } else {
                                        echo '<span style="color: #888;">No deadline</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($doc['feedback']): ?>
                                        <span style="color: #e74c3c; font-size: 0.9rem;"><i class="fas fa-comment-alt"></i>
                                            <?php echo htmlspecialchars($doc['feedback']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #666;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Actions -->
                                    <div style="display:flex;">

                                        <!-- Download Template if available -->
                                        <?php if ($doc['template_path']): ?>
                                            <a href="<?php echo htmlspecialchars($doc['template_path']); ?>"
                                                class="action-btn download" title="Download Template" download target="_blank">
                                                <i class="fas fa-file-download"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- View Submission if exists -->
                                        <?php if ($doc['file_path']): ?>
                                            <button class="action-btn view"
                                                onclick="viewDocument('<?php echo htmlspecialchars($doc['file_path']); ?>', '<?php echo htmlspecialchars($doc['name']); ?>')"
                                                title="View Submission">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Upload button (if missing, revised, or rejected) -->
                                        <?php if (!$doc['status'] || in_array($doc['status'], ['revise', 'rejected', 'missing'])): ?>
                                            <button class="action-btn upload"
                                                onclick="openUploadModal(<?php echo $doc['document_type_id']; ?>, '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?>')"
                                                title="Upload Document">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Document</h3>
                <span class="close-modal" onclick="closeModal('uploadModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" action="student_documents.php">
                    <input type="hidden" name="action" value="upload_document">
                    <input type="hidden" id="uploadDocTypeId" name="document_type_id" value="">

                    <p style="color:#b8b8b8; margin-bottom:1rem;">Uploading for: <strong id="uploadDocName"
                            style="color:var(--accent-clr);"></strong></p>

                    <div class="upload-area" onclick="document.getElementById('docFile').click()">
                        <div id="fileNameDisplay"
                            style="margin-bottom:0.5rem; color:var(--accent-clr); font-weight:bold;"></div>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to select file</p>
                        <small>PDF, Word, or Image (Max 10MB)</small>
                        <input type="file" id="docFile" name="document_file" style="display:none;" required
                            onchange="displayFileName(this)">
                    </div>

                    <button type="submit" class="btn-submit">Submit Document</button>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content viewer-modal-content">
            <div class="modal-header">
                <h3 id="viewDocTitle">Document Preview</h3>
                <div style="display:flex; gap:10px; align-items:center;">
                    <a id="viewDownloadBtn" href="#" class="action-btn download" download
                        style="text-decoration:none; display:flex; align-items:center; gap:5px;">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <span class="close-modal" onclick="closeModal('viewModal')">&times;</span>
                </div>
            </div>
            <div class="modal-body viewer-body">
                <iframe id="viewFrame" class="viewer-frame" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        const uploadModal = document.getElementById('uploadModal');
        const viewModal = document.getElementById('viewModal');

        function openUploadModal(id, name) {
            document.getElementById('uploadDocTypeId').value = id;
            document.getElementById('uploadDocName').textContent = name;
            document.getElementById('fileNameDisplay').textContent = '';
            document.getElementById('docFile').value = '';
            uploadModal.style.display = 'flex';
        }

        function viewDocument(url, title) {
            document.getElementById('viewDocTitle').textContent = title || 'Document Preview';
            document.getElementById('viewFrame').src = url;
            document.getElementById('viewDownloadBtn').href = url;
            viewModal.style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            if (id === 'viewModal') {
                document.getElementById('viewFrame').src = '';
            }
        }

        function displayFileName(input) {
            const display = document.getElementById('fileNameDisplay');
            if (input.files && input.files[0]) {
                display.textContent = input.files[0].name;
            } else {
                display.textContent = '';
            }
        }

        // Close on click outside
        window.onclick = function (e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                if (e.target.id === 'viewModal') {
                    document.getElementById('viewFrame').src = '';
                }
            }
        };

        // Search & Filter
        const searchInput = document.getElementById('searchInput');
        const navLinks = document.querySelectorAll('.nav-link');
        const rows = document.querySelectorAll('#documentsTableBody tr');

        function filterDocs() {
            const query = searchInput.value.toLowerCase();
            const activeLink = document.querySelector('.nav-link.active');
            const filterType = activeLink.getAttribute('data-filter');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.getAttribute('data-status'); // pending, approved, missing, etc
                const isPreReq = row.getAttribute('data-prereq') === 'true';

                let matchesFilter = false;
                if (filterType === 'all') matchesFilter = true;
                else if (filterType === 'pre_required') matchesFilter = isPreReq;
                else matchesFilter = (status === filterType);

                // Handling 'missing' status if filter is 'pending' ? No, stick to exact match usually. 
                // But for 'pending' maybe we want things that are waiting for approval?
                // Our DB status: 'pending', 'approved', 'revise', 'rejected'. 'missing' is virtual.

                if (matchesFilter && text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterDocs);
        navLinks.forEach(link => {
            link.addEventListener('click', function () {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                filterDocs();
            });
        });

        // Clear notification badge when viewing documents page
        document.addEventListener('DOMContentLoaded', function () {
            const badge = document.getElementById('docNotificationBadge');
            if (badge) {
                badge.style.display = 'none';
            }
        });

    </script>
</body>

</html>