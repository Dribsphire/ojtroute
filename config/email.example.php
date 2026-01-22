<?php
/**
 * Email Configuration Example
 * 
 * Copy this file to email.php and update with your actual credentials.
 * 
 * cp config/email.example.php config/email.php
 */

return [
    // SMTP Settings
    'smtp_host' => 'smtp.gmail.com',           // SMTP server (Gmail: smtp.gmail.com)
    'smtp_port' => 587,                        // SMTP port (587 for TLS, 465 for SSL)
    'smtp_secure' => 'tls',                    // Encryption: 'tls' or 'ssl'
    'smtp_auth' => true,                       // Enable SMTP authentication
    
    // Email Account Credentials
    'smtp_username' => 'your-email@gmail.com', // Your email address
    'smtp_password' => 'your-app-password',    // Your email password or app password
    
    // From Address
    'from_email' => 'your-email@gmail.com',    // Sender email address
    'from_name' => 'OJT Route System',         // Sender name
    
    // Reply To
    'reply_to_email' => 'your-email@gmail.com',
    'reply_to_name' => 'OJT Route System',
    
    // Additional Settings
    'charset' => 'UTF-8',
    'is_html' => true,
];






