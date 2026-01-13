<?php
// public/profile.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Define constants
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
    define('APP_PATH', BASE_PATH . '/app');
}

// Load database
require_once APP_PATH . '/config/database.php';

// Create database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Get user details
$user_id = $_SESSION['user_id'];
try {
    $stmt = $db->prepare("
        SELECT id, username, email, full_name, role, agency_name, 
               agency_address, agency_phone, agency_email, 
               created_at, last_login 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("User not found.");
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get user statistics
try {
    // Total tickets
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE generated_by = ?");
    $stmt->execute([$user_id]);
    $total_tickets = $stmt->fetchColumn();
    
    // This month tickets
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM tickets 
        WHERE generated_by = ? AND MONTH(generated_at) = MONTH(CURDATE())
    ");
    $stmt->execute([$user_id]);
    $month_tickets = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM tickets WHERE generated_by = ?");
    $stmt->execute([$user_id]);
    $total_revenue = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $total_tickets = $month_tickets = $total_revenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }
        .stat-card-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card-success { background: linear-gradient(135deg, #42b883 0%, #347474 100%); }
        .stat-card-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
        .detail-row {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-ticket-alt"></i> Ticket System
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
                        <a class="nav-link active" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link text-white">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-user-circle fa-2x me-3"></i>
                        <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>
                    </h1>
                    <p class="mb-0">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?> | 
                        <i class="fas fa-user-tag"></i> <?= ucfirst($user['role']) ?> |
                        <i class="fas fa-calendar-alt"></i> Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light me-2" onclick="editProfile()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="btn btn-warning" onclick="changePassword()">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card stat-card-primary">
                    <h3 class="mb-2"><?= $total_tickets ?></h3>
                    <p class="mb-0">Total Tickets</p>
                    <small>All time tickets generated</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-card-success">
                    <h3 class="mb-2"><?= $month_tickets ?></h3>
                    <p class="mb-0">This Month</p>
                    <small>Tickets this month</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-card-warning">
                    <h3 class="mb-2">₹<?= number_format($total_revenue, 2) ?></h3>
                    <p class="mb-0">Total Revenue</p>
                    <small>All time revenue</small>
                </div>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="profile-card">
            <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" 
                            data-bs-target="#personal" type="button">
                        <i class="fas fa-user"></i> Personal Info
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="agency-tab" data-bs-toggle="tab" 
                            data-bs-target="#agency" type="button">
                        <i class="fas fa-building"></i> Agency Info
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" 
                            data-bs-target="#activity" type="button">
                        <i class="fas fa-chart-line"></i> Activity
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" 
                            data-bs-target="#settings" type="button">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabContent">
                <!-- Personal Info Tab -->
                <div class="tab-pane fade show active" id="personal">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Username</div>
                                <div class="detail-value"><?= htmlspecialchars($user['username']) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Full Name</div>
                                <div class="detail-value"><?= htmlspecialchars($user['full_name'] ?? 'Not set') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Email Address</div>
                                <div class="detail-value">
                                    <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Role</div>
                                <div class="detail-value">
                                    <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'primary' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Account Created</div>
                                <div class="detail-value"><?= date('d/m/Y H:i:s', strtotime($user['created_at'])) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Last Login</div>
                                <div class="detail-value">
                                    <?= $user['last_login'] ? date('d/m/Y H:i:s', strtotime($user['last_login'])) : 'Never' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agency Info Tab -->
                <div class="tab-pane fade" id="agency">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Agency Name</div>
                                <div class="detail-value"><?= htmlspecialchars($user['agency_name'] ?? 'Not set') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Agency Email</div>
                                <div class="detail-value">
                                    <?= !empty($user['agency_email']) ? 
                                        '<a href="mailto:' . htmlspecialchars($user['agency_email']) . '">' . 
                                        htmlspecialchars($user['agency_email']) . '</a>' : 'Not set' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-row">
                                <div class="detail-label">Agency Phone</div>
                                <div class="detail-value">
                                    <?= !empty($user['agency_phone']) ? 
                                        '<a href="tel:' . htmlspecialchars($user['agency_phone']) . '">' . 
                                        htmlspecialchars($user['agency_phone']) . '</a>' : 'Not set' ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Agency Address</div>
                                <div class="detail-value"><?= nl2br(htmlspecialchars($user['agency_address'] ?? 'Not set')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Tab -->
                <div class="tab-pane fade" id="activity">
                    <h6 class="mb-3">Recent Activity</h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Activity logging will be implemented in the next version.
                    </div>
                    <p>Planned features:</p>
                    <ul>
                        <li>Recent ticket generation history</li>
                        <li>Login history</li>
                        <li>Profile update history</li>
                        <li>Export activity report</li>
                    </ul>
                </div>

                <!-- Settings Tab -->
                <div class="tab-pane fade" id="settings">
                    <h6 class="mb-3">Account Settings</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email Notifications</label>
                                <select class="form-select">
                                    <option>Enabled</option>
                                    <option>Disabled</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Timezone</label>
                                <select class="form-select">
                                    <option>Asia/Kolkata (IST)</option>
                                    <option>UTC</option>
                                    <option>America/New_York</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date Format</label>
                                <select class="form-select">
                                    <option>DD/MM/YYYY</option>
                                    <option>MM/DD/YYYY</option>
                                    <option>YYYY-MM-DD</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Items Per Page</label>
                                <select class="form-select">
                                    <option>10</option>
                                    <option selected>20</option>
                                    <option>50</option>
                                    <option>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Tickets -->
        <div class="profile-card">
            <h5 class="mb-3"><i class="fas fa-history"></i> Recent Tickets</h5>
            <?php
            try {
                $stmt = $db->prepare("
                    SELECT ticket_id, ticket_type, customer_name, total_amount, generated_at 
                    FROM tickets 
                    WHERE generated_by = ? 
                    ORDER BY generated_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $recent_tickets = $stmt->fetchAll();
                
                if (empty($recent_tickets)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-ticket-alt fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No tickets generated yet</p>
                        <a href="create_ticket.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Create Your First Ticket
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_tickets as $ticket): ?>
                        <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <div>
                                    <strong><?= htmlspecialchars($ticket['customer_name']) ?></strong>
                                    <span class="badge bg-<?= 
                                        $ticket['ticket_type'] == 'train' ? 'primary' :
                                        ($ticket['ticket_type'] == 'flight' ? 'info' :
                                        ($ticket['ticket_type'] == 'bus' ? 'success' :
                                        ($ticket['ticket_type'] == 'cab' ? 'warning' : 'danger')))
                                    ?> ms-2">
                                        <?= ucfirst($ticket['ticket_type']) ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">₹<?= number_format($ticket['total_amount'], 2) ?></div>
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($ticket['generated_at'])) ?></small>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="ticket_list.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list"></i> View All Tickets
                        </a>
                    </div>
                <?php endif;
                
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Failed to load recent tickets.</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProfile() {
            alert('Edit profile feature will be implemented soon!');
            // Will open modal with edit form
        }

        function changePassword() {
            alert('Change password feature will be implemented soon!');
            // Will open modal for password change
        }

        function saveSettings() {
            alert('Settings saved successfully!');
            // Will save settings via AJAX
        }
    </script>
</body>
</html>