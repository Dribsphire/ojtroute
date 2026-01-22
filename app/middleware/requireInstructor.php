<?php

/**
 * Instructor Authentication Middleware
 * Use this file at the top of instructor-only pages to require instructor authentication
 * 
 * Usage: require_once __DIR__ . '/../../app/middleware/requireInstructor.php';
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

// Check if user has instructor role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    $authService->logout();
    header('Location: ../403.php');
    exit();
}

// Get instructor data from session
$instructor_user_id = $_SESSION['user_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;
$full_name = $_SESSION['full_name'] ?? null;
$email = $_SESSION['email'] ?? null;

// Validate required session data
if (!$instructor_user_id || !$school_id) {
    $authService->logout();
    header('Location: ../login.php');
    exit();
}
