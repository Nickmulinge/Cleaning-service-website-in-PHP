<?php
// Application configuration
define('BASE_URL', 'http://localhost/cleanfinity/');
define('SITE_NAME', 'Cleanfinity Cleaning Services');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u825272840_cleanfinity_db');
define('DB_USER', 'u825272840_cleanfinity_db');
define('DB_PASS', 'Cleanfinity_db@123');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token generation
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF Token validation
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $directories = ['models/', 'controllers/', 'helpers/'];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>
