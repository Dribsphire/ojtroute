<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/services/ProfileService.php';

use App\Services\ProfileService;

// Get current admin profile
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user_id'] ?? null;
$profile = null;

if ($userId) {
    $profileService = new ProfileService();
    $profile = $profileService->getAdminProfile($userId);
}

// Fallback to session data if profile not found
if (!$profile && isset($_SESSION)) {
    $profile = [
        'id' => $_SESSION['user_id'] ?? null,
        'school_id' => $_SESSION['school_id'] ?? 'N/A',
        'full_name' => $_SESSION['full_name'] ?? 'Admin User',
        'email' => $_SESSION['email'] ?? 'N/A',
        'role' => $_SESSION['role'] ?? 'admin',
        'gender' => null,
        'contact' => null,
        'facebook_name' => null,
        'profile_pic_path' => $_SESSION['profile_pic_path'] ?? null,
        'created_at' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--accent-clr);
            margin-right: 2rem;
        }
        
        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .profile-info p {
            margin: 0.3rem 0;
            color: #666;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .detail-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .detail-card h3 {
            margin-top: 0;
            color: var(--accent-clr);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .detail-card p {
            margin: 0.5rem 0 0 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--accent-clr);
            color: white;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            margin: 0;
            color: var(--text-clr);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
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
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>
    
    <main>
        <div class="profile-container">
            <div id="profileMessage" style="display: none; margin-bottom: 1rem; padding: 1rem; border-radius: 0.5em;"></div>
            <div class="profile-header">
                <?php
                $profilePicUrl = $profile['profile_pic_path'] 
                    ? '../../' . htmlspecialchars($profile['profile_pic_path'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($profile['full_name'] ?? 'Admin') . '&background=random';
                ?>
                <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Admin Profile" class="profile-picture" id="profileImage" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($profile['full_name'] ?? 'Admin'); ?>&background=random'">
                <div class="profile-info">
                    <h1 id="adminName" style="color: var(--text-clr);"><?php echo htmlspecialchars($profile['full_name'] ?? 'Admin User'); ?></h1>
                    <p><i class="fas fa-envelope"></i> <span id="adminEmail"><?php echo htmlspecialchars($profile['email'] ?? 'N/A'); ?></span></p>
                    <p><i class="fas fa-user-tag"></i> <span id="adminRole">Administrator</span></p>
                    <p><i class="fas fa-id-card"></i> <span id="adminId"><?php echo htmlspecialchars($profile['school_id'] ?? 'N/A'); ?></span></p>
                </div>
            </div>
            
            <div class="profile-details">
                <div class="detail-card">
                    <h3>Gender</h3>
                    <p id="adminGender"><?php echo htmlspecialchars($profile['gender'] ?? 'Not specified'); ?></p>
                </div>
                <div class="detail-card">
                    <h3>Contact Number</h3>
                    <p id="adminContact"><?php echo htmlspecialchars($profile['contact'] ?? 'Not provided'); ?></p>
                </div>
                <div class="detail-card">
                    <h3>Facebook Name</h3>
                    <p id="adminFacebook"><?php echo htmlspecialchars($profile['facebook_name'] ?? 'Not provided'); ?></p>
                </div>
                <div class="detail-card">
                    <h3>Account Created</h3>
                    <p id="adminCreatedAt"><?php 
                        if ($profile['created_at']) {
                            echo date('F j, Y', strtotime($profile['created_at']));
                        } else {
                            echo 'N/A';
                        }
                    ?></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="openModal('changePictureModal')">
                    <i class="fas fa-camera"></i> Change Profile Picture
                </button>
                <button class="btn btn-secondary" onclick="openModal('editProfileModal')">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
    </main>
    
    <!-- Change Profile Picture Modal -->
    <div id="changePictureModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: var(--accent-clr);">Change Profile Picture</h2>
                <button class="close-btn" onclick="closeModal('changePictureModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="uploadMessage" style="display: none; margin-bottom: 1rem; padding: 1rem; border-radius: 0.5em;"></div>
                <div style="text-align: center; margin-bottom: 2rem;">
                    <?php
                    $currentPicUrl = $profile['profile_pic_path'] 
                        ? '../../' . htmlspecialchars($profile['profile_pic_path'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($profile['full_name'] ?? 'Admin') . '&background=random';
                    ?>
                    <img src="<?php echo htmlspecialchars($currentPicUrl); ?>" alt="Current Profile" id="currentProfilePic" 
                         style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($profile['full_name'] ?? 'Admin'); ?>&background=random'">
                    <p style="color: #666; margin-bottom: 1.5rem;">Upload a new profile picture (JPG, PNG, max 5MB)</p>
                    <input type="file" id="profilePicUpload" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                    <button class="btn btn-primary" onclick="document.getElementById('profilePicUpload').click()">
                        <i class="fas fa-upload"></i> Choose Image
                    </button>
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeModal('changePictureModal')">Cancel</button>
                    <button class="btn btn-primary" id="savePictureBtn" onclick="saveProfilePicture()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: var(--accent-clr);">Edit Profile</h2>
                <button class="close-btn" onclick="closeModal('editProfileModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="editProfileMessage" style="display: none; margin-bottom: 1rem; padding: 1rem; border-radius: 0.5em;"></div>
                <div class="form-group">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" class="form-control" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email Address</label>
                    <input type="email" id="editEmail" class="form-control" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editRole">Role</label>
                    <input type="text" id="editRole" class="form-control" value="Administrator" disabled>
                </div>
                <div class="form-group">
                    <label for="editSchoolId">School ID</label>
                    <input type="text" id="editSchoolId" class="form-control" value="<?php echo htmlspecialchars($profile['school_id'] ?? ''); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="editGender">Gender</label>
                    <select id="editGender" class="form-control">
                        <option value="">-- Select Gender --</option>
                        <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="non-binary" <?php echo ($profile['gender'] ?? '') === 'non-binary' ? 'selected' : ''; ?>>Non-binary</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editContact">Contact Number</label>
                    <input type="tel" id="editContact" class="form-control" value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="editFacebook">Facebook Name</label>
                    <input type="text" id="editFacebook" class="form-control" value="<?php echo htmlspecialchars($profile['facebook_name'] ?? ''); ?>">
                </div>
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeModal('editProfileModal')">Cancel</button>
                    <button class="btn btn-primary" id="saveProfileBtn" onclick="saveProfileChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Profile picture preview
        document.getElementById('profilePicUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('uploadMessage', 'File size exceeds 5MB limit', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showMessage('uploadMessage', 'Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('currentProfilePic').src = e.target.result;
                    document.getElementById('uploadMessage').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Save profile picture
        function saveProfilePicture() {
            const fileInput = document.getElementById('profilePicUpload');
            const saveBtn = document.getElementById('savePictureBtn');
            const messageDiv = document.getElementById('uploadMessage');
            
            if (fileInput.files.length === 0) {
                showMessage('uploadMessage', 'Please select an image to upload.', 'error');
                return;
            }
            
            const file = fileInput.files[0];
            
            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                showMessage('uploadMessage', 'File size exceeds 5MB limit', 'error');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('uploadMessage', 'Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
                return;
            }
            
            // Disable button during upload
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            messageDiv.style.display = 'none';
            
            // Create FormData
            const formData = new FormData();
            formData.append('profile_picture', file);
            
            // Upload file
            fetch('upload_profile_picture.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update profile image
                    const newImageUrl = '../../' + data.profile_pic_path;
                    document.getElementById('profileImage').src = newImageUrl;
                    document.getElementById('currentProfilePic').src = newImageUrl;
                    
                    showMessage('uploadMessage', data.message || 'Profile picture updated successfully!', 'success');
                    
                    setTimeout(() => {
                        closeModal('changePictureModal');
                        // Reload page to refresh profile
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('uploadMessage', data.message || 'Error uploading profile picture', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('uploadMessage', 'Network error. Please try again.', 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save Changes';
            });
        }
        
        // Save profile changes
        function saveProfileChanges() {
            const fullName = document.getElementById('editFullName').value.trim();
            const email = document.getElementById('editEmail').value.trim();
            const gender = document.getElementById('editGender').value;
            const contact = document.getElementById('editContact').value.trim();
            const facebook = document.getElementById('editFacebook').value.trim();
            const saveBtn = document.getElementById('saveProfileBtn');
            const messageDiv = document.getElementById('editProfileMessage');
            
            // Validate required fields
            if (!fullName) {
                showMessage('editProfileMessage', 'Full name is required', 'error');
                return;
            }
            
            if (!email) {
                showMessage('editProfileMessage', 'Email address is required', 'error');
                return;
            }
            
            // Disable button during update
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            messageDiv.style.display = 'none';
            
            // Send update request
            fetch('update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    full_name: fullName,
                    email: email,
                    gender: gender || null,
                    contact: contact || null,
                    facebook_name: facebook || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the profile display
                    document.getElementById('adminName').textContent = fullName;
                    document.getElementById('adminEmail').textContent = email;
                    document.getElementById('adminGender').textContent = gender || 'Not specified';
                    document.getElementById('adminContact').textContent = contact || 'Not provided';
                    document.getElementById('adminFacebook').textContent = facebook || 'Not provided';
                    
                    showMessage('editProfileMessage', data.message || 'Profile updated successfully!', 'success');
                    
                    setTimeout(() => {
                        closeModal('editProfileModal');
                        // Reload page to refresh session data
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('editProfileMessage', data.message || 'Error updating profile', 'error');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save Changes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('editProfileMessage', 'Network error. Please try again.', 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Save Changes';
            });
        }
        
        // Helper function to show messages
        function showMessage(elementId, message, type) {
            const messageDiv = document.getElementById(elementId);
            messageDiv.style.display = 'block';
            messageDiv.className = type === 'success' ? 'alert-success' : 'alert-danger';
            messageDiv.style.backgroundColor = type === 'success' 
                ? 'rgba(26, 210, 28, 0.2)' 
                : 'rgba(255, 77, 77, 0.2)';
            messageDiv.style.color = type === 'success' ? '#1ad21c' : '#ff4d4d';
            messageDiv.style.border = `1px solid ${type === 'success' ? '#1ad21c' : '#ff4d4d'}`;
            messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'times-circle'} me-2"></i>${escapeHtml(message)}`;
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, type === 'success' ? 3000 : 5000);
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Reset modals when closing
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            
            if (modalId === 'changePictureModal') {
                document.getElementById('profilePicUpload').value = '';
                document.getElementById('uploadMessage').style.display = 'none';
                document.getElementById('savePictureBtn').disabled = false;
                document.getElementById('savePictureBtn').innerHTML = 'Save Changes';
            } else if (modalId === 'editProfileModal') {
                document.getElementById('editProfileMessage').style.display = 'none';
                document.getElementById('saveProfileBtn').disabled = false;
                document.getElementById('saveProfileBtn').innerHTML = 'Save Changes';
            }
        }
    </script>
</body>
</html>