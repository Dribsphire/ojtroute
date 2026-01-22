<?php

/**
 * Authentication Middleware
 * Use this file at the top of protected pages to require authentication
 * 
 * Usage: require_once __DIR__ . '/../../app/middleware/requireAuth.php';
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
    header('Location: ../admin_login.php');
    exit();
}


