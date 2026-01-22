<?php
/**
 * CSV Template Download
 * Downloads CSV template for user registration
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/middleware/requireAdmin.php';
require_once __DIR__ . '/../../app/controller/UserController.php';

use App\Controller\UserController;

$controller = new UserController();
$controller->downloadCSVTemplate();

