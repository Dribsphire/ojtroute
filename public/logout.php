<?php

/**
 * Logout Page
 * OJT Route - User logout
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/AuthService.php';

use App\Services\AuthService;

session_start();
$authService = new AuthService();
$authService->setNoCacheHeaders();
$authService->logout();
session_write_close();

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit;
