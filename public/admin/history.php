<?php
// Require admin authentication
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/UserService.php';

use App\Services\UserService;

$userService = new UserService();

// Get filter parameters
$yearFilter = isset($_GET['year']) ? trim($_GET['year']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20; // Show 20 students per page

// Get archived students with pagination
$result = $userService->getArchivedStudents($yearFilter, $search, $page, $perPage);
$archivedStudents = $result['students'];
$totalRecords = $result['total'];
$totalPages = $result['total_pages'];

// Group students by year
$studentsByYear = [];
foreach ($archivedStudents as $student) {
    $year = $student['year'] ?? 'Unknown';
    if (!isset($studentsByYear[$year])) {
        $studentsByYear[$year] = [];
    }
    $studentsByYear[$year][] = $student;
}

// Sort years in descending order
krsort($studentsByYear);

// Get all years for filter
$years = $userService->getArchivedYears();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive History - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .archive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2em;
        }

        .archive-header h2 {
            margin: 0;
            color: var(--text-clr);
        }

        .filter-section {
            display: flex;
            gap: 3em;
            margin-bottom: 2em;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5em;
            color: var(--text-clr);
            font-weight: 500;
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

        .year-section {
            margin-bottom: 2em;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            overflow: hidden;
        }

        .year-header {
            background-color: var(--hover-clr);
            padding: 1em 1.5em;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .year-header:hover {
            background-color: var(--accent-clr);
            color: white;
        }

        .year-header h3 {
            margin: 0;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.5em;
        }

        .year-badge {
            background-color: var(--accent-clr);
            color: white;
            padding: 0.25em 0.75em;
            border-radius: 1em;
            font-size: 0.9em;
            font-weight: 500;
        }

        .year-header:hover .year-badge {
            background-color: white;
            color: var(--accent-clr);
        }

        .students-table {
            width: 100%;
            border-collapse: collapse;
        }

        .students-table th,
        .students-table td {
            padding: 1em;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
        }

        .students-table th {
            background-color: var(--base-clr);
            color: var(--accent-clr);
            font-weight: 500;
        }

        .students-table tr:last-child td {
            border-bottom: none;
        }

        .students-table tbody tr:hover {
            background-color: var(--hover-clr);
        }

        .btn {
            padding: 0.5em 1em;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            transition: all 0.3s ease;
            background-color: var(--base-clr);
            color: var(--text-clr);
            text-decoration: none;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-restore {
            border-color: #2ecc71;
            color: #2ecc71;
        }

        .btn-primary {
            background-color: var(--accent-clr);
            color: white;
            border: none;
        }

        .empty-state {
            text-align: center;
            padding: 4em 2em;
            color: var(--secondary-text-clr);
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 1em;
            opacity: 0.5;
        }

        .archive-date {
            color: var(--secondary-text-clr);
            font-size: 0.9em;
        }

        .stats-container {
            display: flex;
            gap: 1em;
            margin-bottom: 2em;
        }

        .stat-card {
            flex: 1;
            padding: 1.5em;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            background-color: var(--hover-clr);
        }

        .stat-card h4 {
            margin: 0 0 0.5em 0;
            color: var(--secondary-text-clr);
            font-size: 0.9em;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 2em;
            font-weight: 600;
            color: var(--accent-clr);
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
            max-width: 500px;
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

        .btn-group {
            display: flex;
            gap: 1em;
            margin-top: 1.5em;
            justify-content: flex-end;
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-clr);
            border: 1px solid var(--line-clr);
        }

        .btn-secondary:hover {
            background-color: var(--hover-clr);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: rgba(26, 210, 28, 0.2);
            color: #1ad21c;
            border: 1px solid #1ad21c;
        }

        .alert-danger {
            background-color: rgba(255, 77, 77, 0.2);
            color: #ff4d4d;
            border: 1px solid #ff4d4d;
        }
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
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>
    <main>
        
            <div class="archive-header">
                <h2><i class="fas fa-history"></i> Archive History</h2>
                <a href="admin_userpage.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to User Management
                </a>
            </div>

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <h4>Total Archived Students</h4>
                    <div class="stat-value"><?php echo $totalRecords; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Showing on This Page</h4>
                    <div class="stat-value"><?php echo count($archivedStudents); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Academic Years</h4>
                    <div class="stat-value"><?php echo count($studentsByYear); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <div class="filter-group">
                    <label for="yearFilter">Filter by Year</label>
                    <select id="yearFilter" class="form-control" onchange="applyFilters()">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year['year']); ?>" 
                                        <?php echo ($yearFilter === $year['year']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year['year']); ?>
                                    </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="searchInput">Search Students</label>
                    <input type="text" id="searchInput" class="form-control" 
                           placeholder="Search by name or school ID..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeypress="if(event.key === 'Enter') applyFilters()">
                </div>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button class="btn btn-primary" onclick="applyFilters()" style="width: 100%;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <!-- Archived Students by Year -->
            <?php if (empty($studentsByYear)): ?>
                        <div class="empty-state">
                            <i class="fas fa-archive"></i>
                            <h3>No Archived Students Found</h3>
                            <p>There are no archived students matching your criteria.</p>
                        </div>
            <?php else: ?>
                        <?php foreach ($studentsByYear as $year => $students): ?>
                                    <div class="year-section">
                                        <div class="year-header" onclick="toggleYear('year-<?php echo htmlspecialchars($year); ?>')">
                                            <h3>
                                                <i class="fas fa-calendar-alt"></i>
                                                Academic Year <?php echo htmlspecialchars($year); ?>
                                                <span class="year-badge"><?php echo count($students); ?> student<?php echo count($students) !== 1 ? 's' : ''; ?></span>
                                            </h3>
                                            <i class="fas fa-chevron-down" id="icon-year-<?php echo htmlspecialchars($year); ?>"></i>
                                        </div>
                                        <div id="year-<?php echo htmlspecialchars($year); ?>" style="display: block;">
                                            <table class="students-table">
                                                <thead>
                                                    <tr>
                                                        <th>School ID</th>
                                                        <th>Full Name</th>
                                                        <th>Section</th>
                                                        <th>Archived Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($students as $student): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($student['school_id']); ?></td>
                                                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($student['section_name'] ?? 'N/A'); ?></td>
                                                                    <td class="archive-date">
                                                                        <?php
                                                                        if ($student['archived_at']) {
                                                                            echo date('M d, Y h:i A', strtotime($student['archived_at']));
                                                                        } else {
                                                                            echo 'N/A';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <button class="btn btn-restore" 
                                                                                onclick="restoreStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['school_id']); ?>')">
                                                                            <i class="fas fa-undo"></i> Restore
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                        <?php endforeach; ?>
                    
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                                <div class="pagination" style="display: flex; justify-content: center; align-items: center; margin-top: 2em; gap: 5px; flex-wrap: wrap;">
                                    <?php
                                    // Build query string for pagination links
                                    $queryParams = [];
                                    if (!empty($yearFilter))
                                        $queryParams['year'] = $yearFilter;
                                    if (!empty($search))
                                        $queryParams['search'] = $search;
                                    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                                    ?>
                            
                                    <?php if ($page > 1): ?>
                                            <a href="?page=1<?php echo $queryString; ?>" class="pagination-link" title="First Page"><i class="fas fa-angle-double-left"></i></a>
                                            <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="pagination-link" title="Previous"><i class="fas fa-angle-left"></i></a>
                                    <?php else: ?>
                                            <span class="pagination-link disabled"><i class="fas fa-angle-double-left"></i></span>
                                            <span class="pagination-link disabled"><i class="fas fa-angle-left"></i></span>
                                    <?php endif; ?>
                            
                                    <?php
                                    // Show page numbers
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($totalPages, $page + 2);

                                    if ($start_page > 1) {
                                        echo '<a href="?page=1' . $queryString . '" class="pagination-link">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="pagination-ellipsis">...</span>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                    <?php endfor;

                                    if ($end_page < $totalPages) {
                                        if ($end_page < $totalPages - 1) {
                                            echo '<span class="pagination-ellipsis">...</span>';
                                        }
                                        echo '<a href="?page=' . $totalPages . $queryString . '" class="pagination-link">' . $totalPages . '</a>';
                                    }
                                    ?>
                            
                                    <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="pagination-link" title="Next"><i class="fas fa-angle-right"></i></a>
                                            <a href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" class="pagination-link" title="Last Page"><i class="fas fa-angle-double-right"></i></a>
                                    <?php else: ?>
                                            <span class="pagination-link disabled"><i class="fas fa-angle-right"></i></span>
                                            <span class="pagination-link disabled"><i class="fas fa-angle-double-right"></i></span>
                                    <?php endif; ?>
                                </div>
                        <?php endif; ?>
            <?php endif; ?>
        
    </main>

    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Restore Student</h3>
                <button class="close-btn" onclick="closeModal('restoreModal')">&times;</button>
            </div>
            <div id="restoreMessage" style="display: none;"></div>
            <p>Are you sure you want to restore this student?</p>
            <p><strong>School ID: <span id="restoreSchoolId"></span></strong></p>
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="closeModal('restoreModal')">Cancel</button>
                <button class="btn btn-primary" id="confirmRestoreBtn">Restore Student</button>
            </div>
        </div>
    </div>

    <script>
        let currentStudentId = null;

        function toggleYear(yearId) {
            const content = document.getElementById(yearId);
            const icon = document.getElementById('icon-' + yearId);
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                content.style.display = 'none';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }

        function applyFilters() {
            const year = document.getElementById('yearFilter').value;
            const search = document.getElementById('searchInput').value;
            
            const params = new URLSearchParams();
            if (year) params.append('year', year);
            if (search) params.append('search', search);
            
            window.location.href = '?' + params.toString();
        }

        function restoreStudent(studentId, schoolId) {
            currentStudentId = studentId;
            document.getElementById('restoreSchoolId').textContent = schoolId;
            openModal('restoreModal');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('restoreMessage').style.display = 'none';
            currentStudentId = null;
        }

        // Confirm restore
        document.getElementById('confirmRestoreBtn').addEventListener('click', function() {
            if (!currentStudentId) return;

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';

            fetch('restore_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: currentStudentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Student restored successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Error restoring student. Please try again.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Restore Student';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Restore Student';
            });
        });

        function showMessage(message, type) {
            const messageDiv = document.getElementById('restoreMessage');
            messageDiv.style.display = 'block';
            messageDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
            messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'times-circle') + '"></i> ' + message;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>

</html>