<?php
// admin/terms_conditions.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
require_once APP_PATH . '/config/database.php';

try {
    $db = getDB();
    
    // Load branding settings if exists
    $branding = null;
    if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
        require_once APP_PATH . '/classes/BrandingSettings.php';
        if (class_exists('BrandingSettings')) {
            $branding = BrandingSettings::getInstance($db);
        }
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $terms = [];
        foreach (['train', 'flight', 'bus', 'cab', 'tour'] as $type) {
            $terms[$type] = $_POST["terms_{$type}"] ?? '';
        }
        
        if ($branding) {
            $branding->set('terms_conditions', json_encode($terms));
            $success = "Terms & Conditions updated successfully!";
        } else {
            // Store in database table
            $json_terms = json_encode($terms);
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('terms_conditions', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$json_terms, $json_terms]);
            $success = "Terms & Conditions saved!";
        }
    }
    
    // Get current terms
    $current_terms = [
        'train' => '',
        'flight' => '',
        'bus' => '',
        'cab' => '',
        'tour' => ''
    ];
    
    if ($branding) {
        $terms_json = $branding->get('terms_conditions');
    } else {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'terms_conditions'");
        $stmt->execute();
        $terms_json = $stmt->fetchColumn();
    }
    
    if ($terms_json) {
        $current_terms = array_merge($current_terms, json_decode($terms_json, true));
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Terms & Conditions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .terms-tab { padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; }
        .nav-tabs .nav-link.active { font-weight: bold; border-bottom: 3px solid #0d6efd; }
        .preview-box { background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <?php include '../includes/admin_nav.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-file-contract"></i> Manage Terms & Conditions</h4>
                <p class="mb-0">Set different terms for each ticket type</p>
            </div>
            
            <div class="card-body">
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <ul class="nav nav-tabs" id="termsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="train-tab" data-bs-toggle="tab" data-bs-target="#train" type="button">
                                <i class="fas fa-train"></i> Train
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="flight-tab" data-bs-toggle="tab" data-bs-target="#flight" type="button">
                                <i class="fas fa-plane"></i> Flight
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="bus-tab" data-bs-toggle="tab" data-bs-target="#bus" type="button">
                                <i class="fas fa-bus"></i> Bus
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cab-tab" data-bs-toggle="tab" data-bs-target="#cab" type="button">
                                <i class="fas fa-taxi"></i> Cab
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tour-tab" data-bs-toggle="tab" data-bs-target="#tour" type="button">
                                <i class="fas fa-suitcase"></i> Tour
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content terms-tab">
                        <!-- Train Terms -->
                        <div class="tab-pane fade show active" id="train" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Train Ticket Terms & Conditions</label>
                                <textarea class="form-control" name="terms_train" rows="10"><?= htmlspecialchars($current_terms['train']) ?></textarea>
                            </div>
                            <div class="preview-box">
                                <h6>Sample Content:</h6>
                                <p>• PNR Number is mandatory for ticket verification</p>
                                <p>• Report to station 30 minutes before departure</p>
                                <p>• Carry valid government ID proof</p>
                                <p>• No refund for missed trains</p>
                                <p>• E-ticket valid with ID proof only</p>
                            </div>
                        </div>
                        
                        <!-- Flight Terms -->
                        <div class="tab-pane fade" id="flight" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Flight Ticket Terms & Conditions</label>
                                <textarea class="form-control" name="terms_flight" rows="10"><?= htmlspecialchars($current_terms['flight']) ?></textarea>
                            </div>
                            <div class="preview-box">
                                <h6>Sample Content:</h6>
                                <p>• PNR/Booking Reference mandatory for check-in</p>
                                <p>• Check-in 2 hours before domestic, 3 hours before international flights</p>
                                <p>• Passport required for international flights</p>
                                <p>• No-show charges apply</p>
                                <p>• Baggage allowance as per airline policy</p>
                            </div>
                        </div>
                        
                        <!-- Bus Terms -->
                        <div class="tab-pane fade" id="bus" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Bus Ticket Terms & Conditions</label>
                                <textarea class="form-control" name="terms_bus" rows="10"><?= htmlspecialchars($current_terms['bus']) ?></textarea>
                            </div>
                            <div class="preview-box">
                                <h6>Sample Content:</h6>
                                <p>• Operator Name and PNR mandatory for boarding</p>
                                <p>• Report to boarding point 30 minutes before departure</p>
                                <p>• No seat selection guaranteed</p>
                                <p>• Contact Bus Manager for any assistance</p>
                                <p>• Cancellation charges apply as per operator policy</p>
                            </div>
                        </div>
                        
                        <!-- Cab Terms -->
                        <div class="tab-pane fade" id="cab" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Cab Booking Terms & Conditions</label>
                                <textarea class="form-control" name="terms_cab" rows="10"><?= htmlspecialchars($current_terms['cab']) ?></textarea>
                            </div>
                            <div class="preview-box">
                                <h6>Sample Content:</h6>
                                <p>• Driver contact number will be shared 1 hour before pickup</p>
                                <p>• Wait time: 15 minutes free, then charges apply</p>
                                <p>• Night charges extra (10PM to 6AM)</p>
                                <p>• Toll charges extra</p>
                                <p>• Cancellation free before 2 hours</p>
                            </div>
                        </div>
                        
                        <!-- Tour Terms -->
                        <div class="tab-pane fade" id="tour" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Tour Package Terms & Conditions</label>
                                <textarea class="form-control" name="terms_tour" rows="10"><?= htmlspecialchars($current_terms['tour']) ?></textarea>
                            </div>
                            <div class="preview-box">
                                <h6>Sample Content:</h6>
                                <p>• Tour Coordinator contact number provided at booking</p>
                                <p>• Package inclusions as mentioned</p>
                                <p>• No refund for unused services</p>
                                <p>• Hotel check-in: 2PM, check-out: 12PM</p>
                                <p>• Emergency contact: [Coordinator Number]</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save All Terms & Conditions
                        </button>
                        <a href="../dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>