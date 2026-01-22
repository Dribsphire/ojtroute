<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/SectionService.php';

use App\Services\SectionService;

$sectionService = new SectionService();
$sections = $sectionService->getSections();
$instructors = $sectionService->getInstructors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management - Admin Panel</title>
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
        
        .section-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .section-table th, 
        .section-table td {
            padding: 0.75em 1em;
            text-align: left;
            border-bottom: 1px solid var(--line-clr);
            vertical-align: middle;
        }
        
        .section-table th {
            background-color: var(--hover-clr);
            color: var(--accent-clr);
            font-weight: 500;
        }
        
        .section-table tr:last-child td {
            border-bottom: none;
        }
        
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .clickable-row:hover {
            background-color: var(--hover-clr);
        }
        
        .btn {
            padding: 0.5em 1em;
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5em;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid var(--line-clr);
            background-color: var(--base-clr);
            color: var(--text-clr);
        }
        
        .btn-primary {
            background-color: var(--accent-clr);
            color: white;
            border: none;
            padding:12px;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background-color: var(--secondary-text-clr);
            color: white;
            border: none;
        }
        
        .btn i {
            font-size: 0.9em;
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
            padding-bottom: 0.75em;
            border-bottom: 1px solid var(--line-clr);
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
        
        .action-buttons {
            gap: 0.5em;
            justify-content: flex-end;
            padding: 0.5em 0;
        }
        
        .no-data {
            text-align: center;
            padding: 2em;
            color: var(--secondary-text-clr);
        }
        
        .instructor-profile {
            display: flex;
            gap: 2rem;
            padding: 1.5rem;
        }
        
        .instructor-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-clr);
        }
        
        .instructor-details {
            flex: 1;
        }
        
        .instructor-details h3 {
            margin-top: 0;
            color: var(--accent-clr);
            border-bottom: 1px solid var(--line-clr);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
            align-items: center;
        }
        
        .detail-label {
            font-weight: 500;
            width: 150px;
            color: var(--secondary-text-clr);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .instructor-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .instructor-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-clr);
        }
        
        .instructor-name {
            flex: 1;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5em;
            margin-bottom: 1rem;
            display: none;
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

        .alert-warning {
            background-color: rgba(255, 165, 0, 0.2);
            color: #ffa500;
            border: 1px solid #ffa500;
        }
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>
    
    <main>
        
            <div class="header-section">
                <h2>Section Management</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="search-container" style="position: relative;">
                        <input type="text" id="searchSection" class="form-control" placeholder="Search sections, instructors..." style="padding-left: 2.5rem; width: 250px;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--secondary-text-clr);"></i>
                    </div>
                    <button id="addSectionBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Section
                    </button>
                    <button id="archiveSectionsBtn" class="btn btn-primary">
                        <i class="fas fa-box-archive"></i> Archive Sections
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="section-table">
                    <thead>
                        <tr>
                            <th>Section Code</th>
                            <th>Section Name</th>
                            <th>Instructor</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sectionsTableBody">
                        <?php if (empty($sections)): ?>
                            <tr>
                                <td colspan="5" class="no-data">No sections found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sections as $section): ?>
                                <tr class="clickable-row" data-section-id="<?php echo htmlspecialchars($section['id']); ?>" 
                                    onclick="showInstructorProfileBySection(<?php echo htmlspecialchars($section['id']); ?>)">
                                    <td><?php echo htmlspecialchars($section['section_code']); ?></td>
                                    <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                                    <td>
                                        <?php if ($section['instructor_name']): ?>
                                            <div class="instructor-cell">
                                                <img src="<?php echo htmlspecialchars($section['instructor_avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($section['instructor_name']) . '&background=random'); ?>" 
                                                     alt="<?php echo htmlspecialchars($section['instructor_name']); ?>" 
                                                     class="instructor-avatar-sm"
                                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($section['instructor_name']); ?>&background=random'">
                                                <span class="instructor-name"><?php echo htmlspecialchars($section['instructor_name']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($section['student_count']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn" onclick="event.stopPropagation(); openAssignInstructorModal(<?php echo htmlspecialchars($section['id']); ?>, '<?php echo htmlspecialchars($section['section_code']); ?>', '<?php echo htmlspecialchars($section['section_name']); ?>')">
                                                <i class="fas fa-user-tie"></i> Assign
                                            </button>
                                            <button class="btn btn-danger" onclick="event.stopPropagation(); confirmDelete(<?php echo htmlspecialchars($section['id']); ?>, '<?php echo htmlspecialchars($section['section_code']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        
    </main>

    <!-- Assign Instructor Modal -->
    <div id="assignInstructorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Assign Instructor</h3>
                <button class="close-btn" onclick="closeModal('assignInstructorModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="assignInstructorMessage" style="display: none; margin-bottom: 1rem;"></div>
                <div class="form-group">
                    <label for="sectionInfo">Section:</label>
                    <input type="text" id="sectionInfo" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="instructor">Select Instructor:</label>
                    <select id="instructor" class="form-control">
                        <option value="">-- Select Instructor --</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo htmlspecialchars($instructor['instructor_id']); ?>">
                                <?php echo htmlspecialchars($instructor['full_name'] . ' (' . $instructor['school_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('assignInstructorModal')">Cancel</button>
                    <button type="button" id="assignInstructorBtn" class="btn btn-primary" onclick="assignInstructor()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="close-btn" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete section <strong id="sectionToDelete"></strong>? This action cannot be undone.</p>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="deleteSection()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div id="addSectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Section</h3>
                <button class="close-btn" onclick="closeModal('addSectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="addSectionMessage" style="display: none; margin-bottom: 1rem;"></div>
                <div class="form-group">
                    <label for="sectionCode">Section Code:</label>
                    <input type="text" id="sectionCode" class="form-control" placeholder="e.g., 4A">
                </div>
                <div class="form-group">
                    <label for="sectionName">Section Name:</label>
                    <input type="text" id="sectionName" class="form-control" placeholder="e.g., BSIT-4A">
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        <option value="College of Computer Studies">College of Computer Studies</option>
                        <option value="College of Education">College of Education</option>
                        <option value="College of Engineering">College of Engineering</option>
                        <option value="College of Industrial Technology">College of Industrial Technology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Academic Year:</label>
                    <input type="text" id="year" class="form-control" placeholder="e.g., 2025" value="2025" required>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSectionModal')">Cancel</button>
                    <button type="button" id="addSectionBtnModal" class="btn btn-primary" onclick="addSection()">
                        <i class="fas fa-plus"></i> Add Section
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructor Profile Modal -->
    <div id="instructorProfileModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 class="modal-title">Instructor Profile</h3>
                <button class="close-btn" onclick="closeModal('instructorProfileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="instructor-profile">
                    <img id="instructorAvatar" src="" alt="Instructor" class="instructor-avatar">
                    <div class="instructor-details">
                        <h3 id="instructorName"></h3>
                        <div class="detail-row">
                            <span class="detail-label">School ID:</span>
                            <span class="detail-value" id="instructorId"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Assigned Section:</span>
                            <span class="detail-value" id="assignedSection"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Students:</span>
                            <span class="detail-value" id="studentCount"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact Number:</span>
                            <span class="detail-value" id="contactNumber"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('instructorProfileModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Show Instructor Profile by Section ID
        function showInstructorProfileBySection(sectionId) {
            fetch(`get_instructor_profile.php?section_id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.instructor) {
                        const instructor = data.instructor;
                        document.getElementById('instructorName').textContent = instructor.full_name;
                        document.getElementById('instructorId').textContent = instructor.school_id;
                        document.getElementById('assignedSection').textContent = `${instructor.section_code} - ${instructor.section_name}`;
                        document.getElementById('studentCount').textContent = instructor.student_count || 0;
                        document.getElementById('contactNumber').textContent = instructor.contact || 'Not provided';
                        
                        // Set avatar
                        const avatar = document.getElementById('instructorAvatar');
                        if (instructor.profile_pic_path) {
                            avatar.src = instructor.profile_pic_path;
                            avatar.onerror = function() {
                                this.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(instructor.full_name) + '&background=random';
                            };
                        } else {
                            avatar.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(instructor.full_name) + '&background=random';
                        }
                        
                        openModal('instructorProfileModal');
                    } else {
                        alert('No instructor assigned to this section');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading instructor profile');
                });
        }
        
        // Search Functionality
        let searchTimeout;
        document.getElementById('searchSection').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // Debounce search
            searchTimeout = setTimeout(() => {
                if (searchTerm.length === 0) {
                    // Reload all sections
                    loadSections();
                } else {
                    // Search with API
                    fetch(`get_sections.php?search=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                renderSections(data.sections);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            }, 300);
        });
        
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Reset modals
            if (modalId === 'assignInstructorModal') {
                document.getElementById('instructor').value = '';
                document.getElementById('assignInstructorMessage').style.display = 'none';
                document.getElementById('assignInstructorBtn').disabled = false;
                document.getElementById('assignInstructorBtn').innerHTML = 'Save Changes';
                currentSectionId = null;
            } else if (modalId === 'addSectionModal') {
                document.getElementById('sectionCode').value = '';
                document.getElementById('sectionName').value = '';
                document.getElementById('department').value = '';
                document.getElementById('year').value = '2025';
                document.getElementById('addSectionMessage').style.display = 'none';
                document.getElementById('addSectionBtnModal').disabled = false;
                document.getElementById('addSectionBtnModal').innerHTML = '<i class="fas fa-plus"></i> Add Section';
            } else if (modalId === 'deleteModal') {
                deleteSectionId = null;
            }
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Assign Instructor Functions
        let currentSectionId = null;
        let currentSectionCode = '';
        let currentSectionName = '';
        
        function openAssignInstructorModal(sectionId, sectionCode, sectionName) {
            currentSectionId = sectionId;
            currentSectionCode = sectionCode;
            currentSectionName = sectionName;
            document.getElementById('sectionInfo').value = `${sectionCode} - ${sectionName}`;
            document.getElementById('assignInstructorMessage').style.display = 'none';
            
            // Get current section info to pre-select instructor
            fetch(`get_sections.php`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.sections) {
                        const section = data.sections.find(s => s.id == sectionId);
                        const currentInstructorId = section ? section.instructor_table_id : null;
                        
                        // Reload instructors list and pre-select current instructor
                        loadInstructors(currentInstructorId);
                    } else {
                        loadInstructors();
                    }
                })
                .catch(error => {
                    console.error('Error loading section:', error);
                    loadInstructors();
                });
            
            openModal('assignInstructorModal');
        }
        
        function loadInstructors(currentInstructorId = null) {
            fetch('get_instructors.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.instructors) {
                        const select = document.getElementById('instructor');
                        select.innerHTML = '<option value="">-- Select Instructor --</option>';
                        data.instructors.forEach(instructor => {
                            const option = document.createElement('option');
                            option.value = instructor.instructor_id;
                            option.textContent = `${instructor.full_name} (${instructor.school_id})`;
                            if (currentInstructorId && instructor.instructor_id == currentInstructorId) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading instructors:', error);
                });
        }
        
        function assignInstructor() {
            const instructorSelect = document.getElementById('instructor');
            const instructorId = instructorSelect.value;
            const assignBtn = document.getElementById('assignInstructorBtn');
            const messageDiv = document.getElementById('assignInstructorMessage');
            
            if (!currentSectionId) {
                showMessage('assignInstructorMessage', 'Section ID not found', 'error');
                return;
            }
            
            // Disable button during request
            assignBtn.disabled = true;
            assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            messageDiv.style.display = 'none';
            
            fetch('assign_instructor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    section_id: currentSectionId,
                    instructor_id: instructorId || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('assignInstructorMessage', data.message || 'Instructor assigned successfully!', 'success');
                    setTimeout(() => {
                        closeModal('assignInstructorModal');
                        loadSections();
                    }, 1500);
                } else {
                    showMessage('assignInstructorMessage', data.message || 'Error assigning instructor', 'error');
                    assignBtn.disabled = false;
                    assignBtn.innerHTML = 'Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('assignInstructorMessage', 'Network error. Please try again.', 'error');
                assignBtn.disabled = false;
                assignBtn.innerHTML = 'Save Changes';
            });
        }
        
        // Delete Section Functions
        let deleteSectionId = null;
        
        function confirmDelete(sectionId, sectionCode) {
            deleteSectionId = sectionId;
            document.getElementById('sectionToDelete').textContent = sectionCode;
            openModal('deleteModal');
        }
        
        function deleteSection() {
            if (!deleteSectionId) {
                alert('Section ID not found');
                return;
            }
            
            fetch('delete_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    section_id: deleteSectionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('deleteModal');
                    alert(data.message || 'Section deleted successfully!');
                    loadSections();
                } else {
                    alert(data.message || 'Error deleting section');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        }
        
        // Add Section Functions
        document.getElementById('addSectionBtn').addEventListener('click', function() {
            document.getElementById('sectionCode').value = '';
            document.getElementById('sectionName').value = '';
            document.getElementById('department').value = '';
            document.getElementById('year').value = '2025';
            document.getElementById('addSectionMessage').style.display = 'none';
            openModal('addSectionModal');
        });
        
        function addSection() {
            const sectionCode = document.getElementById('sectionCode').value.trim();
            const sectionName = document.getElementById('sectionName').value.trim();
            const department = document.getElementById('department').value.trim();
            const year = document.getElementById('year').value.trim();
            const addBtn = document.getElementById('addSectionBtnModal');
            const messageDiv = document.getElementById('addSectionMessage');
            
            if (!sectionCode || !sectionName || !department || !year) {
                showMessage('addSectionMessage', 'Please fill in all fields', 'error');
                return;
            }
            
            // Disable button during request
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            messageDiv.style.display = 'none';
            
            fetch('add_section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    section_code: sectionCode,
                    section_name: sectionName,
                    department: department,
                    year: year
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('addSectionMessage', data.message || 'Section added successfully!', 'success');
                    // Reset form
                    document.getElementById('sectionCode').value = '';
                    document.getElementById('sectionName').value = '';
                    document.getElementById('department').value = '';
                    document.getElementById('year').value = '2025';
                    
                    setTimeout(() => {
                        closeModal('addSectionModal');
                        loadSections();
                    }, 1500);
                } else {
                    showMessage('addSectionMessage', data.message || 'Error adding section', 'error');
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Section';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('addSectionMessage', 'Network error. Please try again.', 'error');
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Section';
            });
        }
        
        // Load sections from API
        function loadSections() {
            fetch('get_sections.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderSections(data.sections);
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                });
        }
        
        // Render sections table
        function renderSections(sections) {
            const tbody = document.getElementById('sectionsTableBody');
            
            if (sections.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="no-data">No sections found</td></tr>';
                return;
            }
            
            tbody.innerHTML = sections.map(section => {
                const instructorCell = section.instructor_name ? `
                    <div class="instructor-cell">
                        <img src="${section.instructor_avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(section.instructor_name) + '&background=random'}" 
                             alt="${escapeHtml(section.instructor_name)}" 
                             class="instructor-avatar-sm"
                             onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(section.instructor_name)}&background=random'">
                        <span class="instructor-name">${escapeHtml(section.instructor_name)}</span>
                    </div>
                ` : '<span>-</span>';
                
                return `
                    <tr class="clickable-row" data-section-id="${section.id}" 
                        onclick="showInstructorProfileBySection(${section.id})">
                        <td>${escapeHtml(section.section_code)}</td>
                        <td>${escapeHtml(section.section_name)}</td>
                        <td>${instructorCell}</td>
                        <td>${section.student_count}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn" onclick="event.stopPropagation(); openAssignInstructorModal(${section.id}, '${escapeHtml(section.section_code)}', '${escapeHtml(section.section_name)}')">
                                    <i class="fas fa-user-tie"></i> Assign
                                </button>
                                <button class="btn btn-danger" onclick="event.stopPropagation(); confirmDelete(${section.id}, '${escapeHtml(section.section_code)}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Helper function to show messages
        function showMessage(elementId, message, type) {
            const messageDiv = document.getElementById(elementId);
            messageDiv.style.display = 'block';
            messageDiv.className = `alert alert-${type}`;
            messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle'} me-2"></i>${escapeHtml(message)}`;
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, type === 'success' ? 3000 : 5000);
        }
    </script>
</body>
</html>