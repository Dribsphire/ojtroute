<?php
/**
 * Public Directory Index
 * 
 * This file serves as the entry point for the public directory.
 * It redirects all root access to the login page for security.
 */

// Redirect to login page
header('Location: login.php');
exit();
