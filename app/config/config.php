<?php
// app/config/config.php

// Prevent multiple definitions
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}

if (!defined('TEMPLATES_PATH')) {
    define('TEMPLATES_PATH', BASE_PATH . '/templates');
}

// Application settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Travel Ticket System');
    define('APP_VERSION', '1.0.0');
    define('ENVIRONMENT', 'development');
}

// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ticket_system');
    define('DB_USER', 'root');
    define('DB_PASSWORD', '');
}

// Application URLs
if (!defined('BASE_URL')) {
    // define('BASE_URL', 'http://localhost/ticket-system/public');
    define('BASE_URL', 'http://localhost:8000/public');
    define('SITE_URL', 'http://localhost:8000');
}

// File Upload Settings
if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 5242880); // 5MB
}

// Session Settings
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}

// Security
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
}

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>