<?php
// public/view_ticket.php - UPDATED VERSION WITH BRANDING
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if ticket ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ticket ID is required.");
}

$ticket_identifier = $_GET['id'];

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('BASE_URL', 'http://localhost/ticket-system/public');

// Load database
require_once APP_PATH . '/config/database.php';

// Load BrandingSettings if exists
$branding = null;
$brandingEnabled = false;
if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
    require_once APP_PATH . '/classes/BrandingSettings.php';
}

try {
    $db = getDB();
    
    // Get BrandingSettings with database connection
    if (class_exists('BrandingSettings')) {
        $branding = BrandingSettings::getInstance($db);
        $brandingEnabled = true;
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Try to get ticket by ticket_id
$stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->execute([$ticket_identifier]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Ticket not found. Looking for: " . htmlspecialchars($ticket_identifier));
}

// Check if current user owns this ticket or is admin
$user_id = $_SESSION['user_id'] ?? 0;
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

if (!$is_admin && $ticket['generated_by'] != $user_id) {
    die("Access denied. You don't have permission to view this ticket.");
}

// Decode ticket data
$ticket_data = [];
if (!empty($ticket['ticket_data'])) {
    if (is_string($ticket['ticket_data'])) {
        $ticket_data = json_decode($ticket['ticket_data'], true);
    } else {
        $ticket_data = $ticket['ticket_data'];
    }
}

// Get agency info
$stmt = $db->prepare("SELECT agency_name, agency_address, agency_phone, agency_email, agency_logo FROM users WHERE id = ?");
$stmt->execute([$ticket['generated_by']]);
$agency = $stmt->fetch();

// Use branding settings if available
if ($brandingEnabled && $branding) {
    $company_name = $branding->getCompanyName();
    $company_address = $branding->get('company_address');
    $company_phone = $branding->get('company_phone');
    $company_email = $branding->get('company_email');
    $primary_color = $branding->getPrimaryColor();
    $secondary_color = $branding->getSecondaryColor();
    $logo_url = $branding->getLogoUrl();
    $favicon_url = $branding->getFaviconUrl();
} else {
    $company_name = $agency['agency_name'] ?? 'Travel Agency';
    $company_address = $agency['agency_address'] ?? '';
    $company_phone = $agency['agency_phone'] ?? '';
    $company_email = $agency['agency_email'] ?? '';
    $primary_color = '#0d6efd';
    $secondary_color = '#6c757d';
    $logo_url = null;
    $favicon_url = null;
}

// Get user statistics for this ticket
try {
    // Get total tickets by this user
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE generated_by = ?");
    $stmt->execute([$ticket['generated_by']]);
    $user_total_tickets = $stmt->fetchColumn();
    
    // Get total revenue by this user
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM tickets WHERE generated_by = ?");
    $stmt->execute([$ticket['generated_by']]);
    $user_total_revenue = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $user_total_tickets = 0;
    $user_total_revenue = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - <?= htmlspecialchars($ticket['ticket_id']) ?> - <?= htmlspecialchars($company_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if ($favicon_url): ?>
    <link rel="icon" href="<?= htmlspecialchars($favicon_url) ?>" type="image/x-icon">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;
        }
        .ticket-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 50%, black) 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
        }
        .detail-value {
            font-weight: 500;
            color: #333;
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
        .border-primary {
            border-color: var(--primary-color) !important;
        }
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--primary-color) !important;
        }
        .ticket-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
        .passenger-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .passenger-header {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .agency-logo {
            max-height: 40px;
            margin-right: 10px;
        }
        .navbar {
            background: var(--primary-color) !important;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            margin: 0 auto;
        }
        .ticket-meta {
            background: linear-gradient(135deg, var(--secondary-color) 0%, color-mix(in srgb, var(--secondary-color) 50%, black) 100%);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .action-buttons .btn {
            min-width: 120px;
        }
        .ticket-id-display {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }
        .amount-display {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-issued { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .payment-paid { background-color: #d4edda; color: #155724; }
        .payment-pending { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <?php if ($logo_url): ?>
                <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($company_name) ?>" class="agency-logo">
                <?php endif; ?>
                <i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($company_name) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_ticket.php"><i class="fas fa-plus-circle"></i> Create Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ticket_list.php"><i class="fas fa-list"></i> All Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-eye"></i> View Ticket</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link text-white">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        <?php if ($is_admin): ?>
                        <span class="badge bg-danger ms-1">Admin</span>
                        <?php endif; ?>
                    </span>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">
                        <i class="fas fa-ticket-alt"></i> 
                        Ticket #<span class="ticket-id-display"><?= htmlspecialchars($ticket['ticket_id']) ?></span>
                    </h2>
                    <p class="mb-0">
                        <span class="badge bg-light text-dark me-2">
                            <i class="fas fa-tag"></i> <?= ucfirst($ticket['ticket_type']) ?>
                        </span>
                        <span class="status-badge status-<?= $ticket['ticket_status'] ?> me-2">
                            <?= ucfirst($ticket['ticket_status']) ?>
                        </span>
                        <span class="status-badge payment-<?= $ticket['payment_status'] ?>">
                            <i class="fas fa-<?= $ticket['payment_status'] == 'paid' ? 'check-circle' : 'clock' ?>"></i>
                            <?= ucfirst($ticket['payment_status']) ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="amount-display">₹<?= number_format($ticket['total_amount'], 2) ?></div>
                    <small>Total Amount</small>
                </div>
            </div>
        </div>

        <!-- Ticket Meta -->
        <div class="ticket-meta">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                        <div><?= date('d/m/Y', strtotime($ticket['booking_date'])) ?></div>
                        <small class="opacity-75">Booking Date</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <div><?= date('d/m/Y H:i', strtotime($ticket['generated_at'])) ?></div>
                        <small class="opacity-75">Generated On</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <i class="fas fa-user-tie fa-2x mb-2"></i>
                        <div><?= htmlspecialchars($agency['agency_name'] ?? 'Travel Agency') ?></div>
                        <small class="opacity-75">Agency</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="qr-code">
                            <div class="text-center">
                                <i class="fas fa-qrcode fa-3x text-muted"></i>
                                <div class="small mt-2">QR Code</div>
                            </div>
                        </div>
                        <small class="opacity-75">Scan to verify</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                  <div class="action-buttons d-flex flex-wrap gap-2">
                    <a href="ticket_list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="generate_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                    class="btn btn-success" target="_blank">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <?php if ($is_admin || $ticket['generated_by'] == $user_id): ?>
                    <a href="edit_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                    class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Ticket
                    </a>
                    <button type="button" class="btn btn-danger" 
                            onclick="confirmDelete('<?= $ticket['ticket_id'] ?>')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="detail-card">
                    <div class="passenger-header">
                        <h5><i class="fas fa-user"></i> Customer Details</h5>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Name:</div>
                        <div class="col-8 detail-value">
                            <strong><?= htmlspecialchars($ticket['customer_name']) ?></strong>
                        </div>
                    </div>
                    <?php if (!empty($ticket['customer_email'])): ?>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Email:</div>
                        <div class="col-8 detail-value">
                            <a href="mailto:<?= htmlspecialchars($ticket['customer_email']) ?>" class="text-decoration-none">
                                <i class="fas fa-envelope me-1"></i>
                                <?= htmlspecialchars($ticket['customer_email']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ticket['customer_phone'])): ?>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Phone:</div>
                        <div class="col-8 detail-value">
                            <a href="tel:<?= htmlspecialchars($ticket['customer_phone']) ?>" class="text-decoration-none">
                                <i class="fas fa-phone me-1"></i>
                                <?= htmlspecialchars($ticket['customer_phone']) ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Booking Date:</div>
                        <div class="col-8 detail-value">
                            <i class="fas fa-calendar-day me-1"></i>
                            <?= date('d/m/Y', strtotime($ticket['booking_date'])) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 detail-label">PNR/Reference:</div>
                        <div class="col-8 detail-value">
                            <code class="bg-light p-1 rounded"><?= htmlspecialchars($ticket['pnr']) ?></code>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-card">
                    <div class="passenger-header">
                        <h5><i class="fas fa-info-circle"></i> Ticket Information</h5>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Ticket ID:</div>
                        <div class="col-8 detail-value">
                            <code class="bg-light p-1 rounded"><?= htmlspecialchars($ticket['ticket_id']) ?></code>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Type:</div>
                        <div class="col-8 detail-value">
                            <span class="ticket-badge ticket-<?= $ticket['ticket_type'] ?>">
                                <i class="fas fa-<?= 
                                    $ticket['ticket_type'] === 'flight' ? 'plane' :
                                    ($ticket['ticket_type'] === 'train' ? 'train' :
                                    ($ticket['ticket_type'] === 'bus' ? 'bus' :
                                    ($ticket['ticket_type'] === 'cab' ? 'taxi' : 'suitcase')))
                                ?>"></i>
                                <?= ucfirst($ticket['ticket_type']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Status:</div>
                        <div class="col-8 detail-value">
                            <span class="status-badge status-<?= $ticket['ticket_status'] ?>">
                                <?= ucfirst($ticket['ticket_status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 detail-label">Payment Status:</div>
                        <div class="col-8 detail-value">
                            <span class="status-badge payment-<?= $ticket['payment_status'] ?>">
                                <i class="fas fa-<?= $ticket['payment_status'] == 'paid' ? 'check-circle' : 'clock' ?>"></i>
                                <?= ucfirst($ticket['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 detail-label">Created On:</div>
                        <div class="col-8 detail-value">
                            <i class="fas fa-clock me-1"></i>
                            <?= date('d/m/Y H:i:s', strtotime($ticket['generated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Type Specific Details -->
        <?php if ($ticket['ticket_type'] == 'train'): ?>
        <div class="detail-card">
            <div class="passenger-header">
                <h5><i class="fas fa-train text-primary"></i> Train Details</h5>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Train Number</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['train_number'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Train Name</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['train_name'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Class</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['class'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">From Station</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['from_station'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">To Station</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['to_station'] ?? 'N/A') ?></div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Departure</div>
                    <div class="detail-value">
                        <?= !empty($ticket_data['departure_datetime']) ? 
                            date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : 'N/A' ?>
                    </div>
                </div>
                <?php if (!empty($ticket_data['arrival_datetime'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Arrival</div>
                    <div class="detail-value">
                        <?= date('d/m/Y H:i', strtotime($ticket_data['arrival_datetime'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ticket_data['coach'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Coach</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['coach']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ticket_data['seat'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Seat/Berth</div>
                    <div class="detail-value"><?= htmlspecialchars($ticket_data['seat']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Passengers Section -->
        <?php if (!empty($ticket_data['passengers'])): ?>
        <div class="detail-card">
            <div class="passenger-header">
                <h5><i class="fas fa-users"></i> Passengers (<?= count($ticket_data['passengers']) ?>)</h5>
            </div>
            <div class="row">
                <?php foreach ($ticket_data['passengers'] as $index => $passenger): ?>
                <div class="col-md-6 mb-3">
                    <div class="passenger-card">
                        <h6 class="mb-3">
                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                            Passenger Details
                        </h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?= htmlspecialchars($passenger['name'] ?? '') ?></div>
                            </div>
                            <div class="col-3">
                                <div class="detail-label">Age:</div>
                                <div class="detail-value"><?= htmlspecialchars($passenger['age'] ?? '') ?></div>
                            </div>
                            <div class="col-3">
                                <div class="detail-label">Gender:</div>
                                <div class="detail-value"><?= htmlspecialchars($passenger['gender'] ?? '') ?></div>
                            </div>
                        </div>
                        <?php if (!empty($passenger['id_type']) || !empty($passenger['id_number'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="detail-label">ID Proof:</div>
                                <div class="detail-value">
                                    <?= htmlspecialchars($passenger['id_type'] ?? '') ?>: 
                                    <code><?= htmlspecialchars($passenger['id_number'] ?? '') ?></code>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($passenger['seat']) || !empty($passenger['coach'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="detail-label">Seat Details:</div>
                                <div class="detail-value">
                                    <?php if (!empty($passenger['coach'])): ?>
                                    <span class="badge bg-light text-dark me-2">Coach: <?= htmlspecialchars($passenger['coach']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($passenger['seat'])): ?>
                                    <span class="badge bg-light text-dark">Seat: <?= htmlspecialchars($passenger['seat']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Fare Details -->
        <div class="detail-card">
            <div class="passenger-header">
                <h5><i class="fas fa-rupee-sign"></i> Fare Details</h5>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="detail-label">Base Fare</div>
                    <div class="detail-value">₹<?= number_format($ticket_data['base_fare'] ?? 0, 2) ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="detail-label">Tax Amount</div>
                    <div class="detail-value">₹<?= number_format($ticket_data['tax_amount'] ?? 0, 2) ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="detail-label">Service Charge</div>
                    <div class="detail-value">₹<?= number_format($ticket_data['service_charge'] ?? 0, 2) ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value fw-bold text-success">₹<?= number_format($ticket['total_amount'], 2) ?></div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: 70%;" role="progressbar"></div>
                        <div class="progress-bar bg-info" style="width: 20%;" role="progressbar"></div>
                        <div class="progress-bar bg-warning" style="width: 10%;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Base Fare</small>
                        <small class="text-muted">Taxes</small>
                        <small class="text-muted">Service</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agency Details -->
        <div class="detail-card">
            <div class="passenger-header">
                <h5><i class="fas fa-building"></i> Agency Details</h5>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Agency Name</div>
                    <div class="detail-value">
                        <i class="fas fa-building me-1"></i>
                        <?= htmlspecialchars($company_name) ?>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Contact</div>
                    <div class="detail-value">
                        <i class="fas fa-phone me-1"></i>
                        <a href="tel:<?= htmlspecialchars($company_phone) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($company_phone) ?>
                        </a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">
                        <i class="fas fa-envelope me-1"></i>
                        <a href="mailto:<?= htmlspecialchars($company_email) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($company_email) ?>
                        </a>
                    </div>
                </div>
                <?php if (!empty($company_address)): ?>
                <div class="col-12 mb-3">
                    <div class="detail-label">Address</div>
                    <div class="detail-value">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= nl2br(htmlspecialchars($company_address)) ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="detail-label">Agent Statistics</div>
                    <div class="detail-value">
                        <span class="badge bg-light text-dark me-2">
                            <i class="fas fa-ticket-alt"></i> <?= $user_total_tickets ?> Total Tickets
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-rupee-sign"></i> ₹<?= number_format($user_total_revenue, 2) ?> Revenue
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="detail-card">
            <div class="passenger-header">
                <h5><i class="fas fa-file-alt"></i> Additional Information</h5>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="detail-label">Terms & Conditions</div>
                        <div class="detail-value small">
                            <?php if ($brandingEnabled && $branding && $branding->getTermsConditions()): ?>
                                <?= $branding->getTermsConditions() ?>
                            <?php else: ?>
                                <ul class="mb-0">
                                    <li>This is a computer generated ticket, no signature required.</li>
                                    <li>Please carry valid ID proof during journey.</li>
                                    <li>Reporting time: 30 minutes before departure.</li>
                                    <li>Cancellation and refund as per service provider rules.</li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="detail-label">Important Notes</div>
                        <div class="detail-value small">
                            <ul class="mb-0">
                                <li>Ticket is valid only for the date and journey mentioned.</li>
                                <li>Please check all details before journey.</li>
                                <li>Keep this ticket safe for future reference.</li>
                                <li>For assistance, contact the agency directly.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-3">Need to modify this ticket?</h6>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="create_ticket.php?type=<?= $ticket['ticket_type'] ?>&duplicate=<?= $ticket['ticket_id'] ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-copy"></i> Duplicate Ticket
                            </a>
                            <a href="send_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                               class="btn btn-outline-success">
                                <i class="fas fa-share-alt"></i> Share Ticket
                            </a>
                            <a href="history.php?ticket_id=<?= $ticket['ticket_id'] ?>" 
                               class="btn btn-outline-info">
                                <i class="fas fa-history"></i> View History
                            </a>
                        </div>
                    </div>