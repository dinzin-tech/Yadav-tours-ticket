<?php
// public/create_ticket.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$airlines = require_once __DIR__ . '/../app/config/airlines.php';

$airline_data = [];

foreach ($airlines as $code => $airline) {
    $airline_data[] = [
        'id' => $code,
        'text' => $airline->name
    ];
}

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('BASE_URL', 'http://localhost/ticket-system/public');

// Load database
require_once APP_PATH . '/config/database.php';

// Get ticket type from URL
$ticket_type = isset($_GET['type']) ? $_GET['type'] : 'train';
$allowed_types = ['train', 'flight', 'bus', 'cab', 'tour'];
if (!in_array($ticket_type, $allowed_types)) {
    $ticket_type = 'train';
}

// Set page title based on type
$page_titles = [
    'train' => 'Train Ticket',
    'flight' => 'Flight Ticket', 
    'bus' => 'Bus Ticket',
    'cab' => 'Cab Booking',
    'tour' => 'Tour Package'
];
$page_title = $page_titles[$ticket_type];

// Handle form submission
$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $form_data = $_POST;
    $form_data['ticket_type'] = $ticket_type;
    
    // Set booking date
    $form_data['booking_date'] = date('Y-m-d');
    
    // Calculate total amount
    $base_fare = floatval($form_data['base_fare'] ?? 0);
    $tax_percent = floatval($form_data['tax_percent'] ?? 18);
    $tax_amount = ($base_fare * $tax_percent) / 100;
    $service_charge = floatval($form_data['service_charge'] ?? 0);
    $form_data['total_amount'] = $base_fare + $tax_amount + $service_charge;
    $form_data['tax_amount'] = $tax_amount;
    
    // Basic validation
    if (empty($form_data['customer_name'])) {
        $errors[] = 'Customer name is required';
    }
    
    if (empty($form_data['total_amount']) || $form_data['total_amount'] <= 0) {
        $errors[] = 'Valid total amount is required';
    }
    
    // For tickets with PNR
    if (in_array($ticket_type, ['train', 'flight', 'bus'])) {
        if (empty($form_data['pnr'])) {
            $errors[] = 'PNR is required';
        }
    }
    
    if (empty($errors)) {
    try {
        // Create database connection
        $db = getDB();
        
        // Check if BrandingSettings exists
        $brandingEnabled = false;
        $branding = null;
        
        if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
            require_once APP_PATH . '/classes/BrandingSettings.php';
            if (class_exists('BrandingSettings')) {
                $branding = BrandingSettings::getInstance($db);
                $brandingEnabled = true;
            }
        }
        
        // Generate ticket ID
        $ticket_id = 'T' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Prepare passengers array
        $passengers = [];
        if (isset($form_data['passenger_name']) && is_array($form_data['passenger_name'])) {
            $count = count($form_data['passenger_name']);
            for ($i = 0; $i < $count; $i++) {
                $passenger = [
                    'name' => $form_data['passenger_name'][$i] ?? '',
                    'age' => $form_data['passenger_age'][$i] ?? '',
                    'gender' => $form_data['passenger_gender'][$i] ?? '',
                    'id_type' => $form_data['passenger_id_type'][$i] ?? '',
                    'id_number' => $form_data['passenger_id_number'][$i] ?? '',
                    'seat' => $form_data['passenger_seat'][$i] ?? '',
                    'coach' => $form_data['passenger_coach'][$i] ?? ''
                ];
                $passengers[] = $passenger;
            }
            $form_data['passengers'] = $passengers;
        }
        
        // Prepare ticket data JSON
        $form_data['generated_by'] = $_SESSION['username'] ?? 'admin';
        $form_data['generated_at'] = date('Y-m-d H:i:s');
        $form_data['ticket_id'] = $ticket_id;
        
        // Add branding info to ticket data if available
        if ($brandingEnabled && $branding) {
            $form_data['branding_info'] = [
                'company_name' => $branding->getCompanyName(),
                'primary_color' => $branding->getPrimaryColor(),
                'secondary_color' => $branding->getSecondaryColor(),
                'company_address' => $branding->get('company_address'),
                'company_phone' => $branding->get('company_phone'),
                'company_email' => $branding->get('company_email')
            ];
        }
        
        // Convert to JSON
        $ticket_data_json = json_encode($form_data, JSON_UNESCAPED_UNICODE);
        
        // Insert into database
        $stmt = $db->prepare("
            INSERT INTO tickets (
                ticket_id, ticket_type, pnr, booking_date, 
                customer_name, customer_email, customer_phone,
                total_amount, ticket_data, generated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $ticket_id,
            $ticket_type,
            $form_data['pnr'] ?? 'N/A',
            $form_data['booking_date'],
            $form_data['customer_name'],
            $form_data['customer_email'] ?? null,
            $form_data['customer_phone'] ?? null,
            $form_data['total_amount'],
            $ticket_data_json,
            $_SESSION['user_id'] ?? 1
        ]);
        
        // Success - redirect to view ticket
        header('Location: view_ticket.php?id=' . $ticket_id);
        exit();
        
    } catch (Exception $e) {
        $errors[] = 'Error saving ticket: ' . $e->getMessage();
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create <?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f5f7fb;
        }
        .ticket-form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
            padding: 30px;
            margin-top: 20px;
        }
        .form-section {
            border-left: 4px solid #0d6efd;
            padding-left: 15px;
            margin-bottom: 30px;
        }
        .passenger-row {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .type-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
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
                        <a class="nav-link active" href="#"><i class="fas fa-plus-circle"></i> Create Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ticket_list.php"><i class="fas fa-list"></i> All Tickets</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link text-white">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Ticket Type Tabs -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Select Ticket Type</h5>
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $ticket_type == 'train' ? 'active' : '' ?>" 
                           href="?type=train">
                            <i class="fas fa-train type-icon"></i> Train
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $ticket_type == 'flight' ? 'active' : '' ?>" 
                           href="?type=flight">
                            <i class="fas fa-plane type-icon"></i> Flight
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $ticket_type == 'bus' ? 'active' : '' ?>" 
                           href="?type=bus">
                            <i class="fas fa-bus type-icon"></i> Bus
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $ticket_type == 'cab' ? 'active' : '' ?>" 
                           href="?type=cab">
                            <i class="fas fa-taxi type-icon"></i> Cab
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $ticket_type == 'tour' ? 'active' : '' ?>" 
                           href="?type=tour">
                            <i class="fas fa-suitcase type-icon"></i> Tour
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Ticket Form -->
        <div class="ticket-form-container">
            <h3 class="mb-4">
                <i class="fas fa-<?= 
                    $ticket_type == 'flight' ? 'plane' :
                    ($ticket_type == 'train' ? 'train' :
                    ($ticket_type == 'bus' ? 'bus' :
                    ($ticket_type == 'cab' ? 'taxi' : 'suitcase')))
                ?> text-primary"></i>
                Create <?= htmlspecialchars($page_title) ?>
            </h3>

            <form method="POST" action="" id="ticketForm">
                <input type="hidden" name="ticket_type" value="<?= $ticket_type ?>">
                
                <!-- Common Fields -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" class="form-control" name="customer_name" 
                               value="<?= htmlspecialchars($form_data['customer_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Email</label>
                        <input type="email" class="form-control" name="customer_email" 
                               value="<?= htmlspecialchars($form_data['customer_email'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Phone</label>
                        <input type="tel" class="form-control" name="customer_phone" 
                               value="<?= htmlspecialchars($form_data['customer_phone'] ?? '') ?>">
                    </div>
                </div>

                <?php if ($ticket_type == 'train'): ?>
                <!-- Train Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-train"></i> Train Details</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">PNR Number *</label>
                            <input type="text" class="form-control" name="pnr" 
                                value="<?= htmlspecialchars($form_data['pnr'] ?? '') ?>" required>
                            <small class="text-muted">10-digit PNR</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Train Number *</label>
                            <input type="text" class="form-control" name="train_number" 
                                value="<?= htmlspecialchars($form_data['train_number'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Train Name *</label>
                            <input type="text" class="form-control" name="train_name" 
                                value="<?= htmlspecialchars($form_data['train_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Class/Type *</label>
                            <select class="form-select" name="class" required>
                                <option value="AC First Class">AC First Class</option>
                                <option value="AC 2 Tier">AC 2 Tier</option>
                                <option value="AC 3 Tier">AC 3 Tier</option>
                                <option value="Sleeper">Sleeper (Non-AC)</option>
                                <option value="Second Sitting">Second Sitting</option>
                                <option value="AC Chair Car">AC Chair Car</option>
                                <option value="Executive Chair Car">Executive Chair Car</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">From Station *</label>
                            <input type="text" class="form-control" name="from_station" 
                                   value="<?= htmlspecialchars($form_data['from_station'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Station *</label>
                            <input type="text" class="form-control" name="to_station" 
                                   value="<?= htmlspecialchars($form_data['to_station'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Departure Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($form_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arrival Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($form_data['arrival_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coach</label>
                            <input type="text" class="form-control" name="coach" 
                                   value="<?= htmlspecialchars($form_data['coach'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Seat/Berth</label>
                            <input type="text" class="form-control" name="seat" 
                                   value="<?= htmlspecialchars($form_data['seat'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'flight'): ?>
                <!-- Flight Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-plane"></i> Flight Details</h5>
                    <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">PNR/Booking Code *</label>
                        <input type="text" class="form-control" name="pnr" 
                            value="<?= htmlspecialchars($form_data['pnr'] ?? '') ?>" required>
                        <small class="text-muted">6-character code</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Flight Number *</label>
                        <input type="text" class="form-control" name="flight_number" 
                            value="<?= htmlspecialchars($form_data['flight_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Airline Name *</label>
                        <!--<input type="text" class="form-control" name="airline" 
                            value="<?= htmlspecialchars($form_data['airline'] ?? '') ?>" 
                            placeholder="e.g., IndiGo, Air India, SpiceJet" required> -->
                        <select name="airline" id="airline_select" required class="form-select">

                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class *</label>
                        <select class="form-select" name="class" required>
                            <option value="Economy">Economy</option>
                            <option value="Premium Economy">Premium Economy</option>
                            <option value="Business">Business Class</option>
                            <option value="First Class">First Class</option>
                        </select>
                    </div>
                </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">From Airport *</label>
                            <input type="text" class="form-control" name="from_airport" 
                                   value="<?= htmlspecialchars($form_data['from_airport'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Airport *</label>
                            <input type="text" class="form-control" name="to_airport" 
                                   value="<?= htmlspecialchars($form_data['to_airport'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Departure *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($form_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Arrival *</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($form_data['arrival_datetime'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Seat Number</label>
                            <input type="text" class="form-control" name="seat" 
                                   value="<?= htmlspecialchars($form_data['seat'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gate</label>
                            <input type="text" class="form-control" name="gate" 
                                   value="<?= htmlspecialchars($form_data['gate'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Terminal</label>
                            <input type="text" class="form-control" name="terminal" 
                                   value="<?= htmlspecialchars($form_data['terminal'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'bus'): ?>
                <!-- Bus Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-bus"></i> Bus Details</h5>
                    <div class="row">
                <div class="col-md-3">
                    <label class="form-label">PNR/Ticket No *</label>
                    <input type="text" class="form-control" name="pnr" 
                        value="<?= htmlspecialchars($form_data['pnr'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operator Name *</label>
                    <input type="text" class="form-control" name="operator" 
                        value="<?= htmlspecialchars($form_data['operator'] ?? '') ?>" 
                        placeholder="e.g., VRL Travels, SRS Travels" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bus Type *</label>
                                <select class="form-select" name="bus_type" required>
                                    <option value="AC Sleeper">AC Sleeper</option>
                                    <option value="Non-AC Sleeper">Non-AC Sleeper</option>
                                    <option value="AC Seater">AC Seater</option>
                                    <option value="Non-AC Seater">Non-AC Seater</option>
                                    <option value="Volvo AC">Volvo AC</option>
                                    <option value="Volvo Multi-Axle">Volvo Multi-Axle</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bus Manager Contact</label>
                                <input type="tel" class="form-control" name="bus_manager_contact" 
                                    value="<?= htmlspecialchars($form_data['bus_manager_contact'] ?? '') ?>"
                                    placeholder="+91XXXXXXXXXX">
                            </div>
                        </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Boarding Point Address *</label>
                    <textarea class="form-control" name="boarding_address" rows="2" required><?= htmlspecialchars($form_data['boarding_address'] ?? '') ?></textarea>
                    <small class="text-muted">Full address with landmark</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Drop Point Address *</label>
                    <textarea class="form-control" name="drop_address" rows="2" required><?= htmlspecialchars($form_data['drop_address'] ?? '') ?></textarea>
                    <small class="text-muted">Full address with landmark</small>
                </div>
            </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Pickup Point *</label>
                            <input type="text" class="form-control" name="pickup_point" 
                                   value="<?= htmlspecialchars($form_data['pickup_point'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Drop Point *</label>
                            <input type="text" class="form-control" name="drop_point" 
                                   value="<?= htmlspecialchars($form_data['drop_point'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reporting Time</label>
                            <input type="time" class="form-control" name="reporting_time" 
                                   value="<?= htmlspecialchars($form_data['reporting_time'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Departure *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($form_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arrival *</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($form_data['arrival_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Boarding Address</label>
                            <textarea class="form-control" name="boarding_address" rows="1"><?= htmlspecialchars($form_data['boarding_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'cab'): ?>
                <!-- Cab Booking Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-taxi"></i> Cab Details</h5>
                    <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Booking ID *</label>
                        <input type="text" class="form-control" name="booking_id" 
                            value="<?= htmlspecialchars($form_data['booking_id'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Type *</label>
                        <select class="form-select" name="vehicle_type" required>
                            <option value="Hatchback">Hatchback</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Luxury">Luxury</option>
                            <option value="Mini Van">Mini Van</option>
                            <option value="Tempo Traveller">Tempo Traveller</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Driver Contact *</label>
                        <input type="tel" class="form-control" name="driver_contact" 
                            value="<?= htmlspecialchars($form_data['driver_contact'] ?? '') ?>"
                            placeholder="+91XXXXXXXXXX" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Pickup Address *</label>
                        <textarea class="form-control" name="pickup_address" rows="2" required><?= htmlspecialchars($form_data['pickup_address'] ?? '') ?></textarea>
                        <small class="text-muted">Full address with landmark</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Drop Address *</label>
                        <textarea class="form-control" name="drop_address" rows="2" required><?= htmlspecialchars($form_data['drop_address'] ?? '') ?></textarea>
                        <small class="text-muted">Full address with landmark</small>
                    </div>
                </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Pickup Location *</label>
                            <input type="text" class="form-control" name="pickup_location" 
                                   value="<?= htmlspecialchars($form_data['pickup_location'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Drop Location *</label>
                            <input type="text" class="form-control" name="drop_location" 
                                   value="<?= htmlspecialchars($form_data['drop_location'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Pickup Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="pickup_datetime" 
                                   value="<?= htmlspecialchars($form_data['pickup_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Distance (km)</label>
                            <input type="number" class="form-control" name="distance" step="0.1" 
                                   value="<?= htmlspecialchars($form_data['distance'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Duration</label>
                            <input type="text" class="form-control" name="duration" placeholder="e.g., 2 hours" 
                                   value="<?= htmlspecialchars($form_data['duration'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'tour'): ?>
                <!-- Tour Package Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-suitcase"></i> Tour Package Details</h5>
                    <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Package Name *</label>
                        <input type="text" class="form-control" name="package_name" 
                            value="<?= htmlspecialchars($form_data['package_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Package Code</label>
                        <input type="text" class="form-control" name="package_code" 
                            value="<?= htmlspecialchars($form_data['package_code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tour Coordinator Contact *</label>
                        <input type="tel" class="form-control" name="coordinator_contact" 
                            value="<?= htmlspecialchars($form_data['coordinator_contact'] ?? '') ?>"
                            placeholder="+91XXXXXXXXXX" required>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact Number</label>
                        <input type="tel" class="form-control" name="emergency_contact" 
                            value="<?= htmlspecialchars($form_data['emergency_contact'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Number of Persons *</label>
                        <input type="number" class="form-control" name="persons" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Package Type</label>
                        <select class="form-select" name="package_type">
                            <option value="Standard">Standard</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Premium">Premium</option>
                            <option value="Luxury">Luxury</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Start Location Address</label>
                        <textarea class="form-control" name="start_location" rows="2"><?= htmlspecialchars($form_data['start_location'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Location Address</label>
                        <textarea class="form-control" name="end_location" rows="2"><?= htmlspecialchars($form_data['end_location'] ?? '') ?></textarea>
                    </div>
                </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Itinerary / Destinations</label>
                            <textarea class="form-control" name="itinerary" rows="3" 
                                      placeholder="List destinations or itinerary details"><?= htmlspecialchars($form_data['itinerary'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Inclusions</label>
                            <textarea class="form-control" name="inclusions" rows="2" 
                                      placeholder="What's included in the package"><?= htmlspecialchars($form_data['inclusions'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Passengers Section (for train, flight, bus) -->
                <?php if (in_array($ticket_type, ['train', 'flight', 'bus'])): ?>
                        <div class="form-section">
                    <h5><i class="fas fa-users"></i> Passenger Details</h5>
                    <div id="passenger-container">
                        <div class="passenger-row">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Passenger Name *</label>
                                    <input type="text" class="form-control" name="passenger_name[]" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Age *</label>
                                    <input type="number" class="form-control" name="passenger_age[]" min="0" max="120" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" name="passenger_gender[]" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <?php if ($ticket_type == 'train'): ?>
                                <div class="col-md-2">
                                    <label class="form-label">Coach</label>
                                    <input type="text" class="form-control" name="passenger_coach[]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Seat/Berth</label>
                                    <input type="text" class="form-control" name="passenger_seat[]">
                                </div>
                                <?php elseif ($ticket_type == 'flight'): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Seat Number</label>
                                    <input type="text" class="form-control" name="passenger_seat[]">
                                </div>
                                <?php elseif ($ticket_type == 'bus'): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Seat Number</label>
                                    <input type="text" class="form-control" name="passenger_seat[]">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-2" onclick="addPassenger()">
                        <i class="fas fa-plus"></i> Add Passenger
                    </button>
                </div>
                <?php endif; ?>

                <!-- Fare Details -->
                <div class="form-section">
                    <h5><i class="fas fa-rupee-sign"></i> Fare Details</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Base Fare *</label>
                            <input type="number" class="form-control calculate" name="base_fare" 
                                   step="0.01" value="<?= $form_data['base_fare'] ?? '0' ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" class="form-control calculate" name="tax_percent" 
                                   step="0.01" value="<?= $form_data['tax_percent'] ?? '18' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" class="form-control" name="tax_amount" 
                                   step="0.01" value="<?= $form_data['tax_amount'] ?? '0' ?>" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Service Charge</label>
                            <input type="number" class="form-control calculate" name="service_charge" 
                                   step="0.01" value="<?= $form_data['service_charge'] ?? '0' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Amount *</label>
                            <input type="number" class="form-control" name="total_amount" 
                                   step="0.01" value="<?= $form_data['total_amount'] ?? '0' ?>" readonly required>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="row mt-4">
                    <div class="col-md-12 d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-danger me-2">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-ticket-alt"></i> Create Ticket
                            </button>
                            <button type="button" class="btn btn-primary" onclick="previewTicket()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize datepickers
        flatpickr("input[type='datetime-local']", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });
        
        flatpickr("input[type='date']", {
            dateFormat: "Y-m-d",
        });
        
        flatpickr("input[type='time']", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
        });

        // Calculate total amount
        function calculateTotal() {
            const baseFare = parseFloat(document.querySelector('[name="base_fare"]').value) || 0;
            const taxPercent = parseFloat(document.querySelector('[name="tax_percent"]').value) || 0;
            const serviceCharge = parseFloat(document.querySelector('[name="service_charge"]').value) || 0;
            
            const taxAmount = (baseFare * taxPercent) / 100;
            const total = baseFare + taxAmount + serviceCharge;
            
            document.querySelector('[name="tax_amount"]').value = taxAmount.toFixed(2);
            document.querySelector('[name="total_amount"]').value = total.toFixed(2);
        }

        // Add passenger row
        function addPassenger() {
            const container = document.getElementById('passenger-container');
            const passengerRow = container.querySelector('.passenger-row');
            const newRow = passengerRow.cloneNode(true);
            
            // Clear values in cloned row
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            
            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger mt-2';
            removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
            removeBtn.onclick = function() {
                newRow.remove();
            };
            
            newRow.appendChild(removeBtn);
            container.appendChild(newRow);
        }

        // Preview ticket
        function previewTicket() {
            alert('Preview feature will be implemented soon!');
            // In future: Open modal with ticket preview
        }

        // Auto-calculate on input
        document.querySelectorAll('.calculate').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        // Initial calculation
        calculateTotal();
    </script>
    <script>
        $(document).ready(function() {
            var data = <?= json_encode($airline_data) ?>;
            // Initialize Select2 for airline selection if needed
            $('select[name="airline"]').select2({
                data: data,
                placeholder: 'Select Airline',
            });
        });
    </script>
</body>
</html>