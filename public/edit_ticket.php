<?php
// public/edit_ticket.php
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

$ticket_id = $_GET['id'];

$airlines = require_once __DIR__ . '/../app/config/airlines.php';

$airline_data = [];

foreach ($airlines as $code => $airline) {
    $airline_data[] = [
        'id' => $code,
        'text' => $airline->name
    ];
}

// var_dump($airlines);
// die();

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('BASE_URL', 'http://localhost/ticket-system/public');

// Load database
require_once APP_PATH . '/config/database.php';

// Check if BrandingSettings exists
$brandingEnabled = false;
$branding = null;
if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
    require_once APP_PATH . '/classes/BrandingSettings.php';
    if (class_exists('BrandingSettings')) {
        $brandingEnabled = true;
    }
}

try {
    $db = getDB();
    
    if ($brandingEnabled) {
        $branding = BrandingSettings::getInstance($db);
    }
    
    // Get ticket details
    $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die("Ticket not found.");
    }
    
    // Check if current user owns this ticket or is admin
    $user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = ($_SESSION['role'] ?? '') === 'admin';
    
    if (!$is_admin && $ticket['generated_by'] != $user_id) {
        die("Access denied. You don't have permission to edit this ticket.");
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

    $airline_data = [];

    foreach ($airlines as $code => $airline) {
        if($code == $ticket_data['airline']) {
            $selected = true;
        } else {
            $selected = false;
        }
        $airline_data[] = [
            'id' => $code,
            'text' => $airline->name,
            'selected' => $selected
        ];
    }
    
    // Get ticket type
    $ticket_type = $ticket['ticket_type'];
    $allowed_types = ['train', 'flight', 'bus', 'cab', 'tour'];
    if (!in_array($ticket_type, $allowed_types)) {
        $ticket_type = 'train';
    }
    
    // Set page title
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
    $success = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $form_data = $_POST;
        
        // Set booking date if changed
        if (isset($form_data['booking_date']) && !empty($form_data['booking_date'])) {
            $ticket_data['booking_date'] = $form_data['booking_date'];
        }
        
        // Calculate total amount if fare changed
        if (isset($form_data['base_fare'])) {
            $base_fare = floatval($form_data['base_fare'] ?? 0);
            $tax_percent = floatval($form_data['tax_percent'] ?? 18);
            $tax_amount = ($base_fare * $tax_percent) / 100;
            $service_charge = floatval($form_data['service_charge'] ?? 0);
            $total_amount = $base_fare + $tax_amount + $service_charge;
            
            $ticket_data['base_fare'] = $base_fare;
            $ticket_data['tax_percent'] = $tax_percent;
            $ticket_data['tax_amount'] = $tax_amount;
            $ticket_data['service_charge'] = $service_charge;
            $ticket_data['total_amount'] = $total_amount;
        }
        
        // Update passenger data
        if (isset($form_data['passenger_name']) && is_array($form_data['passenger_name'])) {
            $passengers = [];
            $count = count($form_data['passenger_name']);
            for ($i = 0; $i < $count; $i++) {
                $passenger = [
                    'name' => $form_data['passenger_name'][$i] ?? '',
                    'age' => $form_data['passenger_age'][$i] ?? '',
                    'gender' => $form_data['passenger_gender'][$i] ?? '',
                    'seat' => $form_data['passenger_seat'][$i] ?? '',
                    'coach' => $form_data['passenger_coach'][$i] ?? ''
                ];
                $passengers[] = $passenger;
            }
            $ticket_data['passengers'] = $passengers;
        }
        
        // Update other fields based on ticket type
        foreach ($form_data as $key => $value) {
            if ($key !== 'passenger_name' && $key !== 'passenger_age' && 
                $key !== 'passenger_gender' && $key !== 'passenger_seat' && 
                $key !== 'passenger_coach' && $key !== 'base_fare' && 
                $key !== 'tax_percent' && $key !== 'service_charge') {
                $ticket_data[$key] = $value;
            }
        }
        
        // Update timestamp
        $ticket_data['updated_at'] = date('Y-m-d H:i:s');
        $ticket_data['updated_by'] = $_SESSION['username'] ?? 'admin';
        
        // Convert to JSON
        $ticket_data_json = json_encode($ticket_data, JSON_UNESCAPED_UNICODE);
        
        // Update in database
        $update_stmt = $db->prepare("
            UPDATE tickets SET 
                pnr = ?,
                customer_name = ?,
                customer_email = ?,
                customer_phone = ?,
                total_amount = ?,
                ticket_data = ?,
                booking_date = ?
            WHERE ticket_id = ?
        ");
        
        $update_stmt->execute([
            $form_data['pnr'] ?? $ticket['pnr'],
            $form_data['customer_name'] ?? $ticket['customer_name'],
            $form_data['customer_email'] ?? $ticket['customer_email'],
            $form_data['customer_phone'] ?? $ticket['customer_phone'],
            $total_amount ?? $ticket['total_amount'],
            $ticket_data_json,
            $form_data['booking_date'] ?? $ticket['booking_date'],
            $ticket_id
        ]);
        
        $success = true;
        
        // Refresh ticket data
        $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        
        if (is_string($ticket['ticket_data'])) {
            $ticket_data = json_decode($ticket['ticket_data'], true);
        } else {
            $ticket_data = $ticket['ticket_data'];
        }
    }
    
} catch (Exception $e) {
    $errors[] = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($ticket_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f5f7fb; }
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
        .type-icon { font-size: 24px; margin-right: 10px; }
        .ticket-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                    <?php if ($is_admin): ?><span class="badge bg-danger ms-1">Admin</span><?php endif; ?>
                </span>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="ticket-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">
                        <i class="fas fa-edit"></i> 
                        Edit <?= htmlspecialchars($page_title) ?>
                    </h2>
                    <p class="mb-0">
                        Ticket ID: <strong><?= htmlspecialchars($ticket_id) ?></strong>
                        | Created: <?= date('d/m/Y H:i', strtotime($ticket['generated_at'])) ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="view_ticket.php?id=<?= $ticket_id ?>" class="btn btn-light">
                        <i class="fas fa-eye"></i> View Ticket
                    </a>
                    <a href="ticket_list.php" class="btn btn-outline-light">
                        <i class="fas fa-list"></i> All Tickets
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Ticket updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
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

        <!-- Edit Form -->
        <div class="ticket-form-container">
            <form method="POST" action="" id="editTicketForm">
                <!-- Common Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-user"></i> Customer Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" name="customer_name" 
                                   value="<?= htmlspecialchars($ticket['customer_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customer Email</label>
                            <input type="email" class="form-control" name="customer_email" 
                                   value="<?= htmlspecialchars($ticket['customer_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customer Phone</label>
                            <input type="tel" class="form-control" name="customer_phone" 
                                   value="<?= htmlspecialchars($ticket['customer_phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Booking Date</label>
                            <input type="date" class="form-control" name="booking_date" 
                                   value="<?= htmlspecialchars($ticket['booking_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PNR/Reference *</label>
                            <input type="text" class="form-control" name="pnr" 
                                   value="<?= htmlspecialchars($ticket['pnr'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <?php if ($ticket_type == 'train'): ?>
                <!-- Train Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-train"></i> Train Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Train Number *</label>
                            <input type="text" class="form-control" name="train_number" 
                                   value="<?= htmlspecialchars($ticket_data['train_number'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Train Name *</label>
                            <input type="text" class="form-control" name="train_name" 
                                   value="<?= htmlspecialchars($ticket_data['train_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class/Type *</label>
                            <select class="form-select" name="class" required>
                                <option value="AC First Class" <?= ($ticket_data['class'] ?? '') == 'AC First Class' ? 'selected' : '' ?>>AC First Class</option>
                                <option value="AC 2 Tier" <?= ($ticket_data['class'] ?? '') == 'AC 2 Tier' ? 'selected' : '' ?>>AC 2 Tier</option>
                                <option value="AC 3 Tier" <?= ($ticket_data['class'] ?? '') == 'AC 3 Tier' ? 'selected' : '' ?>>AC 3 Tier</option>
                                <option value="Sleeper" <?= ($ticket_data['class'] ?? '') == 'Sleeper' ? 'selected' : '' ?>>Sleeper (Non-AC)</option>
                                <option value="Second Sitting" <?= ($ticket_data['class'] ?? '') == 'Second Sitting' ? 'selected' : '' ?>>Second Sitting</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Station *</label>
                            <input type="text" class="form-control" name="from_station" 
                                   value="<?= htmlspecialchars($ticket_data['from_station'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">To Station *</label>
                            <input type="text" class="form-control" name="to_station" 
                                   value="<?= htmlspecialchars($ticket_data['to_station'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Departure Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Arrival Date/Time</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['arrival_datetime'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Coach</label>
                            <input type="text" class="form-control" name="coach" 
                                   value="<?= htmlspecialchars($ticket_data['coach'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Seat</label>
                            <input type="text" class="form-control" name="seat" 
                                   value="<?= htmlspecialchars($ticket_data['seat'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'flight'): ?>
                <!-- Flight Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-plane"></i> Flight Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Flight Number *</label>
                            <input type="text" class="form-control" name="flight_number" 
                                   value="<?= htmlspecialchars($ticket_data['flight_number'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Airline Name *</label>
                            <!-- <input type="text" class="form-control" name="airline" 
                                   value="<?= htmlspecialchars($ticket_data['airline'] ?? '') ?>" required> -->
                            <select name="airline" id="airline_select" required class="form-select">

                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class *</label>
                            <select class="form-select" name="class" required>
                                <option value="Economy" <?= ($ticket_data['class'] ?? '') == 'Economy' ? 'selected' : '' ?>>Economy</option>
                                <option value="Premium Economy" <?= ($ticket_data['class'] ?? '') == 'Premium Economy' ? 'selected' : '' ?>>Premium Economy</option>
                                <option value="Business" <?= ($ticket_data['class'] ?? '') == 'Business' ? 'selected' : '' ?>>Business Class</option>
                                <option value="First Class" <?= ($ticket_data['class'] ?? '') == 'First Class' ? 'selected' : '' ?>>First Class</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">From Airport *</label>
                            <input type="text" class="form-control" name="from_airport" 
                                   value="<?= htmlspecialchars($ticket_data['from_airport'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">To Airport *</label>
                            <input type="text" class="form-control" name="to_airport" 
                                   value="<?= htmlspecialchars($ticket_data['to_airport'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Seat</label>
                            <input type="text" class="form-control" name="seat" 
                                   value="<?= htmlspecialchars($ticket_data['seat'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Gate</label>
                            <input type="text" class="form-control" name="gate" 
                                   value="<?= htmlspecialchars($ticket_data['gate'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Departure *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Arrival *</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['arrival_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Terminal</label>
                            <input type="text" class="form-control" name="terminal" 
                                   value="<?= htmlspecialchars($ticket_data['terminal'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'bus'): ?>
                <!-- Bus Ticket Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-bus"></i> Bus Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Operator Name *</label>
                            <input type="text" class="form-control" name="operator" 
                                   value="<?= htmlspecialchars($ticket_data['operator'] ?? '') ?>" 
                                   placeholder="e.g., VRL Travels, SRS Travels" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bus Type *</label>
                            <select class="form-select" name="bus_type" required>
                                <option value="AC Sleeper" <?= ($ticket_data['bus_type'] ?? '') == 'AC Sleeper' ? 'selected' : '' ?>>AC Sleeper</option>
                                <option value="Non-AC Sleeper" <?= ($ticket_data['bus_type'] ?? '') == 'Non-AC Sleeper' ? 'selected' : '' ?>>Non-AC Sleeper</option>
                                <option value="AC Seater" <?= ($ticket_data['bus_type'] ?? '') == 'AC Seater' ? 'selected' : '' ?>>AC Seater</option>
                                <option value="Non-AC Seater" <?= ($ticket_data['bus_type'] ?? '') == 'Non-AC Seater' ? 'selected' : '' ?>>Non-AC Seater</option>
                                <option value="Volvo AC" <?= ($ticket_data['bus_type'] ?? '') == 'Volvo AC' ? 'selected' : '' ?>>Volvo AC</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bus Manager Contact</label>
                            <input type="tel" class="form-control" name="bus_manager_contact" 
                                   value="<?= htmlspecialchars($ticket_data['bus_manager_contact'] ?? '') ?>"
                                   placeholder="+91XXXXXXXXXX">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Boarding Point Address *</label>
                            <textarea class="form-control" name="boarding_address" rows="2" required><?= htmlspecialchars($ticket_data['boarding_address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Drop Point Address *</label>
                            <textarea class="form-control" name="drop_address" rows="2" required><?= htmlspecialchars($ticket_data['drop_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Departure *</label>
                            <input type="datetime-local" class="form-control" name="departure_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['departure_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Arrival *</label>
                            <input type="datetime-local" class="form-control" name="arrival_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['arrival_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Seat Number</label>
                            <input type="text" class="form-control" name="seat" 
                                   value="<?= htmlspecialchars($ticket_data['seat'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'cab'): ?>
                <!-- Cab Booking Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-taxi"></i> Cab Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Vehicle Type *</label>
                            <select class="form-select" name="vehicle_type" required>
                                <option value="Hatchback" <?= ($ticket_data['vehicle_type'] ?? '') == 'Hatchback' ? 'selected' : '' ?>>Hatchback</option>
                                <option value="Sedan" <?= ($ticket_data['vehicle_type'] ?? '') == 'Sedan' ? 'selected' : '' ?>>Sedan</option>
                                <option value="SUV" <?= ($ticket_data['vehicle_type'] ?? '') == 'SUV' ? 'selected' : '' ?>>SUV</option>
                                <option value="Luxury" <?= ($ticket_data['vehicle_type'] ?? '') == 'Luxury' ? 'selected' : '' ?>>Luxury</option>
                                <option value="Mini Van" <?= ($ticket_data['vehicle_type'] ?? '') == 'Mini Van' ? 'selected' : '' ?>>Mini Van</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Driver Contact *</label>
                            <input type="tel" class="form-control" name="driver_contact" 
                                   value="<?= htmlspecialchars($ticket_data['driver_contact'] ?? '') ?>"
                                   placeholder="+91XXXXXXXXXX" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Distance (km)</label>
                            <input type="number" class="form-control" name="distance" step="0.1" 
                                   value="<?= htmlspecialchars($ticket_data['distance'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pickup Address *</label>
                            <textarea class="form-control" name="pickup_address" rows="2" required><?= htmlspecialchars($ticket_data['pickup_address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Drop Address *</label>
                            <textarea class="form-control" name="drop_address" rows="2" required><?= htmlspecialchars($ticket_data['drop_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pickup Date/Time *</label>
                            <input type="datetime-local" class="form-control" name="pickup_datetime" 
                                   value="<?= htmlspecialchars($ticket_data['pickup_datetime'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estimated Duration</label>
                            <input type="text" class="form-control" name="duration" placeholder="e.g., 2 hours" 
                                   value="<?= htmlspecialchars($ticket_data['duration'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php elseif ($ticket_type == 'tour'): ?>
                <!-- Tour Package Fields -->
                <div class="form-section">
                    <h5><i class="fas fa-suitcase"></i> Tour Package Details</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Package Name *</label>
                            <input type="text" class="form-control" name="package_name" 
                                   value="<?= htmlspecialchars($ticket_data['package_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tour Coordinator Contact *</label>
                            <input type="tel" class="form-control" name="coordinator_contact" 
                                   value="<?= htmlspecialchars($ticket_data['coordinator_contact'] ?? '') ?>"
                                   placeholder="+91XXXXXXXXXX" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Emergency Contact</label>
                            <input type="tel" class="form-control" name="emergency_contact" 
                                   value="<?= htmlspecialchars($ticket_data['emergency_contact'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?= htmlspecialchars($ticket_data['start_date'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">End Date *</label>
                            <input type="date" class="form-control" name="end_date" 
                                   value="<?= htmlspecialchars($ticket_data['end_date'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Number of Persons *</label>
                            <input type="number" class="form-control" name="persons" min="1" 
                                   value="<?= htmlspecialchars($ticket_data['persons'] ?? '1') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Package Type</label>
                            <select class="form-select" name="package_type">
                                <option value="Standard" <?= ($ticket_data['package_type'] ?? '') == 'Standard' ? 'selected' : '' ?>>Standard</option>
                                <option value="Deluxe" <?= ($ticket_data['package_type'] ?? '') == 'Deluxe' ? 'selected' : '' ?>>Deluxe</option>
                                <option value="Premium" <?= ($ticket_data['package_type'] ?? '') == 'Premium' ? 'selected' : '' ?>>Premium</option>
                                <option value="Luxury" <?= ($ticket_data['package_type'] ?? '') == 'Luxury' ? 'selected' : '' ?>>Luxury</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Location Address</label>
                            <textarea class="form-control" name="start_location" rows="2"><?= htmlspecialchars($ticket_data['start_location'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Location Address</label>
                            <textarea class="form-control" name="end_location" rows="2"><?= htmlspecialchars($ticket_data['end_location'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Itinerary</label>
                            <textarea class="form-control" name="itinerary" rows="3"><?= htmlspecialchars($ticket_data['itinerary'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Passengers Section -->
                <?php if (in_array($ticket_type, ['train', 'flight', 'bus'])): ?>
                <div class="form-section">
                    <h5><i class="fas fa-users"></i> Passenger Details</h5>
                    <div id="passenger-container">
                        <?php 
                        $passengers = $ticket_data['passengers'] ?? [['name' => '', 'age' => '', 'gender' => 'Male', 'seat' => '', 'coach' => '']];
                        foreach ($passengers as $index => $passenger): 
                        ?>
                        <div class="passenger-row <?= $index > 0 ? 'additional-passenger' : '' ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Passenger Name *</label>
                                    <input type="text" class="form-control" name="passenger_name[]" 
                                           value="<?= htmlspecialchars($passenger['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Age *</label>
                                    <input type="number" class="form-control" name="passenger_age[]" 
                                           min="0" max="120" value="<?= htmlspecialchars($passenger['age'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" name="passenger_gender[]" required>
                                        <option value="Male" <?= ($passenger['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($passenger['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($passenger['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <?php if ($ticket_type == 'train'): ?>
                                <div class="col-md-2">
                                    <label class="form-label">Coach</label>
                                    <input type="text" class="form-control" name="passenger_coach[]" 
                                           value="<?= htmlspecialchars($passenger['coach'] ?? '') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Seat/Berth</label>
                                    <input type="text" class="form-control" name="passenger_seat[]" 
                                           value="<?= htmlspecialchars($passenger['seat'] ?? '') ?>">
                                </div>
                                <?php elseif (in_array($ticket_type, ['flight', 'bus'])): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Seat Number</label>
                                    <input type="text" class="form-control" name="passenger_seat[]" 
                                           value="<?= htmlspecialchars($passenger['seat'] ?? '') ?>">
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-sm btn-danger mt-2 remove-passenger">
                                <i class="fas fa-times"></i> Remove Passenger
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary mt-2" id="addPassengerBtn">
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
                                   step="0.01" value="<?= $ticket_data['base_fare'] ?? '0' ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" class="form-control calculate" name="tax_percent" 
                                   step="0.01" value="<?= $ticket_data['tax_percent'] ?? '18' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" class="form-control" name="tax_amount" 
                                   step="0.01" value="<?= $ticket_data['tax_amount'] ?? '0' ?>" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Service Charge</label>
                            <input type="number" class="form-control calculate" name="service_charge" 
                                   step="0.01" value="<?= $ticket_data['service_charge'] ?? '0' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Amount *</label>
                            <input type="number" class="form-control" name="total_amount" 
                                   step="0.01" value="<?= $ticket['total_amount'] ?>" readonly required>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col-md-12 d-flex justify-content-between">
                        <div>
                            <a href="view_ticket.php?id=<?= $ticket_id ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="generate_ticket.php?id=<?= $ticket_id ?>" class="btn btn-info" target="_blank">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                        <div>
                            <button type="reset" class="btn btn-outline-danger me-2">
                                <i class="fas fa-redo"></i> Reset Changes
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Ticket
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
        document.getElementById('addPassengerBtn').addEventListener('click', function() {
            const container = document.getElementById('passenger-container');
            const passengerRows = container.querySelectorAll('.passenger-row');
            const firstRow = passengerRows[0];
            const newRow = firstRow.cloneNode(true);
            
            // Clear values in cloned row
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            
            // Add remove button if not present
            if (!newRow.querySelector('.remove-passenger')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger mt-2 remove-passenger';
                removeBtn.innerHTML = '<i class="fas fa-times"></i> Remove Passenger';
                removeBtn.onclick = function() {
                    newRow.remove();
                };
                newRow.appendChild(removeBtn);
            }
            
            container.appendChild(newRow);
        });

        // Remove passenger
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-passenger')) {
                e.target.closest('.passenger-row').remove();
            }
        });

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