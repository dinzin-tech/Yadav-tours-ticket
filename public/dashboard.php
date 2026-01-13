<?php
// public/dashboard.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Define constants if not defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    define('APP_PATH', BASE_PATH . '/app');
    define('BASE_URL', 'http://localhost/ticket-system/public');
}

// Load database
require_once APP_PATH . '/config/database.php';

// In dashboard.php, after creating database connection:
try {
    $db = getDB();
    
    // Get branding settings with database connection
    if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
        require_once APP_PATH . '/classes/BrandingSettings.php';
        $branding = BrandingSettings::getInstance($db);
        $brandingEnabled = true;
    } else {
        $branding = null;
        $brandingEnabled = false;
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create database connection
try {
    $db = getDB(); // Use getDB() instead of Database::getInstance()
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Get user ID
$user_id = $_SESSION['user_id'] ?? 1; // Default to user_id 1 if not set
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Get statistics
try {
    // Total tickets count
    if ($is_admin) {
        $stmt = $db->prepare("SELECT COUNT(*) as total_tickets FROM tickets");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as total_tickets FROM tickets WHERE generated_by = ?");
        $stmt->execute([$user_id]);
    }
    $totalTickets = $stmt->fetchColumn() ?: 0;
    
    // Today's tickets
    if ($is_admin) {
        $stmt = $db->prepare("SELECT COUNT(*) as today_tickets FROM tickets WHERE DATE(generated_at) = CURDATE()");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as today_tickets FROM tickets WHERE generated_by = ? AND DATE(generated_at) = CURDATE()");
        $stmt->execute([$user_id]);
    }
    $todayTickets = $stmt->fetchColumn() ?: 0;
    
    // This month tickets
    if ($is_admin) {
        $stmt = $db->prepare("SELECT COUNT(*) as month_tickets FROM tickets WHERE MONTH(generated_at) = MONTH(CURDATE()) AND YEAR(generated_at) = YEAR(CURDATE())");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as month_tickets FROM tickets WHERE generated_by = ? AND MONTH(generated_at) = MONTH(CURDATE()) AND YEAR(generated_at) = YEAR(CURDATE())");
        $stmt->execute([$user_id]);
    }
    $monthTickets = $stmt->fetchColumn() ?: 0;
    
    // Total revenue
    if ($is_admin) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM tickets");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM tickets WHERE generated_by = ?");
        $stmt->execute([$user_id]);
    }
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Today's revenue
    if ($is_admin) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM tickets WHERE DATE(generated_at) = CURDATE()");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_revenue FROM tickets WHERE generated_by = ? AND DATE(generated_at) = CURDATE()");
        $stmt->execute([$user_id]);
    }
    $todayRevenue = $stmt->fetchColumn() ?: 0;
    
    // Tickets by type
    if ($is_admin) {
        $stmt = $db->prepare("
            SELECT ticket_type, COUNT(*) as count 
            FROM tickets 
            GROUP BY ticket_type
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT ticket_type, COUNT(*) as count 
            FROM tickets 
            WHERE generated_by = ? 
            GROUP BY ticket_type
        ");
        $stmt->execute([$user_id]);
    }
    $ticketsByType = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Recent tickets
    if ($is_admin) {
        $stmt = $db->prepare("
            SELECT ticket_id, ticket_type, pnr, customer_name, total_amount, generated_at 
            FROM tickets 
            ORDER BY generated_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT ticket_id, ticket_type, pnr, customer_name, total_amount, generated_at 
            FROM tickets 
            WHERE generated_by = ? 
            ORDER BY generated_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
    }
    $recentTickets = $stmt->fetchAll();
    
    // Get paid vs pending tickets
    if ($is_admin) {
        $stmt = $db->prepare("
            SELECT payment_status, COUNT(*) as count 
            FROM tickets 
            GROUP BY payment_status
        ");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("
            SELECT payment_status, COUNT(*) as count 
            FROM tickets 
            WHERE generated_by = ? 
            GROUP BY payment_status
        ");
        $stmt->execute([$user_id]);
    }
    $paymentStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    $error = "Failed to load statistics: " . $e->getMessage();
    $totalTickets = $todayTickets = $monthTickets = $totalRevenue = $todayRevenue = 0;
    $ticketsByType = [];
    $recentTickets = [];
    $paymentStats = [];
}

// Get company name from branding or default
$companyName = $brandingEnabled ? $branding->getCompanyName() : 'Ticket System';
$primaryColor = $brandingEnabled ? $branding->getPrimaryColor() : '#0d6efd';
$secondaryColor = $brandingEnabled ? $branding->getSecondaryColor() : '#6c757d';
$logoUrl = $brandingEnabled ? $branding->getLogoUrl() : null;
$companyAddress = $brandingEnabled ? $branding->get('company_address') : '';
$companyPhone = $brandingEnabled ? $branding->get('company_phone') : '';
$companyEmail = $brandingEnabled ? $branding->get('company_email') : '';
$footerText = $brandingEnabled ? $branding->get('footer_text') : '© ' . date('Y') . ' Ticket System. All rights reserved.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($companyName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
    <style>
        :root {
            --primary-color: <?= $primaryColor ?>;
            --secondary-color: <?= $secondaryColor ?>;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            background: var(--primary-color) !important;
        }
        .stat-card {
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,.12);
        }
        .stat-card-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .quick-action-btn {
            padding: 20px 10px;
            border-radius: 10px;
            transition: all 0.3s;
            border: 2px solid transparent;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,.05);
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
        }
        .quick-action-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            cursor: pointer;
        }
        .ticket-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ticket-train { 
            background-color: rgba(13, 110, 253, 0.1); 
            color: var(--primary-color); 
            border: 1px solid rgba(13, 110, 253, 0.2);
        }
        .ticket-flight { 
            background-color: rgba(23, 162, 184, 0.1); 
            color: #17a2b8; 
            border: 1px solid rgba(23, 162, 184, 0.2);
        }
        .ticket-bus { 
            background-color: rgba(40, 167, 69, 0.1); 
            color: #28a745; 
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .ticket-cab { 
            background-color: rgba(255, 193, 7, 0.1); 
            color: #ffc107; 
            border: 1px solid rgba(255, 193, 7, 0.2);
        }
        .ticket-tour { 
            background-color: rgba(220, 53, 69, 0.1); 
            color: #dc3545; 
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        .btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        .border-primary {
            border-color: var(--primary-color) !important;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid var(--primary-color) !important;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 50%, black) 100%);
            color: white;
            border-bottom: none;
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 70%, black) 100%);
            color: white;
            border-radius: 15px;
            overflow: hidden;
        }
        .welcome-card h2 {
            font-weight: 600;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        .progress-bar {
            border-radius: 4px;
        }
        .agent-badge {
            background: linear-gradient(135deg, var(--secondary-color) 0%, color-mix(in srgb, var(--secondary-color) 50%, black) 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .admin-badge {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .filter-badge {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .filter-badge:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .filter-badge.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyName) ?>" height="40" class="rounded me-2">
                <?php endif; ?>
                <span class="fw-bold"><?= htmlspecialchars($companyName) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="createDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-plus-circle"></i> Create Ticket
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="create_ticket.php?type=train">
                                <i class="fas fa-train text-primary"></i> Train Ticket
                            </a></li>
                            <li><a class="dropdown-item" href="create_ticket.php?type=flight">
                                <i class="fas fa-plane text-info"></i> Flight Ticket
                            </a></li>
                            <li><a class="dropdown-item" href="create_ticket.php?type=bus">
                                <i class="fas fa-bus text-success"></i> Bus Ticket
                            </a></li>
                            <li><a class="dropdown-item" href="create_ticket.php?type=cab">
                                <i class="fas fa-taxi text-warning"></i> Cab Booking
                            </a></li>
                            <li><a class="dropdown-item" href="create_ticket.php?type=tour">
                                <i class="fas fa-suitcase text-danger"></i> Tour Package
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ticket_list.php">
                            <i class="fas fa-list"></i> All Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a></li>
                            <?php if ($brandingEnabled): ?>
                            <li><a class="dropdown-item" href="admin/branding_settings.php">
                                <i class="fas fa-palette"></i> Branding
                            </a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> 
                            <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            <?php if ($is_admin): ?>
                            <span class="admin-badge ms-1">Admin</span>
                            <?php else: ?>
                            <span class="agent-badge ms-1">Agent</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Error Alert -->
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="fas fa-hand-wave"></i> 
                                    Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!
                                </h2>
                                <p class="mb-0 opacity-75">
                                    <i class="fas fa-calendar-alt"></i> <?= date('l, F j, Y') ?>
                                    | <i class="fas fa-clock"></i> <span class="server-time"><?= date('H:i:s') ?></span>
                                    <?php if ($is_admin): ?>
                                    | <i class="fas fa-user-shield"></i> <span class="text-warning">Administrator Mode</span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($companyAddress) || !empty($companyPhone)): ?>
                                <div class="mt-3">
                                    <?php if (!empty($companyAddress)): ?>
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($companyAddress) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($companyPhone)): ?>
                                    <span class="badge bg-light text-dark me-2">
                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($companyPhone) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($companyEmail)): ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($companyEmail) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="h1 mb-0"><?= $totalTickets ?></div>
                                <p class="mb-0 opacity-75">Total Tickets Generated</p>
                                <button class="btn btn-light mt-2" onclick="location.href='create_ticket.php'">
                                    <i class="fas fa-plus"></i> Create New Ticket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card text-white" style="background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 50%, black) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50 mb-1">TOTAL TICKETS</h6>
                                <h2 class="mb-0"><?= $totalTickets ?></h2>
                                <small class="opacity-75">All time tickets</small>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card text-white" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50 mb-1">TODAY'S TICKETS</h6>
                                <h2 class="mb-0"><?= $todayTickets ?></h2>
                                <small class="opacity-75">Generated today</small>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card text-white" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50 mb-1">TOTAL REVENUE</h6>
                                <h2 class="mb-0">₹<?= number_format($totalRevenue, 2) ?></h2>
                                <small class="opacity-75">All time revenue</small>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card stat-card text-white" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50 mb-1">TODAY'S REVENUE</h6>
                                <h2 class="mb-0">₹<?= number_format($todayRevenue, 2) ?></h2>
                                <small class="opacity-75">Revenue today</small>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="create_ticket.php?type=train" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-train"></i><br>
                                    <span>Train Ticket</span>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="create_ticket.php?type=flight" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-plane"></i><br>
                                    <span>Flight Ticket</span>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="create_ticket.php?type=bus" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-bus"></i><br>
                                    <span>Bus Ticket</span>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="create_ticket.php?type=cab" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-taxi"></i><br>
                                    <span>Cab Booking</span>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="create_ticket.php?type=tour" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-suitcase"></i><br>
                                    <span>Tour Package</span>
                                </a>
                            </div>
                            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                                <a href="ticket_list.php" class="btn w-100 quick-action-btn">
                                    <i class="fas fa-list"></i><br>
                                    <span>View All Tickets</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tickets and Charts -->
        <div class="row">
            <!-- Recent Tickets -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> Recent Tickets
                        </h5>
                        <a href="ticket_list.php" class="btn btn-sm btn-light">
                            <i class="fas fa-eye"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTickets)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                <h5>No tickets generated yet</h5>
                                <p class="text-muted">Create your first ticket using the quick actions above!</p>
                                <a href="create_ticket.php?type=train" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create First Ticket
                                </a>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Type</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTickets as $ticket): 
                                        $typeClass = 'ticket-' . $ticket['ticket_type'];
                                    ?>
                                    <tr onclick="location.href='view_ticket.php?id=<?= $ticket['ticket_id'] ?>'" style="cursor: pointer;">
                                        <td>
                                            <strong class="font-monospace"><?= htmlspecialchars($ticket['ticket_id']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($ticket['pnr']) ?></small>
                                        </td>
                                        <td>
                                            <span class="ticket-badge <?= $typeClass ?>">
                                                <i class="fas fa-<?= 
                                                    $ticket['ticket_type'] === 'flight' ? 'plane' :
                                                    ($ticket['ticket_type'] === 'train' ? 'train' :
                                                    ($ticket['ticket_type'] === 'bus' ? 'bus' :
                                                    ($ticket['ticket_type'] === 'cab' ? 'taxi' : 'suitcase')))
                                                ?>"></i>
                                                <?= ucfirst($ticket['ticket_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                ₹<?= number_format($ticket['total_amount'], 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($ticket['generated_at'])) ?>
                                            <br><small class="text-muted"><?= date('H:i', strtotime($ticket['generated_at'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="generate_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                   class="btn btn-outline-success" title="Download PDF">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Sidebar -->
            <div class="col-lg-4 mb-4">
                <!-- Ticket Type Distribution -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie"></i> Ticket Distribution
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($ticketsByType)): ?>
                        <div id="typeChart"></div>
                        <div class="mt-3">
                            <?php foreach ($ticketsByType as $type => $count): 
                                $percentage = $totalTickets > 0 ? round(($count / $totalTickets) * 100) : 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>
                                    <i class="fas fa-<?= 
                                        $type === 'flight' ? 'plane' :
                                        ($type === 'train' ? 'train' :
                                        ($type === 'bus' ? 'bus' :
                                        ($type === 'cab' ? 'taxi' : 'suitcase')))
                                    ?> text-primary me-2"></i>
                                    <?= ucfirst($type) ?>
                                </span>
                                <span class="fw-bold"><?= $count ?> (<?= $percentage ?>%)</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" 
                                     style="width: <?= $percentage ?>%; background-color: var(--primary-color);">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No ticket data available</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line"></i> Quick Stats
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="h4 mb-1 text-primary"><?= $monthTickets ?></div>
                                    <small class="text-muted">This Month</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="h4 mb-1 text-success"><?= $paymentStats['paid'] ?? 0 ?></div>
                                    <small class="text-muted">Paid Tickets</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 border rounded">
                                    <div class="h4 mb-1 text-warning"><?= $paymentStats['pending'] ?? 0 ?></div>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 border rounded">
                                    <div class="h4 mb-1 text-danger"><?= $paymentStats['cancelled'] ?? 0 ?></div>
                                    <small class="text-muted">Cancelled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-5 py-4 text-center border-top bg-light">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-4 text-md-start mb-3 mb-md-0">
                        <?php if ($logoUrl): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyName) ?>" height="30" class="mb-2">
                        <?php endif; ?>
                        <h6 class="mb-0"><?= htmlspecialchars($companyName) ?></h6>
                        <?php if (!empty($companyAddress)): ?>
                        <small class="text-muted d-block"><?= htmlspecialchars($companyAddress) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <small class="text-muted d-block">
                            <i class="fas fa-user-circle"></i> 
                            Logged in as: <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            <span class="badge bg-<?= $is_admin ? 'danger' : 'primary' ?> ms-1">
                                <?= $is_admin ? 'Admin' : 'Agent' ?>
                            </span>
                        </small>
                        <small class="text-muted d-block">
                            <i class="fas fa-clock"></i> Server Time: <span class="server-time"><?= date('H:i:s') ?></span>
                        </small>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <small class="text-muted d-block">
                            <i class="fas fa-ticket-alt"></i> Tickets: <?= $totalTickets ?>
                            | <i class="fas fa-rupee-sign"></i> Revenue: ₹<?= number_format($totalRevenue, 2) ?>
                        </small>
                        <small class="text-muted">
                            <?= htmlspecialchars($footerText) ?>
                        </small>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        // Auto-update time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            document.querySelectorAll('.server-time').forEach(el => {
                el.textContent = timeStr;
            });
        }
        setInterval(updateTime, 1000);
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Create pie chart for ticket distribution
        <?php if (!empty($ticketsByType)): ?>
        const typeChartData = {
            series: [<?= implode(',', array_values($ticketsByType)) ?>],
            labels: [<?= "'" . implode("','", array_map('ucfirst', array_keys($ticketsByType))) . "'" ?>],
            colors: [
                '<?= $primaryColor ?>',
                '#17a2b8',
                '#28a745',
                '#ffc107',
                '#dc3545'
            ]
        };
        
        const typeChart = new ApexCharts(document.querySelector("#typeChart"), {
            series: typeChartData.series,
            chart: {
                type: 'donut',
                height: 200,
            },
            labels: typeChartData.labels,
            colors: typeChartData.colors,
            legend: {
                show: false
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#6c757d',
                                fontSize: '14px'
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        });
        
        typeChart.render();
        <?php endif; ?>
        
        // Add hover effects to table rows
        document.querySelectorAll('tbody tr[onclick]').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>