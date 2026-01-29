<?php
session_start();

// Load authentication service
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/AuthService.php';

use App\Services\AuthService;

$authService = new AuthService();
$error = '';
$errorType = 'danger'; // 'danger', 'warning', 'info'

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolId = trim($_POST['school_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($schoolId) || empty($password)) {
        $error = 'Please enter both School ID and Password';
        $errorType = 'warning';
    } else {
        // Try to authenticate as student first
        $studentResult = $authService->authenticateStudent($schoolId, $password);

        if ($studentResult['success'] && $studentResult['user']) {
            // Check if user is actually a student (not admin or instructor trying to login here)
            if ($studentResult['user']['role'] === 'student') {
                // Set session
                $authService->setSession($studentResult['user']);

                // Redirect to student attendance page
                header('Location: student/attendance.php');
                exit();
            } else {
                // Wrong role - show error instead of redirecting to 403
                $error = 'Invalid credentials. Please try again.';
                $errorType = 'danger';
            }
        }

        // If not student, try instructor
        $instructorResult = $authService->authenticateInstructor($schoolId, $password);

        if ($instructorResult['success'] && $instructorResult['user']) {
            // Check if user is actually an instructor (not admin or student trying to login here)
            if ($instructorResult['user']['role'] === 'instructor') {
                // Set session first (so notAssign.php can display user info)
                $authService->setSession($instructorResult['user']);

                // Check if instructor is assigned to a section
                $instructorId = $instructorResult['user']['instructor_id'] ?? null;

                if ($instructorId && $authService->isInstructorAssignedToSection($instructorId)) {
                    // Redirect to instructor student list page
                    header('Location: instructor/student_list.php');
                    exit();
                } else {
                    // Instructor not assigned to section - redirect to notAssign page
                    header('Location: notAssign.php');
                    exit();
                }
            } else {
                // Wrong role - show error instead of redirecting to 403
                $error = 'Invalid credentials. Please try again.';
                $errorType = 'danger';
            }
        }

        // If neither student nor instructor authentication succeeded, show error
        // Only show error if we haven't already set one from wrong role
        if (empty($error)) {
            // Determine which error to show based on the last attempted authentication
            $errorResult = $instructorResult['error'] ?? $studentResult['error'] ?? 'unknown';

            switch ($errorResult) {
                case 'not_found':
                    $error = 'School ID does not exist.';
                    $errorType = 'danger';
                    break;
                case 'wrong_password':
                    $error = 'Invalid password. Please try again.';
                    $errorType = 'danger';
                    break;
                case 'not_student':
                case 'not_instructor':
                    // User exists but wrong role - show error instead of redirecting
                    $error = 'Invalid credentials. Please try again.';
                    $errorType = 'danger';
                    break;
                default:
                    $error = 'Invalid credentials. Please try again.';
                    $errorType = 'danger';
            }
        }
    }
}

// If already authenticated, redirect based on role
if ($authService->isAuthenticated()) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $role = $_SESSION['role'] ?? '';

    switch ($role) {
        case 'student':
            header('Location: student/attendance.php');
            exit();
        case 'instructor':
            // Check if instructor is assigned
            $instructorId = $_SESSION['instructor_id'] ?? null;
            if ($instructorId && $authService->isInstructorAssignedToSection($instructorId)) {
                header('Location: instructor/student_list.php');
            } else {
                header('Location: notAssign.php');
            }
            exit();
        case 'admin':
            header('Location: admin/admin_userpage.php');
            exit();
        default:
            // Unknown role, stay on login page
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OJT Route</title>
    <link rel="icon" type="image/png" href="images/CHMSU.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <div class="chmsu-logo">
                            <img src="images/CHMSU.png" alt="CHMSU Logo">
                        </div>
                        <h1>OJT Route</h1>
                        <p>CARLOS HILADO MEMORIAL STATE UNIVERSITY OJT SYSTEM</p>
                    </div>

                    <div class="login-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-<?= htmlspecialchars($errorType) ?> alert-dismissible fade show"
                                role="alert">
                                <i
                                    class="bi bi-<?= $errorType === 'warning' ? 'exclamation-triangle' : 'x-circle' ?> me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="school_id" name="school_id"
                                    placeholder="School ID" value="<?= htmlspecialchars($_POST['school_id'] ?? '') ?>"
                                    required>
                                <label for="school_id">
                                    <i class="bi bi-person-badge me-2"></i>School ID
                                </label>
                            </div>

                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Password" required>
                                <label for="password">
                                    <i class="bi bi-lock me-2"></i>Password
                                </label>
                                <button type="button" class="btn btn-link password-toggle" id="passwordToggle">
                                    <i class="bi bi-eye" id="passwordIcon"></i>
                                </button>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                                <a href="about_developer.php"
                                    style="color: #6b7280; text-decoration: none; font-size:13px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960"
                                        width="18px" fill="#6b7280">
                                        <path
                                            d="M710-150q-63 0-106.5-43.5T560-300q0-63 43.5-106.5T710-450q63 0 106.5 43.5T860-300q0 63-43.5 106.5T710-150Zm0-80q29 0 49.5-20.5T780-300q0-29-20.5-49.5T710-370q-29 0-49.5 20.5T640-300q0 29 20.5 49.5T710-230Zm-550-30v-80h320v80H160Zm90-250q-63 0-106.5-43.5T100-660q0-63 43.5-106.5T250-810q63 0 106.5 43.5T400-660q0 63-43.5 106.5T250-510Zm0-80q29 0 49.5-20.5T320-660q0-29-20.5-49.5T250-730q-29 0-49.5 20.5T180-660q0 29 20.5 49.5T250-590Zm230-30v-80h320v80H480Zm230 320ZM250-660Z" />
                                    </svg>
                                    About Developers</a>
                                <a href="https://forms.gle/P9wWgxLmCYuqXhdFA" target="_blank"
                                    style="color: #6b7280; text-decoration: none; font-size:13px; display: flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960"
                                        width="18px" fill="currentColor">
                                        <path
                                            d="M480-200q66 0 113-47t47-113v-160q0-66-47-113t-113-47q-66 0-113 47t-47 113v160q0 66 47 113t113 47Zm-80-120h160v-80H400v80Zm0-160h160v-80H400v80Zm80 40Zm0 320q-65 0-120.5-32T272-240H160v-80h84q-3-20-3.5-40t-.5-40h-80v-80h80q0-20 .5-40t3.5-40h-84v-80h112q14-23 31.5-43t40.5-35l-64-66 56-56 86 86q28-9 57-9t57 9l88-86 56 56-66 66q23 15 41.5 34.5T688-640h112v80h-84q3 20 3.5 40t.5 40h80v80h-80q0 20-.5 40t-3.5 40h84v80H688q-32 56-87.5 88T480-120Z" />
                                    </svg>
                                    Report Bug
                                </a>
                            </div>
                            <small class="text-muted">
                                CHMSU OJT routing system @2025
                            </small>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('passwordToggle')?.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('bi-eye');
                passwordIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('bi-eye-slash');
                passwordIcon.classList.add('bi-eye');
            }
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                alert.classList.add('fade-out');
                setTimeout(function () {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
<style>
    :root {
        --chmsu-green: #0ea539;
        --chmsu-green-light: #34d399;
        --chmsu-green-dark: #059669;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-image: url('images/homepage.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        max-width: 500px;
        width: 100%;
        header: 90%;
    }

    .login-header {
        background: var(--chmsu-green);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .login-header h1 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

    .login-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }

    .login-body {
        padding: 2rem;
    }

    .form-floating {
        margin-bottom: 1rem;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e5e7eb;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        border-color: var(--chmsu-green);
        box-shadow: 0 0 0 0.2rem rgba(14, 165, 57, 0.25);
    }

    .btn-primary {
        background: var(--chmsu-green);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        width: 100%;
    }

    .btn-primary:hover {
        background: var(--chmsu-green-dark);
    }

    .alert {
        border-radius: 10px;
        border: none;
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        min-width: 300px;
        max-width: 500px;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    .alert.fade-out {
        animation: fadeOut 0.5s ease-out forwards;
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        to {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
    }

    .chmsu-logo {
        width: 70px;
        height: 70px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: var(--chmsu-green);
    }

    .chmsu-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .password-toggle {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        border: none;
        background: none;
        color: #6b7280;
        padding: 0;
        font-size: 1.2rem;
        transition: color 0.2s ease;
    }

    .password-toggle:hover {
        color: var(--chmsu-green);
    }

    .password-toggle:focus {
        outline: none;
        box-shadow: none;
    }

    .form-floating.position-relative .form-control {
        padding-right: 3rem;
    }
</style>

</html>