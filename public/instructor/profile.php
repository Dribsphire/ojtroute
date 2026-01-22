<?php
session_start();
require_once __DIR__ . '/../../app/middleware/requireInstructor.php';
require_once __DIR__ . '/../../app/services/InstructorService.php';

// Initialize service
$instructorService = new \App\Services\InstructorService();
$user_id = $_SESSION['user_id'];

// Handle POST requests (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];

    if ($action === 'update_info') {
        $result = $instructorService->updateInstructorInfo($user_id, [
            'fullname' => trim($_POST['fullname']),
            'email' => trim($_POST['email']),
            'contact' => trim($_POST['contact']),
            'facebook' => trim($_POST['facebook'])
        ]);
        $response = $result ? ['success' => true, 'message' => 'Information updated successfully!'] : ['success' => false, 'message' => 'Failed to update information.'];
    }
    elseif ($action === 'change_password') {
        $current = $_POST['currentPassword'];
        $new = $_POST['newPassword'];
        $response = $instructorService->changePassword($user_id, $current, $new);
    }
    elseif ($action === 'update_photo') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['profile_pic']['size'] <= 5000000) { // 5MB
                    $uploadDir = __DIR__ . '/../../storage/uploads/profile_pics/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $newFilename = 'instructor_' . $user_id . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                        $relativePath = '../../storage/uploads/profile_pics/' . $newFilename;
                        if ($instructorService->updateProfilePicturePath($user_id, $relativePath)) {
                            $response = ['success' => true, 'message' => 'Profile picture updated!', 'new_path' => $relativePath];
                        } else {
                            $response = ['success' => false, 'message' => 'Failed to update database path.'];
                        }
                    } else {
                        $response = ['success' => false, 'message' => 'Failed to upload file.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'File too large (Max 5MB).'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid file type.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'No file uploaded or upload error.'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Include nav after logic (keeps HTML clean)
require_once 'instructor_nav.php';

$instructor = $instructorService->getInstructorProfile($user_id);
$instructor_id = $instructorService->getInstructorId($user_id);
$sections = $instructorService->getInstructorSections($instructor_id);

$section_names = array_map(function($s) { return $s['section_name']; }, $sections);
$section_string = !empty($section_names) ? implode(', ', $section_names) : 'No sections assigned';

$instructor_data = [
    'profile_pic' => !empty($instructor['profile_pic_path']) ? $instructor['profile_pic_path'] : '../../storage/images/default_profile.jpg',
    'fullname' => $instructor['full_name'],
    'school_id' => $instructor['school_id'],
    'email' => $instructor['email'],
    'role' => ucfirst($instructor['role']),
    'contact' => $instructor['contact'] ?: 'Not provided',
    'facebook' => $instructor['facebook_name'] ?: 'Not provided',
    'section' => $section_string,
    'department' => $instructor['department'] ?: 'Not assigned'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Profile - OJTRoute System</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/instructor_style.css">
    <style>

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #3e3f4d;
        }

        .profile-pic-container {
            position: relative;
            margin-right: 2rem;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #4a4e69;
        }

        .change-pic-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #4a4e69;
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .change-pic-btn:hover {
            background: #3a86ff;
            transform: scale(1.1);
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            color: #fff;
        }

        .profile-info p {
            margin: 0.25rem 0;
            color: #b8b8b8;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: #363848;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-card h3 {
            margin-top: 0;
            color: #fff;
            border-bottom: 1px solid #4a4e69;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            margin-bottom: 0.75rem;
            align-items: flex-start;
        }

        .detail-label {
            font-weight: 600;
            color: #b8b8b8;
            width: 140px;
            flex-shrink: 0;
            padding-right: 1rem;
        }

        .detail-value {
            color: #fff;
            flex: 1;
            word-break: break-word;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3a86ff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .bton{
            background-color: var(--accent-clr);
            color: white;
            padding: 10px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            overflow: auto;
        }

        .modal-content {
            background: #2a2b3a;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #fff;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #4a4e69;
            border-radius: 5px;
            background: #363848;
            color: #fff;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #3a86ff;
            box-shadow: 0 0 0 2px rgba(58, 134, 255, 0.25);
        }

        .btn-block {
            display: block;
            width: 100%;
            padding: 0.75rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-pic-container {
                margin: 0 0 1.5rem 0;
            }

            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-pic-container">
                    <img src="<?php echo htmlspecialchars($instructor_data['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
                    <button class="change-pic-btn" onclick="document.getElementById('changePicModal').style.display='block'">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($instructor_data['fullname']); ?></h1>
                    <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($instructor_data['school_id']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($instructor_data['email']); ?></p>
                    <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($instructor_data['role']); ?></p>
                </div>
            </div>

            <div class="profile-details">
                <div class="detail-card">
                    <h3>Contact Information</h3>
                    <div class="detail-item">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['contact']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Facebook:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['facebook']); ?></div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>Academic Information</h3>
                    <div class="detail-item">
                        <div class="detail-label">Department:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['department']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Section(s):</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['section']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Role:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($instructor_data['role']); ?></div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button class="bton" onclick="document.getElementById('editInfoModal').style.display='block'">
                    <i class="fas fa-edit"></i> Edit Information
                </button>
                <button class="btn btn-secondary" onclick="document.getElementById('changePassModal').style.display='block'">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>
    </main>

    <!-- Change Profile Picture Modal -->
    <div id="changePicModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
            <h2>Change Profile Picture</h2>
            <form id="profilePicForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="newProfilePic">Upload New Photo</label>
                    <input type="file" id="newProfilePic" name="profile_pic" class="form-control" accept="image/*" required>
                    <small class="text-muted">Maximum file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-upload"></i> Upload Picture
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Information Modal -->
    <div id="editInfoModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
            <h2>Edit Information</h2>
            <form id="editInfoForm">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($instructor_data['fullname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($instructor_data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact" class="form-control" value="<?php echo htmlspecialchars($instructor_data['contact']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="facebook">Facebook Username</label>
                    <input type="text" id="facebook" name="facebook" class="form-control" value="<?php echo htmlspecialchars($instructor_data['facebook']); ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="bton">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePassModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
            <h2>Change Password</h2>
            <form id="changePassForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="bton">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Close modal with Escape key
        document.onkeydown = function(event) {
            event = event || window.event;
            if (event.key === 'Escape') {
                const modals = document.getElementsByClassName('modal');
                for (let i = 0; i < modals.length; i++) {
                    modals[i].style.display = 'none';
                }
            }
        };

        // Form submission handling with AJAX
        function sendUpdate(url, formData, modalId) {
            fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    if (modalId) document.getElementById(modalId).style.display = 'none';
                    if (data.new_path) {
                        // Update profile picture URL to avoid cache issues
                        document.querySelector('.profile-pic').src = data.new_path + '?t=' + new Date().getTime();
                        document.getElementById('profilePicForm').reset();
                    } else {
                        // For text updates or password, reload to reflect changes
                        location.reload();
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred. Please try again.');
            });
        }

        document.getElementById('profilePicForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_photo');
            sendUpdate('profile.php', formData, 'changePicModal');
        };

        document.getElementById('editInfoForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_info');
            sendUpdate('profile.php', formData, 'editInfoModal');
        };

        document.getElementById('changePassForm').onsubmit = function(e) {
            e.preventDefault();
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            
            if (newPass !== confirmPass) {
                alert('New password and confirm password do not match!');
                return false;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'change_password');
            sendUpdate('profile.php', formData, 'changePassModal');
        };
    </script>
</body>
</html>