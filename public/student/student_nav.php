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
        <li <?= ($current_page == 'student_profile.php') ? 'class="active"' : '' ?>>
            <a href="student_profile.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" />
                </svg>
                <span>Profile</span>
            </a>
        </li>
        <li <?= ($current_page == 'attendance.php') ? 'class="active"' : '' ?>>
            <a href="attendance.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M80-320v-112q0-34 17.5-62.5T144-538q62-31 126-46.5T400-600q45 0 89 7t88 22q-17 14-31 30.5T521-505q-30-8-60-11.5t-61-3.5q-56 0-111 13.5T180-466q-9 5-14.5 14t-5.5 20v32h323q-2 20-2 40t2 40H80Zm320-80Zm0-240q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm0-80q33 0 56.5-23.5T480-800q0-33-23.5-56.5T400-880q-33 0-56.5 23.5T320-800q0 33 23.5 56.5T400-720Zm0-80Zm400 0q0 66-47 113t-113 47q-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T584-952q14-5 28-6.5t28-1.5q66 0 113 47t47 113Zm-40 640q-83 0-141.5-58.5T560-360q0-84 58.5-142T760-560q84 0 142 58t58 142q0 83-58 141.5T760-160Zm-28-110 141-142-28-28-113 113-57-57-28 29 85 85Z" />
                </svg>
                <span>Attendance</span>
            </a>
        </li>
        <li <?= ($current_page == 'calendar.php') ? 'class="active"' : '' ?>>
            <a href="calendar.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-160 0q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480q17 0 28.5 11.5T360-440q0 17-11.5 28.5T320-400Zm320 0q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-160 0q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-320q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm320 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z" />
                </svg>
                <span>Calendar</span>
            </a>
        </li>
        <li>
            <button onclick=toggleSubMenu(this) class="dropdown-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e8eaed">
                    <path
                        d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h207q16 0 30.5 6t25.5 17l57 57h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Zm400-160v40q0 17 11.5 28.5T600-320q17 0 28.5-11.5T640-360v-40h40q17 0 28.5-11.5T720-440q0-17-11.5-28.5T680-480h-40v-40q0-17-11.5-28.5T600-560q-17 0-28.5 11.5T560-520v40h-40q-17 0-28.5 11.5T480-440q0 17 11.5 28.5T520-400h40Z" />
                </svg>
                <span>Submissions</span>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e8eaed">
                    <path
                        d="M480-361q-8 0-15-2.5t-13-8.5L268-556q-11-11-11-28t11-28q11-11 28-11t28 11l156 156 156-156q11-11 28-11t28 11q11 11 11 28t-11 28L508-372q-6 6-13 8.5t-15 2.5Z" />
                </svg>
            </button>
            <ul class="sub-menu">
                <div>
                    <li <?= ($current_page == 'student_documents.php') ? 'class="active"' : '' ?>>
                        <a href="student_documents.php" style="position: relative;">
                            Documents
                            <span id="docNotificationBadge" class="notification-badge" style="display: none;"></span>
                        </a>
                    </li>
                    <li <?= ($current_page == 'student_timeouts.php') ? 'class="active"' : '' ?>><a
                            href="student_timeouts.php">Timeouts</a></li>
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
        padding: 1.5rem;
        background: #f8f9fa;
        display: flex;
        gap: 1rem;
        justify-content: center;
        align-items: center;
    }

    .logout-modal-footer .btn {
        flex: 1;
        max-width: 200px;
        min-width: 120px;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
        text-align: center;
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
            padding: 1rem;
        }

        .logout-modal-footer .btn {
            width: 100%;
            max-width: 100%;
            min-width: 100%;
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

    // Document Notification System
    function checkNewDocuments() {
        fetch('check_new_documents.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('docNotificationBadge');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error checking documents:', error));
    }

    // Check immediately on page load
    checkNewDocuments();

    // Check every 30 seconds
    setInterval(checkNewDocuments, 30000);
</script>

<style>
    /* Notification Badge Styles */
    .notification-badge {
        position: absolute;
        top: 10px;
        right: 20px;
        background: #ff4444;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 0.7rem;
        font-weight: bold;
        min-width: 18px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(255, 68, 68, 0.4);
    }

    /* Responsive Badge Adjustments */
    @media (max-width: 768px) {
        .notification-badge {
            top: 8px;
            right: 15px;
            font-size: 0.65rem;
            padding: 2px 5px;
            min-width: 16px;
            border-radius: 8px;
        }
    }

    @media (max-width: 480px) {
        .notification-badge {
            top: 6px;
            right: 10px;
            font-size: 0.6rem;
            padding: 1px 4px;
            min-width: 14px;
            border-radius: 7px;
        }
    }
</style>
<script type="text/javascript" src="../js/app.js" defer></script>