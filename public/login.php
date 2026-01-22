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