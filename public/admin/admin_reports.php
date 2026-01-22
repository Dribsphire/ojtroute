<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - Admin Panel</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
            background: var(--hover-clr);
            border-radius: 0.5em;
            border: 1px solid var(--line-clr);
            margin-top: 1em;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table th,
        .reports-table td {
            padding: 1em;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
            vertical-align: middle;
        }

        .reports-table th {
            background-color: var(--hover-clr);
            color: var(--accent-clr);
            font-weight: 500;
        }

        .reports-table tr:last-child td {
            border-bottom: none;
        }

        .student-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-clr);
        }

        .student-name {
            font-weight: 500;
        }

        .hours-cell {
            font-weight: 600;
            color: var(--accent-clr);
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 180px;
        }

        .search-container input {
            padding: 0.5em 1em 0.5em 2.5rem;
            width: 100%;
            border: 1px solid var(--line-clr);
            border-radius: 0.5em;
            background: var(--base-clr);
            color: var(--text-clr);
            font-size: 0.95em;
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--accent-clr);
            box-shadow: 0 0 0 2px rgba(var(--accent-rgb), 0.2);
        }

        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-text-clr);
            font-size: 1em;
            pointer-events: none;
        }

        .export-btn {
            background-color: #28a745;
            color: white;
            padding: 0.5em 1em;
            border-radius: 0.5em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            border: none;
            cursor: pointer;
            font: inherit;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .export-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            padding: 1.5em 0;
            gap: 0.5em;
        }

        .pagination button {
            padding: 0.5em 1em;
            border: 1px solid var(--line-clr);
            background: var(--base-clr);
            color: var(--text-clr);
            border-radius: 0.3em;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pagination button:hover {
            background: var(--hover-clr);
        }

        .pagination button.active {
            background: var(--accent-clr);
            color: white;
            border-color: var(--accent-clr);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .student-detail {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .student-avatar-lg {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-clr);
        }

        .student-info {
            flex: 1;
        }

        .student-info h3 {
            margin-top: 0;
            color: var(--accent-clr);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 0.75rem;
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary-text-clr);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--base-clr);
            border-radius: 0.5em;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid white;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1em 1.5em;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-title {
            margin: 0;
            color: var(--accent-clr);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--text-clr);
        }

        .modal-body {
            padding: 1.5em;
        }

        .modal-footer {
            padding: 1em 1.5em;
            border-top: 1px solid var(--line-clr);
            display: flex;
            justify-content: flex-end;
            gap: 0.5em;
        }

        .btn {
            padding: 0.5em 1em;
            border-radius: 0.3em;
            border: 1px solid var(--line-clr);
            background: var(--base-clr);
            color: var(--text-clr);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: var(--hover-clr);
        }

        .btn-secondary {
            background: var(--secondary-clr);
            color: white;
        }

        /* Document Checklist Styles */
        .document-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 0.3rem;
            background: var(--hover-clr);
        }

        .document-item i {
            font-size: 1.1em;
        }

        .document-item.completed {
            color: #28a745;
        }

        .document-item.incomplete {
            color: var(--secondary-text-clr);
        }

        .document-name {
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <?php include 'admin_nav.php'; ?>

    <main>
        <div class="header-section"
            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; width: 100%;">
            <h2 style="margin: 0; white-space: nowrap;">Student OJT Reports</h2>
            <div style="display: flex; gap: 5rem; align-items: center; flex-wrap: wrap;">
                <div class="search-container">
                    <input type="text" id="searchReport" class="form-control" placeholder="Search students...">
                    <i class="fas fa-search"></i>
                </div>
                <select id="sectionFilter"
                    style="padding: 0.5em 1em; border: 1px solid var(--line-clr); border-radius: 0.5em; background: var(--base-clr); color: var(--text-clr); font-size: 0.95em; min-width: 200px;">
                    <option value="">All Sections</option>
                </select>
                <button class="export-btn" id="exportCSV" style="white-space: nowrap;">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>School ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Total OJT Hours</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2em;">
                            Loading student reports...
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <button id="prevPage" disabled>&laquo; Previous</button>
                <div id="pageNumbers"></div>
                <button id="nextPage">Next &raquo;</button>
            </div>
        </div>

        <!-- Student Profile Modal -->
        <div id="studentProfileModal" class="modal">
            <div class="modal-content" style="max-width: 700px;">
                <div class="modal-header">
                    <h3 class="modal-title">Student Profile</h3>
                    <button class="close-btn" onclick="closeModal('studentProfileModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="student-detail">
                        <img id="modalStudentAvatar" src="" alt="Student" class="student-avatar-lg">
                        <div class="student-info">
                            <h3 id="modalStudentName"></h3>
                            <div class="info-grid">
                                <div class="info-label">School ID:</div>
                                <div id="modalStudentId"></div>

                                <div class="info-label">Section:</div>
                                <div id="modalSection"></div>

                                <div class="info-label">Workplace:</div>
                                <div id="modalWorkplace"></div>

                                <div class="info-label">Company Head:</div>
                                <div id="modalCompanyHead"></div>

                                <div class="info-label">Position:</div>
                                <div id="modalPosition"></div>

                                <div class="info-label">Start Date:</div>
                                <div id="modalStartDate"></div>

                                <div class="info-label">Total OJT Hours:</div>
                                <div id="modalOjtHours"></div>
                            </div>

                            <!-- Pre-Required Documents Checklist -->
                            <h4
                                style="margin-top: 1.5rem; margin-bottom: 1rem; color: var(--accent-clr); border-bottom: 1px solid var(--line-clr); padding-bottom: 0.5rem;">
                                Pre-Required Documents</h4>
                            <div class="documents-checklist" id="documentsChecklist"
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.5rem;">
                                <!-- Documents will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('studentProfileModal')">Close</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Pagination variables
        const rowsPerPage = 10;
        let currentPage = 1;
        let totalPages = 1;
        let totalStudents = 0;
        let allStudents = [];
        let searchTerm = '';
        let sectionFilter = '';

        // DOM Elements
        const tableBody = document.getElementById('reportsTableBody');
        const pageNumbers = document.getElementById('pageNumbers');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const searchInput = document.getElementById('searchReport');
        const sectionFilterSelect = document.getElementById('sectionFilter');

        // Load student reports from API
        function loadStudentReports(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page: currentPage,
                per_page: rowsPerPage
            });

            if (searchTerm) {
                params.append('search', searchTerm);
            }

            if (sectionFilter) {
                params.append('section_id', sectionFilter);
            }

            tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2em;">Loading student reports...</td></tr>';

            fetch(`get_student_reports.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allStudents = data.students;
                        totalPages = data.total_pages;
                        totalStudents = data.total;
                        renderTable();
                        renderPagination();
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 2em; color: red;">Error: ${data.message || 'Failed to load reports'}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading reports:', error);
                    tableBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2em; color: red;">Error loading student reports. Please try again.</td></tr>';
                });
        }

        // Initialize the table
        function renderTable() {
            tableBody.innerHTML = '';

            if (allStudents.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="4" style="text-align: center; padding: 2em;">
                        No students found${searchTerm ? ' matching your search.' : '.'}
                    </td>
                `;
                tableBody.appendChild(row);
                return;
            }

            allStudents.forEach(student => {
                const row = document.createElement('tr');
                row.className = 'clickable-row';
                row.style.cursor = 'pointer';

                const avatarUrl = student.profile_pic_path ||
                    `https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=random`;
                const sectionName = student.section_name || student.section_code || 'No Section';
                const hours = parseFloat(student.total_hours || 0).toFixed(2);

                row.innerHTML = `
                    <td>${escapeHtml(student.school_id)}</td>
                    <td class="student-cell">
                        <img src="${avatarUrl}" alt="${escapeHtml(student.full_name)}" class="student-avatar" 
                             onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=random'">
                        <span class="student-name">${escapeHtml(student.full_name)}</span>
                    </td>
                    <td>${escapeHtml(sectionName)}</td>
                    <td class="hours-cell">${hours} hrs</td>
                `;

                // Add click event to show student profile
                row.addEventListener('click', () => showStudentProfile(student.user_id));

                tableBody.appendChild(row);
            });
        }

        // Render pagination buttons
        function renderPagination() {
            // Clear existing page numbers
            pageNumbers.innerHTML = '';

            // Previous button state
            prevBtn.disabled = currentPage === 1;

            // Next button state
            nextBtn.disabled = currentPage >= totalPages || totalPages === 0;

            // Page number buttons (show max 5 pages)
            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                if (i === currentPage) {
                    pageBtn.classList.add('active');
                }
                pageBtn.addEventListener('click', () => {
                    loadStudentReports(i);
                });
                pageNumbers.appendChild(pageBtn);
            }
        }

        // Show student profile modal
        function showStudentProfile(userId) {
            fetch(`get_student_profile.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.student) {
                        const student = data.student;
                        const avatarUrl = student.profile_pic_path ||
                            `https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=random`;

                        document.getElementById('modalStudentAvatar').src = avatarUrl;
                        document.getElementById('modalStudentAvatar').onerror = function () {
                            this.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=random`;
                        };
                        document.getElementById('modalStudentName').textContent = student.full_name;
                        document.getElementById('modalStudentId').textContent = student.school_id;
                        document.getElementById('modalSection').textContent = student.section_name || student.section_code || 'No Section';
                        document.getElementById('modalWorkplace').textContent = student.company_name || 'Not assigned';
                        document.getElementById('modalCompanyHead').textContent = student.company_head || 'N/A';
                        document.getElementById('modalPosition').textContent = student.position_title || 'N/A';
                        document.getElementById('modalStartDate').textContent = student.start_date ?
                            new Date(student.start_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                        document.getElementById('modalOjtHours').textContent = `${parseFloat(student.total_hours || 0).toFixed(2)} hours`;

                        // Populate document checklist
                        const documentsContainer = document.getElementById('documentsChecklist');
                        documentsContainer.innerHTML = '';

                        if (data.documents) {
                            const documentLabels = {
                                'MOA': 'Memorandum of Agreement (MOA)',
                                'Internship Agreement': 'Internship Agreement',
                                'parents consent': 'Parents Consent',
                                'Endorsement': 'Endorsement Letter',
                                'pledge of good conduct': 'Pledge of Good Conduct',
                                'resume': 'Resume',
                                'application letter': 'Application Letter',
                                'medical certificate': 'Medical Certificate',
                                'weekly report': 'Weekly Accomplishment Report'
                            };

                            for (const [key, status] of Object.entries(data.documents)) {
                                const docItem = document.createElement('div');
                                docItem.className = 'document-item ' + (status ? 'completed' : 'incomplete');

                                const icon = document.createElement('i');
                                icon.className = status ? 'fas fa-check-circle' : 'fas fa-times-circle';

                                const docName = document.createElement('span');
                                docName.className = 'document-name';
                                docName.textContent = documentLabels[key] || key;

                                // For Endorsement, show the date if available
                                if (key === 'Endorsement' && status && status !== 'done') {
                                    docName.textContent += ` (${status})`;
                                }

                                docItem.appendChild(icon);
                                docItem.appendChild(docName);
                                documentsContainer.appendChild(docItem);
                            }
                        } else {
                            documentsContainer.innerHTML = '<div style="color: var(--secondary-text-clr); font-style: italic;">No document information available</div>';
                        }

                        openModal('studentProfileModal');
                    } else {
                        alert('Student profile not found');
                    }
                })
                .catch(error => {
                    console.error('Error loading profile:', error);
                    alert('Error loading student profile');
                });
        }

        // Search functionality with debounce
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTerm = this.value.trim();

            searchTimeout = setTimeout(() => {
                loadStudentReports(1);
            }, 300);
        });

        // Section filter functionality
        sectionFilterSelect.addEventListener('change', function () {
            sectionFilter = this.value;
            loadStudentReports(1);
        });

        // Load sections for the filter dropdown
        function loadSections() {
            fetch('../admin/get_sections.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.sections) {
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.id;
                            option.textContent = section.section_name || section.section_code;
                            sectionFilterSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                });
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Re-enable scrolling
            }
        }

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        nextBtn.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredStudents.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });

        // Export to CSV functionality
        document.getElementById('exportCSV').addEventListener('click', function () {
            // Build URL with section filter if applied
            let exportUrl = 'export_reports_csv.php';
            if (sectionFilter) {
                exportUrl += '?section_id=' + sectionFilter;
            }
            window.location.href = exportUrl;
        });

        // Pagination buttons
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                loadStudentReports(currentPage - 1);
            }
        });

        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                loadStudentReports(currentPage + 1);
            }
        });

        // Initialize the table on page load
        loadSections();
        loadStudentReports(1);
    </script>
</body>

</html>