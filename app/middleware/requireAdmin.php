<?php

/**
 * Admin Authentication Middleware
 * Use this file at the top of admin pages to require admin authentication
 * 
 * Usage: require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
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

// Require admin authentication (will redirect if not admin)
$authService->requireAdmin('../admin_login.php');


