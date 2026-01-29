<?php
// Require admin authentication
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid var(--line-clr);
            border-radius: 4px;
            color: var(--text-clr);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination-link:hover:not(.disabled):not(.active) {
            background-color: var(--hover-clr);
            border-color: var(--accent-clr);
            color: var(--accent-clr);
        }

        .pagination-link.active {
            background-color: var(--accent-clr);
            border-color: var(--accent-clr);
            color: #fff;
            font-weight: 600;
        }

        .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-ellipsis {
            padding: 0 10px;
            color: var(--text-clr);
        }

        .user-management {
            padding: 0;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        .user-table th {
            background-color: var(--hover-clr);
            font-weight: 600;
            color: var(--accent-clr);
        }

        .user-table tr:hover {
            background-color: var(--hover-clr);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            background-color: var(--base-clr);
            color: var(--text-clr);
        }

        .btn-change-password {
            border-color: var(--accent-clr);
            color: var(--accent-clr);
        }

        .btn-delete {
            border-color: #e74c3c;
            color: #e74c3c;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-user-btn {
            background-color: var(--accent-clr);
            color: white;
            padding: 6px;
            border-radius: 0.5em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            font: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .add-user-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 0.5em;
            border: 1px solid var(--line-clr);
            margin-top: 1em;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
        }

        .user-table th,
        .user-table td {
            padding: 1em;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        .user-table th {
            background-color: var(--hover-clr);
            color: var(--accent-clr);
            font-weight: 500;
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        .container h2 {
            margin-top: 0;
            color: var(--text-clr);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--base-clr);
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5em;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5em;
            border-bottom: 1px solid var(--line-clr);
            padding-bottom: 0.75em;
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
            padding: 0.25em;
        }

        .form-group {
            margin-bottom: 1.25em;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5em;
            color: var(--text-clr);
        }

        .form-control {
            width: 100%;
            padding: 0.75em;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            background-color: var(--base-clr);
            color: var(--text-clr);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-clr);
        }

        .btn-group {
            display: flex;
            gap: 1em;
            margin-top: 1.5em;
            justify-content: flex-end;
        }

        .btn-primary {
            background-color: var(--accent-clr);
            color: white;
            border: none;
            padding: 0.75em 1.5em;
            border-radius: 0.5em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-clr);
            border: 1px solid var(--line-clr);
            padding: 0.75em 1.5em;
            border-radius: 0.5em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: var(--hover-clr);
        }

        .file-upload {
            border: 2px dashed var(--line-clr);
            border-radius: 0.5em;
            padding: 2em;
            text-align: center;
            margin: 1em 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: var(--accent-clr);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5em;
            color: var(--secondary-text-clr);
        }

        .file-upload-label i {
            font-size: 2em;
            color: var(--accent-clr);
        }

        .registration-options {
            display: flex;
            gap: 1em;
            margin-bottom: 1.5em;
        }

        .registration-option {
            flex: 1;
            text-align: center;
            padding: 1.5em;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .registration-option:hover {
            border-color: var(--accent-clr);
            background-color: var(--hover-clr);
        }

        .registration-option i {
            font-size: 2em;
            margin-bottom: 0.5em;
            color: var(--accent-clr);
        }

        .password-toggle {
            color: var(--secondary-text-clr);
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--accent-clr);
        }

        /* CSV Upload Messages */
        #csvMessage {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        #csvMessage.alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        #csvMessage.alert-warning {
            background-color: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid #ffa500;
        }

        #csvMessage.alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }

        /* Manual Registration Messages */
        #manualMessage {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        #manualMessage.alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        #manualMessage.alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }

        /* Change Password Messages */
        #changePasswordMessage {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        #changePasswordMessage.alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        #changePasswordMessage.alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }

        /* Email Messages */
        #emailMessage {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        #emailMessage.alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        #emailMessage.alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }

        #emailMessage.alert-warning {
            background-color: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid #ffa500;
        }

        /* Archive Messages */
        #archiveMessage {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        #archiveMessage.alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        #archiveMessage.alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }

        #archiveMessage.alert-warning {
            background-color: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid #ffa500;
        }
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>

    <?php
    require_once __DIR__ . '/../../app/services/UserService.php';

    use App\Services\UserService;

    $userService = new UserService();

    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
    $sectionFilter = isset($_GET['section']) ? trim($_GET['section']) : '';
    $yearFilter = isset($_GET['year']) ? trim($_GET['year']) : '';
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    // Get users from database
    $options = [
        'page' => $page,
        'per_page' => 10,
        'search' => $search,
        'role' => $roleFilter,
        'section' => $sectionFilter,
        'year' => $yearFilter
    ];

    $result = $userService->getUsers($options);
    $users = $result['users'];
    $total_records = $result['total'];
    $total_pages = $result['total_pages'];

    // Get sections and years for filter dropdowns
    $sections = $userService->getSections();
    $years = $userService->getYears();
    ?>

    <main class="user-management">
        <div class="container">
            <div class="header-section">
                <h2>User Management</h2>
                <div class="header-actions">
                    <button id="archiveBtn" class="btn btn-secondary">
                        <i class="fas fa-archive"></i> Archive Students
                    </button>
                    <button id="emailBtn" class="btn btn-secondary" style="white-space: nowrap;"
                        onclick="openModal('emailModal')">
                        <i class="fas fa-envelope"></i> Compose Email
                    </button>
                    <button id="addUserBtn" class="add-user-btn">
                        <i class="fas fa-plus"></i> Add New User
                    </button>

                </div>
            </div>
            <div class="search-section" style="margin: 15px 0;">
                <div style="display: flex; flex-wrap: nowrap; gap: 10px; align-items: center;">
                    <div style="position: relative; min-width: 250px; flex: 1;">
                        <input type="text" id="searchInput" placeholder="Search users..." class="form-control"
                            style="padding-left: 40px; width: 88%;" value="<?php echo htmlspecialchars($search); ?>">
                        <i class="fas fa-search"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--secondary-text-clr);"></i>
                    </div>
                    <button id="searchBtn" class="btn btn-secondary" style="white-space: nowrap;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <div class="filter-dropdown" style="min-width: 150px;">
                        <select id="roleFilter" class="form-control"
                            style="padding: 8px 15px; border-radius: 4px; border: 1px solid #ddd; background-color: white; cursor: pointer; width: 100%; color: #000000;">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo ($roleFilter === 'student') ? 'selected' : ''; ?>>Student
                            </option>
                            <option value="admin" <?php echo ($roleFilter === 'admin') ? 'selected' : ''; ?>>Admin
                            </option>
                            <option value="instructor" <?php echo ($roleFilter === 'instructor') ? 'selected' : ''; ?>>
                                Instructor</option>
                        </select>
                    </div>
                    <div class="filter-dropdown" style="min-width: 150px;">
                        <select id="sectionFilter" class="form-control"
                            style="padding: 8px 15px; border-radius: 4px; border: 1px solid #ddd; background-color: white; cursor: pointer; width: 100%; color: #000000;">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section_name']); ?>" <?php echo ($sectionFilter === $section['section_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-dropdown" style="min-width: 120px;">
                        <select id="yearFilter" class="form-control"
                            style="padding: 8px 15px; border-radius: 4px; border: 1px solid #ddd; background-color: white; cursor: pointer; width: 100%; color: #000000;">
                            <option value="">All Years</option>
                            <?php foreach ($years as $yearData): ?>
                                <option value="<?php echo htmlspecialchars($yearData['year']); ?>" <?php echo ($yearFilter === $yearData['year']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($yearData['year']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>School ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Section</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7"
                                    style="text-align: center; padding: 2rem; color: var(--secondary-text-clr);">
                                    <i class="fas fa-users"
                                        style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                    No users found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['school_id']); ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'student'): ?>
                                            <a href="admin_student_information.php?id=<?php echo htmlspecialchars($user['id']); ?>"
                                                style="color: var(--accent-clr); text-decoration: none; font-weight: 500;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['section_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['year'] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn btn-change-password" title="Change Password"
                                            data-user-id="<?php echo htmlspecialchars($user['id']); ?>"
                                            data-school-id="<?php echo htmlspecialchars($user['school_id']); ?>">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn btn-delete" title="Delete Account"
                                            data-user-id="<?php echo htmlspecialchars($user['id']); ?>"
                                            data-school-id="<?php echo htmlspecialchars($user['school_id']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build query string for pagination links
                        $queryParams = [];
                        if (!empty($search))
                            $queryParams['search'] = $search;
                        if (!empty($roleFilter))
                            $queryParams['role'] = $roleFilter;
                        if (!empty($sectionFilter))
                            $queryParams['section'] = $sectionFilter;
                        if (!empty($yearFilter))
                            $queryParams['year'] = $yearFilter;
                        $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo $queryString; ?>" class="pagination-link" title="First Page"><i
                                    class="fas fa-angle-double-left"></i></a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="pagination-link"
                                title="Previous"><i class="fas fa-angle-left"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="pagination-link disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>

                        <?php
                        // Show page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo '<a href="?page=1' . $queryString . '" class="pagination-link">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>"
                                class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . $queryString . '" class="pagination-link">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="pagination-link"
                                title="Next"><i class="fas fa-angle-right"></i></a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo $queryString; ?>" class="pagination-link"
                                title="Last Page"><i class="fas fa-angle-double-right"></i></a>
                        <?php else: ?>
                            <span class="pagination-link disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="pagination-link disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Users</h3>
                <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <div class="registration-options">
                <div class="registration-option" onclick="showRegistrationForm('manual')">
                    <i class="fas fa-user-plus"></i>
                    <h4>Manual Registration</h4>
                    <p>Add a single user manually</p>
                </div>
                <div class="registration-option" onclick="showRegistrationForm('csv')">
                    <i class="fas fa-file-csv"></i>
                    <h4>CSV Upload</h4>
                    <p>Upload multiple users via CSV</p>
                </div>
            </div>

            <!-- Manual Registration Form -->
            <div id="manualRegistrationForm" style="display: none;">
                <div id="manualMessage" style="display: none; margin-bottom: 1rem;"></div>
                <form id="manualForm">
                    <div class="form-group">
                        <label for="schoolId">School ID <span style="color: #e74c3c;">*</span></label>
                        <input type="text" id="schoolId" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="fullName">Full Name <span style="color: #e74c3c;">*</span></label>
                        <input type="text" id="fullName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span style="color: #e74c3c;">*</span></label>
                        <input type="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role <span style="color: #e74c3c;">*</span></label>
                        <select id="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span style="color: #e74c3c;">*</span></label>
                        <select id="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="non-binary">Non-binary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="section">Section</label>
                        <input type="text" id="section" class="form-control" placeholder="e.g., BSIT-4A">
                        <small style="color: var(--secondary-text-clr);">Required for students</small>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="text" id="year" class="form-control" placeholder="2025" value="2025">
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="text" id="contact" class="form-control" placeholder="e.g., 09123456789">
                    </div>
                    <div class="form-group">
                        <label for="facebookName">Facebook Name</label>
                        <input type="text" id="facebookName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span style="color: #e74c3c;">*</span></label>
                        <input type="password" id="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password <span style="color: #e74c3c;">*</span></label>
                        <input type="password" id="confirmPassword" class="form-control" required>
                        <div id="passwordMatchError"
                            style="color: #e74c3c; font-size: 0.85em; margin-top: 5px; display: none;">
                            Passwords do not match!
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary"
                            onclick="closeModal('addUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="registerUserBtn">Register User</button>
                    </div>
                </form>
            </div>

            <!-- CSV Registration Form -->
            <div id="csvRegistrationForm" style="display: none;">
                <div id="csvMessage" style="display: none; margin-bottom: 1rem;"></div>
                <div class="form-group">
                    <label>Download CSV Template</label>
                    <a href="download_template.php" class="btn btn-secondary"
                        style="display: inline-flex; align-items: center; gap: 0.5em;">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
                <div class="file-upload" id="csvUploadArea">
                    <label for="csvFile" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Click to upload CSV file</span>
                        <small>or drag and drop</small>
                    </label>
                    <input type="file" id="csvFile" accept=".csv">
                </div>
                <div id="fileInfo" style="margin: 1em 0; display: none;">
                    <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                    <span id="fileName"></span>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="button" id="uploadCsvBtn" class="btn btn-primary" disabled>Upload & Register
                        Users</button>
                </div>
            </div>
        </div>
    </div>

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
                        <option value="all_instructors">All Instructors</option>
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

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title">Change Password</h3>
                <button class="close-btn" onclick="closeModal('changePasswordModal')">&times;</button>
            </div>
            <div id="changePasswordMessage" style="display: none; margin-bottom: 1rem;"></div>
            <div class="form-group">
                <label for="newPassword">New Password <span style="color: #e74c3c;">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="newPassword" class="form-control" required minlength="6">
                    <i class="fas fa-eye password-toggle"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                        data-target="newPassword"></i>
                </div>
                <small style="color: var(--secondary-text-clr);">Minimum 6 characters</small>
            </div>
            <div class="form-group">
                <label for="confirmNewPassword">Confirm New Password <span style="color: #e74c3c;">*</span></label>
                <div style="position: relative;">
                    <input type="password" id="confirmNewPassword" class="form-control" required minlength="6">
                    <i class="fas fa-eye password-toggle"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                        data-target="confirmNewPassword"></i>
                </div>
                <div id="passwordMatchError" style="color: #e74c3c; font-size: 0.85em; margin-top: 5px; display: none;">
                    Passwords do not match!
                </div>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-secondary"
                    onclick="closeModal('changePasswordModal')">Cancel</button>
                <button type="button" id="savePasswordBtn" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Archive Students Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Archive Students</h3>
                <button class="close-btn" onclick="closeModal('archiveModal')">&times;</button>
            </div>
            <div id="archiveMessage" style="display: none; margin-bottom: 1rem;"></div>

            <div class="form-group">
                <label for="archiveYear">Filter by Year</label>
                <select id="archiveYear" class="form-control" onchange="loadStudentsForArchive()">
                    <option value="">All Years</option>
                    <?php foreach ($years as $yearData): ?>
                        <option value="<?php echo htmlspecialchars($yearData['year']); ?>">
                            <?php echo htmlspecialchars($yearData['year']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5em;">
                    <label style="margin: 0;">Select Students to Archive</label>
                    <div>
                        <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;"
                            onclick="selectAllStudents()">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;"
                            onclick="deselectAllStudents()">
                            <i class="fas fa-square"></i> Deselect All
                        </button>
                    </div>
                </div>
                <div id="studentsListContainer"
                    style="max-height: 400px; overflow-y: auto; border: 1px solid var(--line-clr); border-radius: 0.5em; padding: 1em; background-color: var(--hover-clr);">
                    <div style="text-align: center; color: var(--secondary-text-clr); padding: 2em;">
                        <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 0.5em; display: block;"></i>
                        Select a year to load students
                    </div>
                </div>
                <div id="selectedCount" style="margin-top: 0.5em; color: var(--secondary-text-clr); font-size: 0.9em;">
                    0 students selected
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="confirmArchive">
                    I understand this action will archive the selected students. This action cannot be undone.
                </label>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
                <button type="button" id="archiveConfirmBtn" class="btn btn-primary" disabled>Archive Selected
                    Students</button>
            </div>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset forms when closing
            if (modalId === 'addUserModal') {
                document.getElementById('manualRegistrationForm').style.display = 'none';
                document.getElementById('csvRegistrationForm').style.display = 'none';
                document.getElementById('manualForm').reset();
                document.getElementById('csvFile').value = '';
                document.getElementById('fileInfo').style.display = 'none';
                document.getElementById('uploadCsvBtn').disabled = true;
                document.getElementById('manualMessage').style.display = 'none';
                document.getElementById('passwordMatchError').style.display = 'none';
                document.getElementById('registerUserBtn').disabled = false;
                document.getElementById('registerUserBtn').innerHTML = 'Register User';
            } else if (modalId === 'changePasswordModal') {
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmNewPassword').value = '';
                document.getElementById('passwordMatchError').style.display = 'none';
                document.getElementById('changePasswordMessage').style.display = 'none';
                document.getElementById('savePasswordBtn').disabled = false;
                document.getElementById('savePasswordBtn').innerHTML = 'Save Changes';
                currentUserId = null;
                currentSchoolId = null;
            } else if (modalId === 'emailModal') {
                document.getElementById('emailSubject').value = '';
                document.getElementById('emailBody').value = '';
                document.getElementById('recipientType').value = 'all_students';
                document.getElementById('specificStudentContainer').style.display = 'none';
                document.getElementById('specificStudent').value = '';
                document.getElementById('emailMessage').style.display = 'none';
                document.getElementById('sendEmailBtn').disabled = false;
                document.getElementById('sendEmailBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Send';
            } else if (modalId === 'archiveModal') {
                document.getElementById('archiveYear').value = '';
                document.getElementById('confirmArchive').checked = false;
                document.getElementById('archiveConfirmBtn').disabled = true;
                document.getElementById('archiveMessage').style.display = 'none';
                document.getElementById('archiveConfirmBtn').innerHTML = 'Archive Selected Students';
                document.getElementById('studentsListContainer').innerHTML = `
                    <div style="text-align: center; color: var(--secondary-text-clr); padding: 2em;">
                        <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 0.5em; display: block;"></i>
                        Select a year to load students
                    </div>
                `;
                document.getElementById('selectedCount').textContent = '0 students selected';
            }
        }

        function showRegistrationForm(type) {
            if (type === 'manual') {
                document.getElementById('manualRegistrationForm').style.display = 'block';
                document.getElementById('csvRegistrationForm').style.display = 'none';
            } else if (type === 'csv') {
                document.getElementById('manualRegistrationForm').style.display = 'none';
                document.getElementById('csvRegistrationForm').style.display = 'block';
            }
        }

        // Close modal when clicking outside content
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Add User Button
            document.getElementById('addUserBtn').addEventListener('click', function () {
                openModal('addUserModal');
            });

            // Archive Button
            document.getElementById('archiveBtn').addEventListener('click', function () {
                openModal('archiveModal');
            });

            // CSV File Upload
            const csvFileInput = document.getElementById('csvFile');
            const fileUploadArea = document.getElementById('csvUploadArea');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const uploadCsvBtn = document.getElementById('uploadCsvBtn');

            csvFileInput.addEventListener('change', function (e) {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    fileName.textContent = file.name;
                    fileInfo.style.display = 'flex';
                    fileInfo.style.alignItems = 'center';
                    fileInfo.style.gap = '0.5em';
                    uploadCsvBtn.disabled = false;
                }
            });

            // Drag and drop for CSV upload
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                fileUploadArea.style.borderColor = 'var(--accent-clr)';
                fileUploadArea.style.backgroundColor = 'var(--hover-clr)';
            }

            function unhighlight() {
                fileUploadArea.style.borderColor = 'var(--line-clr)';
                fileUploadArea.style.backgroundColor = 'transparent';
            }

            fileUploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0 && files[0].name.endsWith('.csv')) {
                    csvFileInput.files = files;
                    const event = new Event('change');
                    csvFileInput.dispatchEvent(event);
                }
            }

            // Archive confirmation - updated for student selection
            const confirmArchive = document.getElementById('confirmArchive');
            const archiveConfirmBtn = document.getElementById('archiveConfirmBtn');

            confirmArchive.addEventListener('change', function () {
                updateArchiveButtonState();
            });

            // Manual Form submission
            document.getElementById('manualForm').addEventListener('submit', function (e) {
                e.preventDefault();

                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const role = document.getElementById('role').value;
                const section = document.getElementById('section').value;

                // Validate passwords match
                if (password !== confirmPassword) {
                    showManualMessage('Passwords do not match!', 'error');
                    document.getElementById('passwordMatchError').style.display = 'block';
                    return;
                } else {
                    document.getElementById('passwordMatchError').style.display = 'none';
                }

                // Validate section for students
                if (role === 'student' && !section.trim()) {
                    showManualMessage('Section is required for students', 'error');
                    return;
                }

                // Disable submit button
                const registerBtn = document.getElementById('registerUserBtn');
                registerBtn.disabled = true;
                registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

                // Prepare form data
                const formData = {
                    school_id: document.getElementById('schoolId').value.trim(),
                    full_name: document.getElementById('fullName').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    role: role,
                    gender: document.getElementById('gender').value,
                    section: section.trim() || null,
                    year: document.getElementById('year').value.trim() || '2025',
                    contact: document.getElementById('contact').value.trim() || null,
                    facebook_name: document.getElementById('facebookName').value.trim() || null,
                    password: password
                };

                // Send to server
                fetch('register_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showManualMessage(data.message || 'User registered successfully!', 'success');
                            // Reset form
                            document.getElementById('manualForm').reset();
                            // Close modal and refresh page after delay
                            setTimeout(() => {
                                closeModal('addUserModal');
                                window.location.reload();
                            }, 2000);
                        } else {
                            showManualMessage(data.message || 'Error registering user. Please try again.', 'error');
                            registerBtn.disabled = false;
                            registerBtn.innerHTML = 'Register User';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showManualMessage('Network error. Please try again.', 'error');
                        registerBtn.disabled = false;
                        registerBtn.innerHTML = 'Register User';
                    });
            });

            // Function to show manual registration messages
            function showManualMessage(message, type) {
                const messageDiv = document.getElementById('manualMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
                messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'times-circle') + ' me-2"></i>' +
                    message.replace(/\n/g, '<br>');

                // Auto-hide after 5 seconds for success, 10 for errors
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, type === 'success' ? 5000 : 10000);
            }

            // CSV Upload
            uploadCsvBtn.addEventListener('click', function () {
                const file = csvFileInput.files[0];
                if (!file) {
                    showCSVMessage('Please select a CSV file', 'error');
                    return;
                }

                // Validate file type
                if (!file.name.endsWith('.csv')) {
                    showCSVMessage('Please upload a valid CSV file', 'error');
                    return;
                }

                // Disable button during upload
                uploadCsvBtn.disabled = true;
                uploadCsvBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

                const formData = new FormData();
                formData.append('csvFile', file);

                fetch('upload_users.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let message = `Successfully registered ${data.successful} out of ${data.total} users.`;
                            if (data.failed > 0) {
                                message += `\n\n${data.failed} users failed to register:`;
                                data.errors.forEach(error => {
                                    message += `\n- Row ${error.row} (${error.school_id}): ${error.message}`;
                                });
                            }
                            showCSVMessage(message, data.failed > 0 ? 'warning' : 'success');

                            // If all successful, close modal and refresh page after delay
                            if (data.failed === 0) {
                                setTimeout(() => {
                                    closeModal('addUserModal');
                                    window.location.reload();
                                }, 2000);
                            }
                        } else {
                            showCSVMessage(data.message || 'Error uploading CSV file', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showCSVMessage('Network error. Please try again.', 'error');
                    })
                    .finally(() => {
                        uploadCsvBtn.disabled = false;
                        uploadCsvBtn.innerHTML = '<i class="fas fa-upload"></i> Upload & Register Users';
                    });
            });

            // Function to show CSV upload messages
            function showCSVMessage(message, type) {
                const messageDiv = document.getElementById('csvMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger');
                messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle') + ' me-2"></i>' +
                    message.replace(/\n/g, '<br>');

                // Auto-hide after 10 seconds for success, 15 for errors
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, type === 'success' ? 10000 : 15000);
            }

            // Archive confirmation button click handler
            document.getElementById('archiveConfirmBtn').addEventListener('click', function () {
                const confirmCheckbox = document.getElementById('confirmArchive');
                const archiveMessage = document.getElementById('archiveMessage');
                const archiveBtn = document.getElementById('archiveConfirmBtn');

                // Validate checkbox is checked
                if (!confirmCheckbox.checked) {
                    showArchiveMessage('Please confirm that you understand this action', 'warning');
                    return;
                }

                // Get selected students
                const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
                if (selectedCheckboxes.length === 0) {
                    showArchiveMessage('Please select at least one student to archive', 'warning');
                    return;
                }

                const studentIds = Array.from(selectedCheckboxes).map(cb => cb.value);

                // Final confirmation
                if (!confirm(`Are you sure you want to archive ${studentIds.length} student(s)?\n\nThis action cannot be undone.`)) {
                    return;
                }

                // Disable button during archive
                archiveBtn.disabled = true;
                const originalHTML = archiveBtn.innerHTML;
                archiveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';

                // Hide previous messages
                archiveMessage.style.display = 'none';

                // Send archive request
                fetch('archive_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_ids: studentIds
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showArchiveMessage(data.message || `Successfully archived ${studentIds.length} student(s)`, 'success');

                            // Close modal and reload page after delay
                            setTimeout(() => {
                                closeModal('archiveModal');
                                window.location.reload();
                            }, 2000);
                        } else {
                            showArchiveMessage(data.message || 'Error archiving students. Please try again.', 'error');
                            archiveBtn.disabled = false;
                            archiveBtn.innerHTML = originalHTML;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showArchiveMessage('Network error. Please try again.', 'error');
                        archiveBtn.disabled = false;
                        archiveBtn.innerHTML = originalHTML;
                    });
            });

            // Function to show archive messages
            function showArchiveMessage(message, type) {
                const messageDiv = document.getElementById('archiveMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger');
                messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle') + ' me-2"></i>' +
                    message.replace(/\n/g, '<br>');

                // Auto-hide after 5 seconds for success, 10 for errors/warnings
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, type === 'success' ? 5000 : 10000);
            }

            // Delete user functionality
            // Use event delegation for dynamically created buttons
            document.addEventListener('click', function (e) {
                if (e.target && (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete'))) {
                    const button = e.target.classList.contains('btn-delete') ? e.target : e.target.closest('.btn-delete');
                    const userId = button.getAttribute('data-user-id');
                    const schoolId = button.getAttribute('data-school-id');

                    if (!userId || !schoolId) {
                        console.error('Missing user data');
                        return;
                    }

                    // Double confirmation for delete
                    if (confirm(`Are you sure you want to delete user "${schoolId}"?\n\nThis action cannot be undone and will permanently remove the user and all associated data.`)) {
                        if (confirm(`Final confirmation: Delete user "${schoolId}"?\n\nThis is your last chance to cancel.`)) {
                            // Disable button during deletion
                            button.disabled = true;
                            const originalHTML = button.innerHTML;
                            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                            // Send delete request
                            fetch('delete_user.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    user_id: userId
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Show success message
                                        alert(data.message || 'User deleted successfully!');
                                        // Reload page to refresh user list
                                        window.location.reload();
                                    } else {
                                        // Show error message
                                        alert(data.message || 'Error deleting user. Please try again.');
                                        button.disabled = false;
                                        button.innerHTML = originalHTML;
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Network error. Please try again.');
                                    button.disabled = false;
                                    button.innerHTML = originalHTML;
                                });
                        }
                    }
                }
            });

            // Change password functionality
            let currentUserId = null;
            let currentSchoolId = null;
            const changePasswordButtons = document.querySelectorAll('.btn-change-password');
            changePasswordButtons.forEach(button => {
                button.addEventListener('click', function () {
                    currentUserId = this.getAttribute('data-user-id');
                    currentSchoolId = this.getAttribute('data-school-id');
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmNewPassword').value = '';
                    document.getElementById('passwordMatchError').style.display = 'none';
                    openModal('changePasswordModal');
                });
            });

            // Password visibility toggle
            function togglePasswordVisibility(inputId, icon) {
                const input = document.getElementById(inputId);
                if (!input) return; // Ensure the input exists

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            // Password match validation
            const newPassword = document.getElementById('newPassword');
            const confirmNewPassword = document.getElementById('confirmNewPassword');
            const passwordMatchError = document.getElementById('passwordMatchError');
            const savePasswordBtn = document.getElementById('savePasswordBtn');

            // Add event delegation for dynamically created password toggles
            document.addEventListener('click', function (e) {
                if (e.target && e.target.classList.contains('password-toggle')) {
                    const inputId = e.target.getAttribute('data-target');
                    const input = document.getElementById(inputId);
                    if (input) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            e.target.classList.remove('fa-eye');
                            e.target.classList.add('fa-eye-slash');
                        } else {
                            input.type = 'password';
                            e.target.classList.remove('fa-eye-slash');
                            e.target.classList.add('fa-eye');
                        }
                    }
                }
            });

            function validatePasswords() {
                if (newPassword.value && confirmNewPassword.value) {
                    if (newPassword.value !== confirmNewPassword.value) {
                        passwordMatchError.style.display = 'block';
                        savePasswordBtn.disabled = true;
                        return false;
                    } else {
                        passwordMatchError.style.display = 'none';
                        savePasswordBtn.disabled = false;
                        return true;
                    }
                }
                savePasswordBtn.disabled = !(newPassword.value && confirmNewPassword.value);
                return false;
            }

            newPassword.addEventListener('input', validatePasswords);
            confirmNewPassword.addEventListener('input', validatePasswords);

            // Save new password
            savePasswordBtn.addEventListener('click', function () {
                if (!validatePasswords()) {
                    showChangePasswordMessage('Please ensure passwords match', 'error');
                    return;
                }

                if (!currentUserId) {
                    showChangePasswordMessage('User ID not found. Please try again.', 'error');
                    return;
                }

                const newPassword = document.getElementById('newPassword').value;

                // Validate password length
                if (newPassword.length < 6) {
                    showChangePasswordMessage('Password must be at least 6 characters long', 'error');
                    return;
                }

                // Disable button during update
                savePasswordBtn.disabled = true;
                savePasswordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

                // Send to server
                fetch('change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: currentUserId,
                        new_password: newPassword
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showChangePasswordMessage(data.message || 'Password updated successfully!', 'success');
                            // Reset form
                            document.getElementById('newPassword').value = '';
                            document.getElementById('confirmNewPassword').value = '';
                            document.getElementById('passwordMatchError').style.display = 'none';

                            // Close modal after delay
                            setTimeout(() => {
                                closeModal('changePasswordModal');
                            }, 2000);
                        } else {
                            showChangePasswordMessage(data.message || 'Error updating password. Please try again.', 'error');
                            savePasswordBtn.disabled = false;
                            savePasswordBtn.innerHTML = 'Save Changes';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showChangePasswordMessage('Network error. Please try again.', 'error');
                        savePasswordBtn.disabled = false;
                        savePasswordBtn.innerHTML = 'Save Changes';
                    });
            });

            // Function to show change password messages
            function showChangePasswordMessage(message, type) {
                const messageDiv = document.getElementById('changePasswordMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
                messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'times-circle') + ' me-2"></i>' +
                    message.replace(/\n/g, '<br>');

                // Auto-hide after 5 seconds for success, 10 for errors
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, type === 'success' ? 5000 : 10000);
            }
        });

        // Email functionality
        function toggleSpecificStudent() {
            const recipientType = document.getElementById('recipientType').value;
            const specificStudentContainer = document.getElementById('specificStudentContainer');
            const select = document.getElementById('specificStudent');

            if (recipientType === 'specific_student') {
                specificStudentContainer.style.display = 'block';

                // Always fetch fresh list of students (to ensure we have latest data with emails)
                select.innerHTML = '<option value="">Loading students...</option>';

                fetch('get_students.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.students && data.students.length > 0) {
                            select.innerHTML = '<option value="">Select a student</option>';
                            data.students.forEach(student => {
                                const option = document.createElement('option');
                                option.value = student.id.toString(); // Ensure it's a string
                                option.textContent = `${student.full_name} (${student.school_id})`;
                                select.appendChild(option);
                            });
                        } else {
                            select.innerHTML = '<option value="">No students with email addresses available</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        select.innerHTML = '<option value="">Error loading students</option>';
                    });
            } else {
                specificStudentContainer.style.display = 'none';
            }
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
                body: body
            };

            if (recipientType === 'specific_student') {
                // Ensure student ID is sent (convert to number if it's a numeric string)
                emailData.specificStudent = specificStudent ? (isNaN(specificStudent) ? specificStudent : parseInt(specificStudent)) : null;
            }

            // Send email
            fetch('send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(emailData)
            })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
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
                        if (data.received_data) {
                            console.error('Received data:', data.received_data);
                        }
                        showEmailMessage(errorMsg, 'error');
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showEmailMessage(error.message || 'Network error. Please try again.', 'error');
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                });
        }

        // Function to show email messages
        function showEmailMessage(message, type) {
            const messageDiv = document.getElementById('emailMessage');
            messageDiv.style.display = 'block';
            messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger');
            messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle') + ' me-2"></i>' +
                message.replace(/\n/g, '<br>');

            // Auto-hide after 5 seconds for success, 10 for errors/warnings
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, type === 'success' ? 5000 : 10000);
        }

        // Search and filter functionality - redirect to page with filters
        const searchBtn = document.getElementById('searchBtn');
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const yearFilter = document.getElementById('yearFilter');

        function applyFilters() {
            const params = new URLSearchParams();

            if (searchInput.value.trim()) {
                params.append('search', searchInput.value.trim());
            }
            if (roleFilter.value) {
                params.append('role', roleFilter.value);
            }
            if (sectionFilter.value) {
                params.append('section', sectionFilter.value);
            }
            if (yearFilter.value) {
                params.append('year', yearFilter.value);
            }

            // Reset to page 1 when filtering
            params.append('page', '1');

            window.location.href = '?' + params.toString();
        }

        // Search button click
        if (searchBtn) {
            searchBtn.addEventListener('click', applyFilters);
        }

        // Search on Enter key
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        }

        // Filter dropdowns change
        if (roleFilter) {
            roleFilter.addEventListener('change', applyFilters);
        }
        if (sectionFilter) {
            sectionFilter.addEventListener('change', applyFilters);
        }
        if (yearFilter) {
            yearFilter.addEventListener('change', applyFilters);
        }

        // Archive Students Functions
        function loadStudentsForArchive() {
            const year = document.getElementById('archiveYear').value;
            const container = document.getElementById('studentsListContainer');

            container.innerHTML = '<div style="text-align: center; padding: 2em;"><i class="fas fa-spinner fa-spin" style="font-size: 2em;"></i><br>Loading students...</div>';

            // Build URL with year filter if selected
            let url = 'get_students_for_archive.php';
            if (year) {
                url += '?year=' + encodeURIComponent(year);
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.students && data.students.length > 0) {
                        let html = '<div style="display: flex; flex-direction: column; gap: 0.5em;">';
                        data.students.forEach(student => {
                            html += `
                                <label style="display: flex; align-items: center; padding: 0.75em; border: 1px solid var(--line-clr); border-radius: 0.5em; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='var(--base-clr)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <input type="checkbox" class="student-checkbox" value="${student.id}" onchange="updateSelectedCount(); updateArchiveButtonState();" style="margin-right: 0.75em;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500;">${student.full_name}</div>
                                        <div style="font-size: 0.85em; color: var(--secondary-text-clr);">
                                            ${student.school_id} • ${student.section_name || 'N/A'} • ${student.year || 'N/A'}
                                        </div>
                                    </div>
                                </label>
                            `;
                        });
                        html += '</div>';
                        container.innerHTML = html;
                        updateSelectedCount();
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; color: var(--secondary-text-clr); padding: 2em;">
                                <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 0.5em; display: block;"></i>
                                ${year ? 'No students found for the selected year' : 'No students found'}
                            </div>
                        `;
                        updateSelectedCount();
                    }
                    updateArchiveButtonState();
                })
                .catch(error => {
                    console.error('Error loading students:', error);
                    container.innerHTML = `
                        <div style="text-align: center; color: #e74c3c; padding: 2em;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 0.5em; display: block;"></i>
                            Error loading students. Please try again.
                        </div>
                    `;
                });
        }

        function selectAllStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
            updateArchiveButtonState();
        }

        function deselectAllStudents() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
            updateArchiveButtonState();
        }

        function updateSelectedCount() {
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
            const countDiv = document.getElementById('selectedCount');
            const count = selectedCheckboxes.length;
            countDiv.textContent = `${count} student${count !== 1 ? 's' : ''} selected`;
        }

        function updateArchiveButtonState() {
            const confirmCheckbox = document.getElementById('confirmArchive');
            const archiveBtn = document.getElementById('archiveConfirmBtn');
            const selectedCheckboxes = document.querySelectorAll('.student-checkbox:checked');

            archiveBtn.disabled = !(confirmCheckbox.checked && selectedCheckboxes.length > 0);
        }
    </script>
</body>

</html>