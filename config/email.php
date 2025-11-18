<?php
/**
 * Email Configuration
 * Configure your SMTP settings here
 */

return [
    // SMTP Configuration
    'smtp_host' => 'smtp.gmail.com', // Change to your SMTP server
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // Change to your email
    'smtp_password' => 'your-app-password', // Use app password for Gmail
    
    // Sender Information
    'from_email' => 'noreply@cleanfinity.com',
    'from_name' => 'Cleanfinity Cleaning Services',
    
    // Development Settings
    'debug_mode' => true, // Set to false in production
    'log_emails' => true, // Log email attempts
];
?>
