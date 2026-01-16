<?php
// public/index.php

// Start session
session_start();

// Define base path if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load configuration
require_once BASE_PATH . '/app/config/config.php';

// Load database connection
require_once APP_PATH . '/config/database.php';

// Load helper functions
require_once APP_PATH . '/helpers/functions.php';

// Load security functions
require_once APP_PATH . '/config/security.php';

// Initialize database
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Generate CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_csrf_token();
}

function generate_csrf_token() {
    // Generate a random token
    return bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


// Simple router
$request = $_SERVER['REQUEST_URI'];
$base_path = '/ticket-system/public/';
$route = str_replace($base_path, '', $request);
$route = explode('?', $route)[0];

// Default to login if empty
if (empty($route) || $route === '/') {
    $route = 'login';
}

// Route mapping
$routes = [
    'login' => 'login.php',
    'dashboard' => 'dashboard.php',
    'logout' => 'logout.php',
    'tickets/create' => 'create_ticket.php',
    'tickets/list' => 'ticket_list.php',
    'profile' => 'profile.php'
];

// Check if route exists
if (isset($routes[$route])) {
    $file = PUBLIC_PATH . '/' . $routes[$route];
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        echo "Page not found: " . e($route);
    }
} else {
    // Check if file exists directly
    $file = PUBLIC_PATH . '/' . $route . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        http_response_code(404);
        echo "Page not found: " . e($route);
    }
}
?>