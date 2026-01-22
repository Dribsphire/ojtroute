<?php
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebar">
    <ul>
        <li>
            <div style="width: 80px; height: 80px; display:flex; align-items:center; justify-content:center;">
                <img src="../../public/images/CHMSU.png" alt="CHMSU logo" width="60" height="60" loading="lazy">
            </div>
            <span class="logo" style="margin-right: -1rem;">OJTRoute System</span>
        </li>
        <li <?= ($current_page == 'profile.php') ? 'class="active"' : '' ?>>
            <a href="profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" />
                </svg>
                <span>Profile</span>
            </a>
        </li>
        <li <?= ($current_page == 'student_list.php') ? 'class="active"' : '' ?>>
            <a href="student_list.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M411-480q-28 0-46-21t-13-49l12-72q8-43 40.5-70.5T480-720q44 0 76.5 27.5T597-622l12 72q5 28-13 49t-46 21H411Zm24-80h91l-8-49q-2-14-13-22.5t-25-8.5q-14 0-24.5 8.5T443-609l-8 49ZM124-441q-23 1-39.5-9T63-481q-2-9-1-18t5-17q0 1-1-4-2-2-10-24-2-12 3-23t13-19l2-2q2-19 15.5-32t33.5-13q3 0 19 4l3-1q5-5 13-7.5t17-2.5q11 0 19.5 3.5T208-626q1 0 1.5.5t1.5.5q14 1 24.5 8.5T251-596q2 7 1.5 13.5T250-570q0 1 1 4 7 7 11 15.5t4 17.5q0 4-6 21-1 2 0 4l2 16q0 21-17.5 36T202-441h-78Zm676 1q-33 0-56.5-23.5T720-520q0-12 3.5-22.5T733-563l-28-25q-10-8-3.5-20t18.5-12h80q33 0 56.5 23.5T880-540v20q0 33-23.5 56.5T800-440ZM0-240v-63q0-44 44.5-70.5T160-400q13 0 25 .5t23 2.5q-14 20-21 43t-7 49v65H0Zm240 0v-65q0-65 66.5-105T480-450q108 0 174 40t66 105v65H240Zm560-160q72 0 116 26.5t44 70.5v63H780v-65q0-26-6.5-49T754-397q11-2 22.5-2.5t23.5-.5Zm-320 30q-57 0-102 15t-53 35h311q-9-20-53.5-35T480-370Zm0 50Zm1-280Z" />
                </svg>
                <span>Student List</span>
            </a>
        </li>
        <li <?= ($current_page == 'attendance.php') ? 'class="active"' : '' ?>>
            <a href="attendance.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M80-320v-112q0-34 17.5-62.5T144-538q62-31 126-46.5T400-600q45 0 89 7t88 22q-17 14-31 30.5T521-505q-30-8-60-11.5t-61-3.5q-56 0-111 13.5T180-466q-9 5-14.5 14t-5.5 20v32h323q-2 20-2 40t2 40H80Zm320-80Zm0-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm0-80q33 0 56.5-23.5T480-800q0-33-23.5-56.5T400-880q-33 0-56.5 23.5T320-800q0 33 23.5 56.5T400-720Zm0-80Zm400 0q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T584-952q14-5 28-6.5t28-1.5q66 0 113 47t47 113Zm-40 640q-83 0-141.5-58.5T560-360q0-84 58.5-142T760-560q84 0 142 58t58 142q0 83-58 141.5T760-160Zm-28-110 141-142-28-28-113 113-57-57-28 29 85 85Z" />
                </svg>
                <span>Attendance <span id="attendanceBadge" class="nav-badge" style="display:none;">0</span></span>
            </a>
        </li>
        <li <?= ($current_page == 'documents_reports.php') ? 'class="active"' : '' ?>>
            <a href="documents_reports.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e8eaed">
                    <path
                        d="M520-640v-160q0-17 11.5-28.5T560-840h240q17 0 28.5 11.5T840-800v160q0 17-11.5 28.5T800-600H560q-17 0-28.5-11.5T520-640ZM120-480v-320q0-17 11.5-28.5T160-840h240q17 0 28.5 11.5T440-800v320q0 17-11.5 28.5T400-440H160q-17 0-28.5-11.5T120-480Zm400 320v-320q0-17 11.5-28.5T560-520h240q17 0 28.5 11.5T840-480v320q0 17-11.5 28.5T800-120H560q-17 0-28.5-11.5T520-160Zm-400 0v-160q0-17 11.5-28.5T160-360h240q17 0 28.5 11.5T440-320v160q0 17-11.5 28.5T400-120H160q-17 0-28.5-11.5T120-160Zm80-360h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                </svg>
                <span>Reports</span>
            </a>
        </li>
        <li>
            <button onclick=toggleSubMenu(this) class="dropdown-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e8eaed">
                    <path
                        d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h207q16 0 30.5 6t25.5 17l57 57h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Zm400-160v40q0 17 11.5 28.5T600-320q17 0 28.5-11.5T640-360v-40h40q17 0 28.5-11.5T720-440q0-17-11.5-28.5T680-480h-40v-40q0-17-11.5-28.5T600-560q-17 0-28.5 11.5T560-520v40h-40q-17 0-28.5 11.5T480-440q0 17 11.5 28.5T520-400h40Z" />
                </svg>
                <span>Submissions <span id="submissionsBadge" class="nav-badge" style="display:none;">0</span></span>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e8eaed">
                    <path
                        d="M480-361q-8 0-15-2.5t-13-8.5L268-556q-11-11-11-28t11-28q11-11 28-11t28 11l156 156 156-156q11-11 28-11t28 11q11 11 11 28t-11 28L508-372q-6 6-13 8.5t-15 2.5Z" />
                </svg>
            </button>
            <ul class="sub-menu">
                <div>
                    <li <?= ($current_page == 'documents.php') ? 'class="active"' : '' ?>><a href="documents.php">Documents
                            <span id="docBadge" class="nav-badge" style="display:none;">0</span></a></li>
                    <li <?= ($current_page == 'timeouts.php') ? 'class="active"' : '' ?>><a href="timeouts.php">Timeouts
                            <span id="timeoutBadge" class="nav-badge" style="display:none;">0</span></a></li>
                </div>
            </ul>
        </li>
        <li>
            <a href="#" onclick="showLogoutModal(); return false;">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z" />
                </svg>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Logout</h3>
        </div>

        <div class="logout-modal-body">
            <p>Are you sure you want to logout from the admin portal?</p>
        </div>

        <div class="logout-modal-footer">
            <button type="button" class="btn btn-cancel" onclick="hideLogoutModal()">
                <i class="fas fa-times"></i>
                Cancel
            </button>
            <a href="../logout.php" class="btn btn-logout-confirm">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<style>
    /* Logout Modal Styles */
    .logout-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease-out;
    }

    .logout-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logout-modal-content {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        max-width: 450px;
        width: 90%;
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
        border: 3px solid #1ad21c;
    }

    .logout-modal-header {
        background-color: #11121a;
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
    }

    .logout-modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .logout-modal-body {
        padding: 1rem;
        text-align: center;
    }

    .logout-modal-body p {
        margin: 0 0 0.5rem 0;
        color: #6c757d;
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .logout-modal-footer {
        padding: 1rem;
        background: #f8f9fa;
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
    }

    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-logout-confirm {
        background: #dc3545;
        color: white;
    }

    .btn-logout-confirm:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @media (max-width: 576px) {
        .logout-modal-content {
            width: 95%;
            margin: 1rem;
        }

        .logout-modal-footer {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.addEventListener('click', function (event) {
        const modal = document.getElementById('logoutModal');
        if (event.target === modal) {
            hideLogoutModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            hideLogoutModal();
        }
    });

    // Notification Polling
    function checkNotifications() {
        // Check document notifications
        fetch('check_notifications.php')
            .then(res => res.json())
            .then(data => {
                const docEl = document.getElementById('docBadge');
                if (docEl) {
                    if (data.count > 0) {
                        docEl.textContent = data.count;
                        docEl.style.display = 'inline-block';
                    } else {
                        docEl.style.display = 'none';
                    }
                }

                // Check timeout notifications
                const timeoutEl = document.getElementById('timeoutBadge');
                if (timeoutEl && data.timeout_count !== undefined) {
                    if (data.timeout_count > 0) {
                        timeoutEl.textContent = data.timeout_count;
                        timeoutEl.style.display = 'inline-block';
                    } else {
                        timeoutEl.style.display = 'none';
                    }
                }

                // Check attendance notifications
                const attendanceEl = document.getElementById('attendanceBadge');
                if (attendanceEl && data.attendance_count !== undefined) {
                    if (data.attendance_count > 0) {
                        attendanceEl.textContent = data.attendance_count;
                        attendanceEl.style.display = 'inline-block';
                    } else {
                        attendanceEl.style.display = 'none';
                    }
                }

                // Update parent Submissions badge
                const parentBadge = document.getElementById('submissionsBadge');
                if (parentBadge) {
                    const total = (parseInt(data.count) || 0) + (parseInt(data.timeout_count) || 0);
                    if (total > 0) {
                        parentBadge.textContent = total;
                        parentBadge.style.display = 'inline-block';
                    } else {
                        parentBadge.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Notification poll error:', err));
    }

    // Poll every 10 seconds
    setInterval(checkNotifications, 10000);
    // Initial check (delay slightly to ensure load)
    setTimeout(checkNotifications, 1000);
</script>

<style>
    .nav-badge {
        background: #dc3545;
        color: white;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
        font-weight: bold;
        vertical-align: middle;
    }
</style>
<script type="text/javascript" src="../js/app.js" defer></script>