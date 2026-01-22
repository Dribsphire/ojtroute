<?php

/**
 * Student Authentication Middleware
 * Use this file at the top of student-only pages to require student authentication
 * 
 * Usage: require_once __DIR__ . '/../../app/middleware/requireStudent.php';
 */

require_once __DIR__ . '/../services/AuthService.php';

use App\Services\AuthService;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize authentication service
$authService = new AuthService();

// Set no-cache headers to prevent browser back button access
$authService->setNoCacheHeaders();

// Check if user is authenticated
if (!$authService->isAuthenticated()) {
    $authService->logout();
    header('Location: ../login.php');
    exit();
}

// Check if user has student role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    $authService->logout();
    header('Location: ../403.php');
    exit();
}

// Get student data from session
$student_id = $_SESSION['user_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;
$full_name = $_SESSION['full_name'] ?? null;
$email = $_SESSION['email'] ?? null;
$section_id = $_SESSION['section_id'] ?? null;

// Validate required session data
if (!$student_id || !$school_id) {
    $authService->logout();
    header('Location: ../login.php');
    exit();
}
