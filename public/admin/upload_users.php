<?php
/**
 * CSV Upload Handler
 * Handles CSV file upload and user registration
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/controller/UserController.php';

use App\Controller\UserController;

$controller = new UserController();
$controller->handleCSVUpload();

