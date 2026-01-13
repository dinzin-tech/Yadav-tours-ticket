<?php
// public/print_ticket.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ticket ID is required.");
}

$ticket_id = $_GET['id'];

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Load database
require_once APP_PATH . '/config/database.php';

try {
    $db = getDB();
    
    // Load BrandingSettings
    $branding = null;
    if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
        require_once APP_PATH . '/classes/BrandingSettings.php';
        $branding = BrandingSettings::getInstance($db);
    }
    
    // Get ticket details
    $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("Ticket not found.");
    }
    
    // Check ownership
    $user_id = $_SESSION['user_id'];
    $is_admin = ($_SESSION['role'] ?? '') === 'admin';
    
    if (!$is_admin && $ticket['generated_by'] != $user_id) {
        die("Access denied.");
    }
    
    // Decode ticket data
    if (is_string($ticket['ticket_data'])) {
        $ticket_data = json_decode($ticket['ticket_data'], true);
    } else {
        $ticket_data = $ticket['ticket_data'];
    }
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Use branding colors if available
if ($branding) {
    $primary_color = $branding->getPrimaryColor();
    $secondary_color = $branding->getSecondaryColor();
    $company_name = $branding->getCompanyName();
    $company_address = $branding->get('company_address');
    $company_phone = $branding->get('company_phone');
    $company_email = $branding->get('company_email');
} else {
    $primary_color = '#0d6efd';
    $secondary_color = '#6c757d';
    $company_name = 'Travel Agency';
    $company_address = '123 Travel Street';
    $company_phone = '+91 1234567890';
    $company_email = 'info@travelagency.com';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - <?= htmlspecialchars($ticket['ticket_id']) ?></title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                margin: 20px;
                font-family: Arial, sans-serif;
                font-size: 12px;
            }
            .no-print {
                display: none !important;
            }
        }
        .ticket-header {
            background: <?= $primary_color ?>;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .ticket-body {
            border: 2px solid <?= $primary_color ?>;
            border-radius: 10px;
            max-width: 800px;
            margin: 0 auto;
        }
        .section {
            padding: 15px;
            border-bottom: 1px dashed #ddd;
        }
        .section:last-child {
            border-bottom: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f5f5f5;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .agency-info {
            font-size: 10px;
            margin-top: 5px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="ticket-body">
        <div class="ticket-header">
            <h2 style="margin: 0;"><?= htmlspecialchars($company_name) ?></h2>
            <div class="agency-info">
                <?= htmlspecialchars($company_address) ?> | 
                Phone: <?= htmlspecialchars($company_phone) ?> | 
                Email: <?= htmlspecialchars($company_email) ?>
            </div>
        </div>
        
        <!-- Ticket content -->
        <div class="section">
            <h3>Ticket Details</h3>
            <table>
                <tr>
                    <td width="30%"><strong>Ticket ID:</strong></td>
                    <td><?= htmlspecialchars($ticket['ticket_id']) ?></td>
                </tr>
                <tr>
                    <td><strong>PNR:</strong></td>
                    <td><?= htmlspecialchars($ticket['pnr']) ?></td>
                </tr>
                <tr>
                    <td><strong>Customer:</strong></td>
                    <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                </tr>
                <tr>
                    <td><strong>Amount:</strong></td>
                    <td>₹<?= number_format($ticket['total_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td><strong>Booking Date:</strong></td>
                    <td><?= date('d/m/Y', strtotime($ticket['booking_date'])) ?></td>
                </tr>
            </table>
        </div>
        
        <?php if (!empty($ticket_data['passengers'])): ?>
        <div class="section">
            <h3>Passengers</h3>
            <table border="1">
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                </tr>
                <?php foreach ($ticket_data['passengers'] as $index => $passenger): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($passenger['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($passenger['age'] ?? '') ?></td>
                    <td><?= htmlspecialchars($passenger['gender'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h3>Terms & Conditions</h3>
            <?php if ($branding && $branding->getTermsConditions()): ?>
                <?= $branding->getTermsConditions() ?>
            <?php else: ?>
                <p>1. This is a computer generated ticket, no signature required.</p>
                <p>2. Please carry valid ID proof during journey.</p>
                <p>3. Reporting time: 30 minutes before departure.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: <?= $primary_color ?>; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Ticket
        </button>
        <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Go Back
        </button>
    </div>
    
    <script>
        window.onload = function() {
            // Auto-print when page loads
            window.print();
        };
    </script>
</body>
</html>