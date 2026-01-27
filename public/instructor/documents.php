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
    // Handle error or redirect
    echo "Instructor profile not found.";
    exit;
}

// Handle Template Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_template') {
    $typeId = $_POST['document_type_id'] ?? null;
    $file = $_FILES['template_file'] ?? null;

    if ($typeId && $file) {
        $result = $instructorService->uploadDocumentTemplate($typeId, $file);
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    }
    header('Location: documents.php');
    exit;
}
// Handle Create Document Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_document') {
    $name = $_POST['name'] ?? '';
    $code = $_POST['code'] ?? '';
    $category = $_POST['category'] ?? 'other';
    $deadline = $_POST['deadline'] ?? null;
    $file = $_FILES['template_file'] ?? null;

    if ($name && $code) {
        $result = $instructorService->createDocumentType($name, $code, $category, $deadline, $instructorId, $file);
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    } else {
        $_SESSION['error_msg'] = 'Name and Code are required.';
    }
    header('Location: documents.php');
    exit;
}

// Handle Delete Document Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_document') {
    $id = $_POST['document_id'] ?? null;

    if ($id) {
        $result = $instructorService->deleteDocumentType($id, $instructorId);
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    } else {
        $_SESSION['error_msg'] = 'Invalid document ID.';
    }
    header('Location: documents.php');
    exit;
}

// Handle Approval/Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $subId = $_POST['submission_id'];
        $status = $_POST['status'];
        $feedback = $_POST['feedback'] ?? '';
        $points = isset($_POST['points']) && $_POST['points'] !== '' ? floatval($_POST['points']) : null;
        $accuracyQualityPoints = isset($_POST['accuracyQualityPoints']) && $_POST['accuracyQualityPoints'] !== '' ? floatval($_POST['accuracyQualityPoints']) : null;
        $professionalPresentationPoints = isset($_POST['professionalPresentationPoints']) && $_POST['professionalPresentationPoints'] !== '' ? floatval($_POST['professionalPresentationPoints']) : null;
        $result = $instructorService->updateSubmissionStatus($subId, $status, $feedback, $points, $accuracyQualityPoints, $professionalPresentationPoints);
    } elseif ($_POST['action'] === 'bulk_approve') {
        $ids = json_decode($_POST['submission_ids'], true);
        if ($ids) {
            $result = $instructorService->bulkUpdateSubmissionStatus($ids, 'approved');
        } else {
            $result = ['success' => false, 'message' => 'No items selected'];
        }
    }

    if (isset($result)) {
        if ($result['success']) {
            $_SESSION['success_msg'] = $result['message'];
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    }
    header('Location: documents.php');
    exit;
}

// Fetch Data
$documentTypes = $instructorService->getAllDocumentTypes($instructorId);
$submissions = $instructorService->getStudentSubmissions($instructorId);

// Group document types
$preRequiredDocs = array_filter($documentTypes, function ($d) {
    return $d['is_pre_required'];
});
$otherDocs = array_filter($documentTypes, function ($d) {
    return !$d['is_pre_required'];
});

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - OJTRoute System</title>
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

        /* Alert Messages */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 0.75em;
            margin-bottom: 1rem;
            border: 1px solid;
            animation: slideDown 0.3s ease-out;
            position: relative;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i:first-child {
            font-size: 1.25rem;
        }

        .alert span {
            flex: 1;
            font-weight: 500;
        }

        .alert-close {
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0.25rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .alert-close:hover {
            opacity: 1;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #ef4444;
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

        .status-approve {
            background-color: rgba(26, 210, 28, 0.2);
            color: var(--accent-clr);
        }

        .status-revise {
            background-color: rgba(77, 166, 255, 0.2);
            color: #4da6ff;
        }

        .status-reject {
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
            max-width: 280px;
            white-space: normal;
            color: rgba(255, 255, 255, 0.85);
        }

        .col-check {
            width: 44px;
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
            height: min(90vh, 800px);
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

        .upload-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .upload-modal.open {
            display: flex;
        }

        .upload-modal-content {
            width: min(600px, 95vw);
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 1em;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .upload-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--line-clr);
            background: var(--base-clr);
        }

        .upload-modal-title {
            font-weight: 700;
            color: var(--text-clr);
            font-size: 1rem;
        }

        .upload-modal-body {
            padding: 1.5rem;
            background: var(--hover-clr);
        }

        .upload-area {
            border: 2px dashed var(--line-clr);
            border-radius: 0.5em;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: var(--accent-clr);
            background: rgba(26, 210, 28, 0.1);
        }

        .upload-area i {
            font-size: 3rem;
            color: var(--accent-clr);
            margin-bottom: 1rem;
        }

        .upload-area p {
            color: var(--text-clr);
            margin: 0.5rem 0;
        }

        .upload-area small {
            color: rgba(255, 255, 255, 0.6);
        }

        .file-input {
            display: none;
        }

        .upload-preview {
            margin-top: 1.5rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            margin-bottom: 0.5rem;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-clr);
            border-radius: 0.5em;
            color: white;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: var(--text-clr);
            font-size: 0.8rem;
        }

        .file-size {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .remove-file {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.4rem 0.6rem;
            border-radius: 0.3em;
            cursor: pointer;
            transition: all 0.2s;
        }

        .remove-file:hover {
            border-color: #ff4d4d;
            color: #ff4d4d;
        }

        .uploaded-files {
            margin: 1rem 0;
            padding: 1rem;
            background: var(--hover-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
        }

        .uploaded-files-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .uploaded-files h3 {
            margin: 0;
            color: var(--accent-clr);
            font-size: 1rem;
        }

        .toggle-btn {
            background: transparent;
            border: 1px solid var(--line-clr);
            color: var(--text-clr);
            padding: 0.4rem 0.6rem;
            border-radius: 0.3em;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-btn:hover {
            border-color: var(--accent-clr);
            color: var(--accent-clr);
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .toggle-btn.collapsed i {
            transform: rotate(-90deg);
        }

        #uploadedFilesList {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        #uploadedFilesList.collapsed {
            max-height: 0;
            opacity: 0;
            margin: 0;
            padding: 0;
        }

        .uploaded-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.50rem;
            background: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            margin-bottom: 0.5rem;
        }

        .uploaded-file-item:last-child {
            margin-bottom: 0;
        }

        .empty-state {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
        }
    </style>
</head>

<body>
    <?php require_once 'instructor_nav.php'; ?>
    <main>
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success_msg']); ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-error" id="errorAlert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['error_msg']); ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>

        <div class="page-header">
            <h2 class="page-title">Document Submission</h2>
        </div>

        <div class="table-tools">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input id="submissionSearch" type="text"
                    placeholder="Search student, section, document type, status...">
            </div>
            <button id="bulkApproveBtn" class="btn primary" type="button">
                <i class="fas fa-check"></i>
                Bulk Approve
            </button>
            <button id="uploadDocument" class="btn primary" type="button">
                <i class="fa-solid fa-file"></i>
                Upload Templates
            </button>
        </div>

        <div class="uploaded-files">
            <div class="uploaded-files-header">
                <h3>Document Requirements & Templates</h3>
                <button id="toggleUploadedFiles" class="toggle-btn collapsed" type="button"
                    aria-label="Toggle uploaded files">
                    <i class="fas fa-chevron-down" id="toggleIcon"></i>
                </button>
            </div>
            <div id="uploadedFilesList" class="collapsed">
                <h4
                    style="color: var(--text-clr); margin-bottom:0.5rem; border-bottom:1px solid var(--line-clr); padding-bottom:0.2rem;">
                    Pre-Required (Blocks Attendance)</h4>
                <?php foreach ($preRequiredDocs as $doc): ?>
                    <div class="uploaded-file-item">
                        <div class="file-info">
                            <div class="file-icon"><i class="fas fa-file-contract"></i></div>
                            <div class="file-details">
                                <div class="file-name"><?php echo htmlspecialchars($doc['name']); ?></div>
                                <div class="file-size">
                                    <?php echo $doc['template_path'] ? 'Template Available' : 'No Template Uploaded'; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem;">
                            <button class="btn" onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?>')"
                                    title=" Delete Document">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php if ($doc['template_path']): ?>
                                <button class="btn"
                                    onclick="window.openDocModal('<?php echo htmlspecialchars($doc['template_path']); ?>', '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?> Template')"
                                    title="View Template">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="<?php echo htmlspecialchars($doc['template_path']); ?>" class="btn" download
                                    target="_blank" title="Download Template">
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php endif; ?>
                            <button class="btn"
                                onclick="openUploadTemplateModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?>')"
                                title="Upload Template">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <h4
                    style="color: var(--text-clr); margin:1rem 0 0.5rem 0; border-bottom:1px solid var(--line-clr); padding-bottom:0.2rem;">
                    Other Documents (Post-OJT)</h4>
                <?php if (empty($otherDocs)): ?>
                    <div class="empty-state">No other document types defined.</div>
                <?php endif; ?>
                <?php foreach ($otherDocs as $doc): ?>
                    <div class="uploaded-file-item">
                        <div class="file-info">
                            <div class="file-icon"><i class="fas fa-file"></i></div>
                            <div class="file-details">
                                <div class="file-name"><?php echo htmlspecialchars($doc['name']); ?></div>
                                <div class="file-size">
                                    <?php echo $doc['template_path'] ? 'Template Available' : 'No Template Uploaded'; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; gap:0.5rem;">
                            <button class="btn"
                                onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?>')"
                                title="Delete Document">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php if ($doc['template_path']): ?>
                                <button class="btn"
                                    onclick="window.openDocModal('<?php echo htmlspecialchars($doc['template_path']); ?>', '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?> Template')"
                                    title="View Template">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="<?php echo htmlspecialchars($doc['template_path']); ?>" class="btn" download
                                    target="_blank" title="Download Template">
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php endif; ?>
                            <button class="btn"
                                onclick="openUploadTemplateModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['name'], ENT_QUOTES); ?>')"
                                title="Upload Template">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container">
            <div class="responsive-table">
                <table id="submissionsTable">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input id="selectAll" type="checkbox" />
                            </th>
                            <th>Student Name</th>
                            <th>Document Type</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Feedback</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="7" class="empty-state" style="padding: 2rem;">No submissions found.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($submissions as $row): ?>
                            <tr data-submission-id="<?php echo $row['id']; ?>"
                                data-file="<?php echo htmlspecialchars($row['file_path']); ?>">
                                <td class="col-check">
                                    <input class="row-check" type="checkbox" />
                                </td>
                                <td>
                                    <?php
                                    // Check if submission is late
                                    $isLate = false;
                                    if ($row['deadline'] && $row['submitted_at']) {
                                        $deadline = new DateTime($row['deadline']);
                                        $submittedAt = new DateTime($row['submitted_at']);
                                        $isLate = $submittedAt > $deadline;
                                    }
                                    ?>
                                    <div style="font-weight: 700; <?php echo $isLate ? 'color: #ff4d4d;' : ''; ?>">
                                        <?php echo htmlspecialchars($row['student_name']); ?>
                                        <?php if ($isLate): ?>

                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.7);">
                                        <?php echo htmlspecialchars($row['section']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['document_type']); ?>
                                    <?php if ($row['is_pre_required']): ?>
                                        <i class="fas fa-exclamation-circle" title="Pre-Required"
                                            style="color: var(--accent-clr); margin-left:5px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['status']); ?>" data-status>
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td data-date><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                <td class="feedback" data-feedback>
                                    <?php echo $row['feedback'] ? htmlspecialchars($row['feedback']) : '<span style="opacity:0.65;">No feedback</span>'; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($row['points'] !== null): ?>
                                        <span style="color: var(--accent-clr); font-weight: 700; font-size: 0.9rem;">
                                            <?php echo number_format($row['points'], 1); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="opacity: 0.5; font-size: 0.85rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="icon-btn" type="button" data-action="view" title="View"
                                            onclick="window.openDocModal('<?php echo htmlspecialchars($row['file_path']); ?>', '<?php echo htmlspecialchars($row['document_type']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="icon-btn" type="button" title="Process Approval"
                                            onclick="openApprovalModal(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>', '<?php echo htmlspecialchars($row['feedback'] ?? '', ENT_QUOTES); ?>', '<?php echo $row['points'] ?? ''; ?>', '<?php echo $row['accuracyQualityPoints'] ?? ''; ?>', '<?php echo $row['professionalPresentationPoints'] ?? ''; ?>')">
                                            <i class="fas fa-check-square"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="docModal" class="doc-modal" aria-hidden="true">
        <div class="doc-modal-content" role="dialog" aria-modal="true" aria-labelledby="docModalTitle">
            <div class="doc-modal-header">
                <div id="docModalTitle" class="doc-modal-title">Document Preview</div>
                <div class="doc-modal-actions">
                    <a id="docDownloadBtn" class="btn primary" href="#" download>
                        <i class="fas fa-download"></i>
                        Download
                    </a>
                    <button id="docCloseBtn" class="icon-btn" type="button" title="Close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="doc-modal-body">
                <iframe id="docFrame" class="doc-frame" src="" title="Document Preview"></iframe>
            </div>
        </div>
    </div>

    <!-- Manage Template Modal -->
    <div id="uploadModal" class="upload-modal" aria-hidden="true">
        <div class="upload-modal-content" role="dialog" aria-modal="true" aria-labelledby="uploadModalTitle">
            <div class="upload-modal-header">
                <div id="uploadModalTitle" class="upload-modal-title">Upload Template</div>
                <div class="doc-modal-actions">
                    <button id="uploadCloseBtn" class="icon-btn" type="button" title="Close"
                        onclick="closeUploadModal()"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" action="documents.php">
                <input type="hidden" name="action" value="upload_template">
                <input type="hidden" id="uploadDocTypeId" name="document_type_id" value="">

                <div class="upload-modal-body">
                    <p style="margin-bottom: 1rem; color: var(--text-clr);">
                        Upload a template for: <strong id="uploadDocTypeName"
                            style="color: var(--accent-clr);"></strong>
                    </p>

                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('templateFile').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to select file</p>
                        <small>PDF or Word Doc (Max 5MB)</small>
                        <input type="file" id="templateFile" name="template_file" class="file-input"
                            accept=".pdf,.doc,.docx" required
                            onchange="document.getElementById('fileNameDisplay').textContent = this.files[0] ? this.files[0].name : ''">
                    </div>
                    <div id="fileNameDisplay"
                        style="margin-top:0.5rem; text-align:center; color: var(--accent-clr); font-weight:bold;"></div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn" type="button" onclick="closeUploadModal()">Cancel</button>
                        <button type="submit" class="btn primary">Upload Template</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Document Type Modal -->
    <div id="createDocModal" class="upload-modal" aria-hidden="true">
        <div class="upload-modal-content" role="dialog" aria-modal="true" aria-labelledby="createDocModalTitle">
            <div class="upload-modal-header">
                <div id="createDocModalTitle" class="upload-modal-title">New Document Requirement</div>
                <div class="doc-modal-actions">
                    <button id="createDocCloseBtn" class="icon-btn" type="button" title="Close"
                        onclick="closeCreateDocModal()"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" action="documents.php">
                <input type="hidden" name="action" value="create_document">

                <div class="upload-modal-body">
                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Document
                            Name</label>
                        <input type="text" name="name" required placeholder="e.g. Weekly Report 1"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Document
                            Code</label>
                        <input type="text" name="code" required placeholder="e.g. WEEKLY_1"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Category</label>
                        <select name="category" required
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                            <option value="other">Other (Post-OJT)</option>
                            <option value="pre_required">Pre-Required (Blocks Attendance)</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="excuse">Excuse Letter</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Submission
                            Deadline (Optional)</label>
                        <input type="date" name="deadline" placeholder="Select deadline date"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                        <small
                            style="display:block; margin-top:0.3rem; color: rgba(255,255,255,0.6); font-size:0.85rem;">Students
                            will be notified of this deadline</small>
                    </div>

                    <div style="margin-bottom: 0.5rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Template
                            File</label>
                        <div class="upload-area" style="padding: 1.5rem;"
                            onclick="document.getElementById('createTemplateFile').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem;"></i>
                            <p style="font-size: 0.9rem;">Click to select file</p>
                            <small>PDF or Word Doc (Max 5MB)</small>
                            <input type="file" id="createTemplateFile" name="template_file" class="file-input"
                                accept=".pdf,.doc,.docx"
                                onchange="document.getElementById('createFileNameDisplay').textContent = this.files[0] ? this.files[0].name : ''">
                        </div>
                        <div id="createFileNameDisplay"
                            style="margin-top:0.5rem; text-align:center; color: var(--accent-clr); font-weight:bold; font-size: 0.9rem;">
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn" type="button" onclick="closeCreateDocModal()">Cancel</button>
                        <button type="submit" class="btn primary">Create Document</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="upload-modal" aria-hidden="true">
        <div class="upload-modal-content" role="dialog" aria-modal="true" aria-labelledby="approvalModalTitle">
            <div class="upload-modal-header">
                <div id="approvalModalTitle" class="upload-modal-title">Process Submission</div>
                <div class="doc-modal-actions">
                    <button id="approvalCloseBtn" class="icon-btn" type="button" title="Close"
                        onclick="closeApprovalModal()"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <form method="POST" action="documents.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" id="approvalSubId" name="submission_id" value="">

                <div class="upload-modal-body">
                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Status</label>
                        <select id="approvalStatus" name="status" required
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                            <option value="pending">Pending</option>
                            <option value="approved">Approve</option>
                            <option value="revise">Revise</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label
                            style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">Feedback</label>
                        <textarea id="approvalFeedback" name="feedback" rows="4"
                            placeholder="Enter feedback for the student..."
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none; resize: vertical;"></textarea>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">
                            Bonus Points (Optional)
                            <i class="fas fa-info-circle" title="Award bonus points for exceptional work"
                                style="font-size: 0.8rem; opacity: 0.7; margin-left: 4px;"></i>
                        </label>
                        <input type="number" id="approvalPoints" name="points" min="0" max="100" step="0.5"
                            placeholder="e.g., 5.0"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                        <small
                            style="display:block; margin-top:0.3rem; color: rgba(255,255,255,0.6); font-size:0.85rem;">
                            Leave blank if no bonus points
                        </small>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">
                            Accuracy & Quality Points (Optional)
                            <i class="fas fa-info-circle" title="Award points for accuracy and quality of content"
                                style="font-size: 0.8rem; opacity: 0.7; margin-left: 4px;"></i>
                        </label>
                        <input type="number" id="approvalAccuracyQualityPoints" name="accuracyQualityPoints" min="0"
                            max="100" step="0.5" placeholder="e.g., 10.0"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                        <small
                            style="display:block; margin-top:0.3rem; color: rgba(255,255,255,0.6); font-size:0.85rem;">
                            Points for content accuracy and quality
                        </small>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; color:var(--text-clr); margin-bottom:0.4rem; font-size:0.9rem;">
                            Professional Presentation Points (Optional)
                            <i class="fas fa-info-circle"
                                title="Award points for professional formatting and presentation"
                                style="font-size: 0.8rem; opacity: 0.7; margin-left: 4px;"></i>
                        </label>
                        <input type="number" id="approvalProfessionalPresentationPoints"
                            name="professionalPresentationPoints" min="0" max="100" step="0.5" placeholder="e.g., 8.0"
                            style="width: 100%; padding: 0.6rem 0.8rem; border-radius: .5em; border: 1px solid var(--line-clr); background: var(--base-clr); color: var(--text-clr); outline: none;">
                        <small
                            style="display:block; margin-top:0.3rem; color: rgba(255,255,255,0.6); font-size:0.85rem;">
                            Points for formatting and professional presentation
                        </small>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button class="btn" type="button" onclick="closeApprovalModal()">Cancel</button>
                        <button type="submit" class="btn primary">Update Status</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Approval Form -->
    <form id="bulkApproveForm" method="POST" action="documents.php" style="display:none;">
        <input type="hidden" name="action" value="bulk_approve">
        <input type="hidden" id="bulkApprovedIds" name="submission_ids" value="">
    </form>

    <!-- Delete Document Form -->
    <form id="deleteDocumentForm" method="POST" action="documents.php" style="display:none;">
        <input type="hidden" name="action" value="delete_document">
        <input type="hidden" id="deleteDocumentId" name="document_id" value="">
    </form>

    <script>
        function deleteDocument(id, name) {
            if (confirm(`Are you sure you want to delete the document requirement "${name}"? This cannot be undone.`)) {
                document.getElementById('deleteDocumentId').value = id;
                document.getElementById('deleteDocumentForm').submit();
            }
        }

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Modal Functions
        const uploadModal = document.getElementById('uploadModal');

        function openUploadTemplateModal(id, name) {
            document.getElementById('uploadDocTypeId').value = id;
            document.getElementById('uploadDocTypeName').textContent = name;
            document.getElementById('fileNameDisplay').textContent = '';
            document.getElementById('templateFile').value = ''; // Reset file input

            uploadModal.classList.add('open');
            uploadModal.setAttribute('aria-hidden', 'false');
        }

        function closeUploadModal() {
            uploadModal.classList.remove('open');
            uploadModal.setAttribute('aria-hidden', 'true');
        }

        const createDocModal = document.getElementById('createDocModal');
        function openCreateDocModal() {
            createDocModal.classList.add('open');
            createDocModal.setAttribute('aria-hidden', 'false');
        }
        function closeCreateDocModal() {
            createDocModal.classList.remove('open');
            createDocModal.setAttribute('aria-hidden', 'true');
        }

        const approvalModal = document.getElementById('approvalModal');
        function openApprovalModal(submissionId, currentStatus, currentFeedback, currentPoints, accuracyQualityPoints, professionalPresentationPoints) {
            document.getElementById('approvalSubId').value = submissionId;
            document.getElementById('approvalStatus').value = currentStatus || 'pending';
            document.getElementById('approvalFeedback').value = currentFeedback || '';
            document.getElementById('approvalPoints').value = currentPoints || '';
            document.getElementById('approvalAccuracyQualityPoints').value = accuracyQualityPoints || '';
            document.getElementById('approvalProfessionalPresentationPoints').value = professionalPresentationPoints || '';

            approvalModal.classList.add('open');
            approvalModal.setAttribute('aria-hidden', 'false');
        }

        function closeApprovalModal() {
            approvalModal.classList.remove('open');
            approvalModal.setAttribute('aria-hidden', 'true');
        }

        (function () {
            const table = document.getElementById('submissionsTable');
            const searchInput = document.getElementById('submissionSearch');
            const selectAll = document.getElementById('selectAll');
            const bulkApproveBtn = document.getElementById('bulkApproveBtn');
            // if (!table || !searchInput || !selectAll || !bulkApproveBtn) return; // Allow running even if empty

            // Restore missing variables
            const tbody = table ? table.querySelector('tbody') : null;
            const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];

            // Debugging Logs
            console.log('Script initialized');
            console.log('Submissions Table:', table);
            console.log('TBody:', tbody);
            console.log('Toggle Button:', document.getElementById('toggleUploadedFiles'));

            if (!tbody) {
                console.warn('Submissions table body not found. Submission features disabled.');
            }
            const docModal = document.getElementById('docModal');
            const docFrame = document.getElementById('docFrame');
            const docCloseBtn = document.getElementById('docCloseBtn');
            const docDownloadBtn = document.getElementById('docDownloadBtn');



            // Global scope for view button
            window.openDocModal = function (fileUrl, title) {
                if (!docModal || !docFrame || !docDownloadBtn) return;
                docFrame.src = fileUrl;
                docDownloadBtn.href = fileUrl;
                if (title) {
                    const titleEl = document.getElementById('docModalTitle');
                    if (titleEl) titleEl.textContent = title;
                }
                docModal.classList.add('open');
                docModal.setAttribute('aria-hidden', 'false');
            };

            function closeDocModal() {
                if (!docModal || !docFrame) return;
                docModal.classList.remove('open');
                docModal.setAttribute('aria-hidden', 'true');
                docFrame.src = '';
            }

            if (docCloseBtn) {
                docCloseBtn.addEventListener('click', closeDocModal);
            }

            if (docModal) {
                docModal.addEventListener('click', function (e) {
                    if (e.target === docModal) closeDocModal();
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeDocModal();
            });

            function setStatus(row, status, feedbackText) {
                const badge = row.querySelector('[data-status]');
                const feedbackEl = row.querySelector('[data-feedback]');
                if (!badge || !feedbackEl) return;

                badge.classList.remove('status-pending', 'status-approve', 'status-revise', 'status-reject');
                badge.classList.add('status-' + status);
                badge.textContent = status;

                if (typeof feedbackText === 'string') {
                    feedbackEl.innerHTML = feedbackText.trim() !== ''
                        ? feedbackText
                        : '<span style="opacity:0.65;">No feedback</span>';
                }
            }

            function getVisibleRows() {
                return rows.filter(r => r.style.display !== 'none');
            }

            function updateSelectAllState() {
                const visible = getVisibleRows();
                const checks = visible.map(r => r.querySelector('.row-check')).filter(Boolean);
                if (checks.length === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                    return;
                }
                const checkedCount = checks.filter(c => c.checked).length;
                selectAll.checked = checkedCount === checks.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checks.length;
            }

            function applySearch() {
                const q = (searchInput.value || '').trim().toLowerCase();
                rows.forEach(row => {
                    const txt = row.textContent.toLowerCase();
                    row.style.display = (!q || txt.includes(q)) ? '' : 'none';
                });
                updateSelectAllState();
            }

            searchInput.addEventListener('input', applySearch);

            selectAll.addEventListener('change', function () {
                const visible = getVisibleRows();
                visible.forEach(row => {
                    const cb = row.querySelector('.row-check');
                    if (cb) cb.checked = selectAll.checked;
                });
                updateSelectAllState();
            });

            tbody.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('row-check')) {
                    updateSelectAllState();
                }
            });

            bulkApproveBtn.addEventListener('click', function () {
                const visible = getVisibleRows();
                const selected = visible.filter(row => {
                    const cb = row.querySelector('.row-check');
                    return cb && cb.checked;
                });

                if (selected.length === 0) return;

                selected.forEach(row => {
                    setStatus(row, 'approve', 'Complete and verified.');
                });
            });

            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const row = btn.closest('tr');
                if (!row) return;

                const action = btn.getAttribute('data-action');
                if (action === 'view') {
                    const fileUrl = row.getAttribute('data-file') || '../images/documentSample/PDF Sample 1.pdf';
                    const docTypeCell = row.children && row.children[2] ? row.children[2].textContent.trim() : 'Document Preview';
                    openDocModal(fileUrl, docTypeCell);
                    return;
                }

                if (action === 'approve') {
                    setStatus(row, 'approve', 'Complete and verified.');
                    return;
                }

                if (action === 'revise') {
                    const fb = prompt('Enter feedback for revision:', 'Please re-upload with correct format.');
                    if (fb === null) return;
                    setStatus(row, 'revise', fb);
                    return;
                }

                if (action === 'reject') {
                    const fb = prompt('Enter reason for rejection:', 'Document rejected.');
                    if (fb === null) return;
                    setStatus(row, 'reject', fb);
                }
            });

            // Toggle Uploaded Files functionality
            const toggleBtn = document.getElementById('toggleUploadedFiles');
            const toggleIcon = document.getElementById('toggleIcon');
            const uploadedFilesList = document.getElementById('uploadedFilesList');

            if (toggleBtn && toggleIcon && uploadedFilesList) {
                toggleBtn.addEventListener('click', function () {
                    const isCollapsed = uploadedFilesList.classList.contains('collapsed');

                    if (isCollapsed) {
                        uploadedFilesList.classList.remove('collapsed');
                        toggleBtn.classList.remove('collapsed');
                        toggleIcon.className = 'fas fa-chevron-down';
                    } else {
                        uploadedFilesList.classList.add('collapsed');
                        toggleBtn.classList.add('collapsed');
                        toggleIcon.className = 'fas fa-chevron-up';
                    }
                });
            }

            // Approval Modal Logic
            const approvalModal = document.getElementById('approvalModal');
            window.openApprovalModal = function (id, status, feedback) {
                document.getElementById('approvalSubId').value = id;
                document.getElementById('approvalStatus').value = status.toLowerCase() === 'pending' ? 'approved' : status.toLowerCase(); // Default to approved if pending
                document.getElementById('approvalFeedback').value = feedback || '';
                approvalModal.classList.add('open');
                approvalModal.setAttribute('aria-hidden', 'false');
            };
            window.closeApprovalModal = function () {
                approvalModal.classList.remove('open');
                approvalModal.setAttribute('aria-hidden', 'true');
            };

            // Bulk Approve Logic
            if (bulkApproveBtn) {
                bulkApproveBtn.addEventListener('click', function () {
                    const visible = getVisibleRows();
                    const selectedIds = visible
                        .filter(row => {
                            const cb = row.querySelector('.row-check');
                            return cb && cb.checked;
                        })
                        .map(row => row.getAttribute('data-submission-id'))
                        .filter(Boolean);

                    if (selectedIds.length === 0) {
                        alert('Please select at least one item to approve.');
                        return;
                    }

                    if (confirm('Are you sure you want to approve ' + selectedIds.length + ' documents?')) {
                        document.getElementById('bulkApprovedIds').value = JSON.stringify(selectedIds);
                        document.getElementById('bulkApproveForm').submit();
                    }
                });
            }

            // Main Action Button (Manage Templates)
            const mainUploadBtn = document.getElementById('uploadDocument');
            if (mainUploadBtn) {
                mainUploadBtn.removeEventListener('click', function () { }); // Remove old listeners if any (not strictly needed as this is a fresh run context usually)
                // Or rather, we are replacing the block where it was defined.
                mainUploadBtn.addEventListener('click', function () {
                    openCreateDocModal();
                });
            }

            // Search Initial Apply
            applySearch();
        })();
    </script>
</body>

</html>