<?php
session_start();
require_once '../../app/middleware/requireInstructor.php';
require_once '../../app/services/InstructorService.php';

$instructorService = new App\Services\InstructorService();
$instructor_user_id = $_SESSION['user_id'] ?? null;
$instructor_id = $instructorService->getInstructorId($instructor_user_id);

// Mark attendance as viewed (clears notification badge)
$_SESSION['attendance_last_view'] = date('Y-m-d H:i:s');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    try {
        if ($_POST['action'] === 'get_attendance') {
            $status = $_POST['status'] ?? 'all';
            $blockType = $_POST['block_type'] ?? 'all';
            $dateFrom = $_POST['date_from'] ?? date('Y-m-01');
            $dateTo = $_POST['date_to'] ?? date('Y-m-d');
            $search = $_POST['search'] ?? '';

            // Pagination parameters
            $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
            $perPage = isset($_POST['per_page']) ? min(200, max(25, (int) $_POST['per_page'])) : 50;
            $offset = ($page - 1) * $perPage;

            // Optimized query with specific columns only (no SELECT *)
            $sql = "
                SELECT 
                    ar.id,
                    ar.student_id,
                    ar.attendance_date as date,
                    ar.block_type,
                    ar.time_in,
                    ar.time_out,
                    ar.hours,
                    ar.status,
                    ar.photo_path,
                    ar.time_in_latitude,
                    ar.time_in_longitude,
                    u.full_name,
                    u.school_id,
                    u.profile_pic_path,
                    sw.company_name as workplace,
                    s.section_code
                FROM attendance_records ar
                INNER JOIN students st ON ar.student_id = st.id
                INNER JOIN users u ON st.user_id = u.id
                INNER JOIN sections s ON u.section_id = s.id
                LEFT JOIN student_workplaces sw ON st.id = sw.student_id AND sw.is_active = 1
                WHERE s.instructor_id = :instructor_id
            ";

            $params = [':instructor_id' => $instructor_id];

            if ($status !== 'all') {
                $sql .= " AND ar.status = :status";
                $params[':status'] = $status;
            }

            if ($blockType !== 'all') {
                $sql .= " AND ar.block_type = :block_type";
                $params[':block_type'] = $blockType;
            }

            if ($dateFrom) {
                $sql .= " AND ar.attendance_date >= :date_from";
                $params[':date_from'] = $dateFrom;
            }

            if ($dateTo) {
                $sql .= " AND ar.attendance_date <= :date_to";
                $params[':date_to'] = $dateTo;
            }

            if ($search) {
                $sql .= " AND (u.full_name LIKE :search1 OR u.school_id LIKE :search2)";
                $params[':search1'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }

            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
            $countStmt = $instructorService->getDb()->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $perPage);

            // Add sorting and pagination
            $sql .= " ORDER BY ar.attendance_date DESC, ar.time_in DESC LIMIT :limit OFFSET :offset";
            $params[':limit'] = $perPage;
            $params[':offset'] = $offset;

            $stmt = $instructorService->getDb()->prepare($sql);

            // Bind pagination parameters as integers
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            // Bind other parameters
            foreach ($params as $key => $value) {
                if ($key !== ':limit' && $key !== ':offset') {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['data'] = $records;
            $response['pagination'] = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ];
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Attendance - Instructor Dashboard</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <style>
        .section {
            background: #2a2b3a;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #fff;
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .live-indicator::before {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            color: #e0e0e0;
        }

        th,
        td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #3a3b4a;
            vertical-align: middle;
        }

        th {
            background-color: #3a3b4a;
            color: #fff;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:hover {
            background-color: #343545;
        }

        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-ongoing {
            background-color: #ffc10720;
            color: #ffc107;
        }

        .status-pending {
            background-color: #17a2b820;
            color: #17a2b8;
        }

        .status-completed {
            background-color: #28a74520;
            color: #28a745;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }

        label {
            margin-bottom: 0.5rem;
            color: #a0a0a0;
            font-size: 0.9rem;
        }

        select,
        input {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #3a3b4a;
            background-color: #2a2b3a;
            color: #fff;
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .search-box input {
            min-width: 250px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-view {
            background-color: #17a2b8;
            color: white;
            padding: 0.4rem 0.8rem;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #a0a0a0;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #a0a0a0;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
            padding: 20px;
        }

        .modal-content {
            background-color: #2a2b3a;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 20px;
            color: #fff;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #ff6b6b;
        }

        .image-info {
            margin-top: 15px;
            color: #e0e0e0;
            text-align: center;
            font-size: 0.9rem;
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-profile img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.5rem 1rem;
            background: #3a3b4a;
            border-radius: 4px;
        }

        .auto-refresh input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>

<body>
    <?php include 'instructor_nav.php'; ?>

    <main>
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-check"></i>
                    Real-Time Student Attendance
                    <span class="live-indicator">LIVE</span>
                </h3>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="auto-refresh">
                        <input type="checkbox" id="autoRefresh" checked>
                        <label for="autoRefresh" style="margin: 0; cursor: pointer;">Auto-refresh (30s)</label>
                    </div>
                    <button class="btn" style="background: var(--accent-clr); color: white;" onclick="loadAttendance()">
                        <i class="fas fa-sync-alt"></i> Refresh Now
                    </button>
                </div>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <label for="status-filter">Status</label>
                    <select id="status-filter">
                        <option value="all">All Status</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date-from">From</label>
                    <input type="date" id="date-from">
                </div>

                <div class="filter-group">
                    <label for="date-to">To</label>
                    <input type="date" id="date-to">
                </div>

                <div class="filter-group">
                    <label for="block-type">Block Type</label>
                    <select id="block-type">
                        <option value="all">All Blocks</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="overtime">Overtime</option>
                    </select>
                </div>

                <div class="filter-group" style="flex: 1;">
                    <label for="search">Search</label>
                    <input type="text" id="search" placeholder="Search by name or ID...">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Section</th>
                            <th>Workplace</th>
                            <th>Date</th>
                            <th>Block Type</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <tr>
                            <td colspan="10" class="loading">
                                <i class="fas fa-spinner"></i>
                                <p>Loading attendance records...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="empty-state" style="display: none;">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No attendance records found</h3>
                    <p>There are no attendance records matching your filters.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <img id="modalImage" src="" alt="Attendance Image"
                style="width: 100%; max-height: 60vh; object-fit: contain; display: block; margin: 0 auto;">
            <div class="image-info">
                <p id="imageDetails"></p>
            </div>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Set default date range to current month
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            document.getElementById('date-from').valueAsDate = firstDay;
            document.getElementById('date-to').valueAsDate = today;

            // Load initial data
            loadAttendance();

            // Setup auto-refresh
            setupAutoRefresh();

            // Add filter event listeners
            ['status-filter', 'block-type', 'date-from', 'date-to', 'search'].forEach(id => {
                const element = document.getElementById(id);
                if (element.type === 'text') {
                    let timeout;
                    element.addEventListener('input', () => {
                        clearTimeout(timeout);
                        timeout = setTimeout(loadAttendance, 500);
                    });
                } else {
                    element.addEventListener('change', loadAttendance);
                }
            });

            // Modal setup
            const modal = document.getElementById('imageModal');
            const closeBtn = document.querySelector('.close-modal');

            closeBtn.onclick = () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            };

            window.onclick = (event) => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            };

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });

        function setupAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');

            if (checkbox.checked) {
                autoRefreshInterval = setInterval(loadAttendance, 30000); // 30 seconds
            }

            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    autoRefreshInterval = setInterval(loadAttendance, 30000);
                } else {
                    clearInterval(autoRefreshInterval);
                }
            });
        }

        function loadAttendance() {
            const formData = new FormData();
            formData.append('action', 'get_attendance');
            formData.append('status', document.getElementById('status-filter').value);
            formData.append('block_type', document.getElementById('block-type').value);
            formData.append('date_from', document.getElementById('date-from').value);
            formData.append('date_to', document.getElementById('date-to').value);
            formData.append('search', document.getElementById('search').value);

            fetch('attendance.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAttendanceTable(data.data);
                    } else {
                        console.error('Error loading attendance:', data.error);
                        showError(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load attendance records');
                });
        }

        // Format time from 24-hour (military) to 12-hour AM/PM format
        function formatTime(timeStr) {
            if (!timeStr) return '-';

            // Handle both "HH:MM:SS" and "YYYY-MM-DD HH:MM:SS" formats
            const timePart = timeStr.includes(' ') ? timeStr.split(' ')[1] : timeStr;
            const [hours, minutes] = timePart.split(':');

            let hour = parseInt(hours, 10);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12; // Convert 0 to 12 for midnight

            return `${hour}:${minutes} ${ampm}`;
        }

        function renderAttendanceTable(records) {
            const tbody = document.getElementById('attendanceTableBody');
            const emptyState = document.querySelector('.empty-state');

            if (records.length === 0) {
                tbody.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';

            tbody.innerHTML = records.map(record => {
                const profilePic = record.profile_pic_path || '../../storage/images/default_profile.jpg';
                const timeIn = formatTime(record.time_in);
                const timeOut = record.time_out ? formatTime(record.time_out) : '-';
                const hours = record.hours ? parseFloat(record.hours).toFixed(2) : '-';
                const statusClass = `status-${record.status}`;
                const workplace = record.workplace || 'Not assigned';

                return `
                    <tr>
                        <td>
                            <div class="student-profile">
                                <img src="${profilePic}" alt="${record.full_name}">
                                <div>
                                    <div>${record.full_name}</div>
                                    <small style="color: #a0a0a0;">${record.school_id}</small>
                                </div>
                            </div>
                        </td>
                        <td>${record.section_code}</td>
                        <td>${workplace}</td>
                        <td>${record.date}</td>
                        <td style="text-transform: capitalize;">${record.block_type}</td>
                        <td>${timeIn}</td>
                        <td>${timeOut}</td>
                        <td>${hours}</td>
                        <td><span class="status ${statusClass}">${record.status}</span></td>
                        <td>
                            <button class="btn btn-view" onclick='viewImage(${JSON.stringify(record)})'>
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function viewImage(record) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalDetails = document.getElementById('imageDetails');

            const imagePath = record.photo_path || '../images/no-image.png';
            const timeIn = formatTime(record.time_in);
            const timeOut = record.time_out ? formatTime(record.time_out) : 'Still ongoing';

            modal.style.display = 'block';
            modalImg.src = imagePath;
            modalDetails.innerHTML = `
                <strong>${record.full_name}</strong> (${record.school_id})<br>
                ${record.date} • ${record.block_type}<br>
                Time In: ${timeIn} | Time Out: ${timeOut}<br>
                Status: ${record.status}
            `;
            document.body.style.overflow = 'hidden';
        }

        function showError(message) {
            const tbody = document.getElementById('attendanceTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align: center; color: #dc3545; padding: 2rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>${message}</p>
                    </td>
                </tr>
            `;
        }
    </script>
</body>

</html>