<?php
// public/generate_ticket.php
session_start();

/* -------------------- AUTH -------------------- */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if (empty($_GET['id'])) {
    die('Invalid ticket ID');
}

$ticket_id = $_GET['id'];

// load airlines data
$airlines = require_once __DIR__ . '/../app/config/airlines.php';

/* -------------------- PATHS -------------------- */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

/* -------------------- DB -------------------- */
require_once APP_PATH . '/config/database.php';
$db = getDB();

/* -------------------- DATA -------------------- */
$stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die('Ticket not found');
}

$ticket_data = json_decode($ticket['ticket_data'], true);
$ticket_type = $ticket['ticket_type'] ?? 'flight';

/* -------------------- AGENCY -------------------- */
$stmt = $db->prepare("
    SELECT agency_name, agency_address, agency_phone, agency_email, agency_logo
    FROM users WHERE id = ?
");
$stmt->execute([$ticket['generated_by']]);
$agency = $stmt->fetch(PDO::FETCH_ASSOC);

$agency = $agency ?: [
    'agency_name' => 'Travel Agency',
    'agency_address' => '',
    'agency_phone' => '',
    'agency_email' => '',
    'agency_logo' => null
];

/* -------------------- CHECK BRANDING -------------------- */
$branding = null;
if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
    require_once APP_PATH . '/classes/BrandingSettings.php';
    if (class_exists('BrandingSettings')) {
        $branding = BrandingSettings::getInstance($db);
    }
}

// $brand_settings = $branding ? $branding->loadSettings() : [];

// $brand = new BrandingSettings($db);

$agency = BrandingSettings::getAllSettings() ?? $agency;

// var_dump($agency);
// exit();


/* -------------------- HELPER -------------------- */
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (!$date) return '';
    return date('H:i \h\r\s, d-M-Y', strtotime($date));
}

function formatJustDate($date) {
    if (!$date) return '';
    return date('d-M-Y', strtotime($date));
}

$type_label = ucfirst($ticket_type);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= e($type_label) ?> Ticket - <?= e($ticket['ticket_id']) ?></title>

<style>
    @media print {
        @page { size: A4; margin: 15mm; }
        body { margin:0; -webkit-print-color-adjust:exact; }
        .no-print { display:none; }
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #222;
    }

    .container {
        max-width: 820px;
        margin: auto;
    }

    /* FLIGHT TICKET SPECIFIC STYLES */
    .flight-header {
        margin-bottom: 20px;
        border-bottom: 2px solid #ddd;
        padding-bottom: 10px;
    }
    .flight-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .agency-logo-img {
        max-height: 50px;
        margin-bottom: 5px;
    }
    .flight-header-right {
        text-align: right;
    }

    .flight-info-box {
        background: #fdfdfd;
        border: 1px solid #000;
        margin: 15px 0;
        padding: 10px;
        display: flex;
        justify-content: space-between;
    }

    .section-title {
        font-weight: bold;
        margin: 18px 0 6px;
        border-bottom: 1px solid #000;
        padding-bottom: 4px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }

    .table th, .table td {
        border: 1px solid #000;
        padding: 6px;
        font-size: 11px;
        text-align: left;
    }

    .table th {
        background: #f2f2f2;
        font-weight: bold;
    }

    .fare-box {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .fare-box td, .fare-box th {
        border: 1px solid #000;
        padding: 5px;
    }

    .fare-row {
        display: flex;
        justify-content: space-between;
    }

    .total {
        font-weight: bold;
    }

    .terms {
        font-size: 10px;
        line-height: 1.4;
        margin-top: 15px;
    }

    .footer {
        text-align: center;
        font-size: 10px;
        margin-top: 15px;
        border-top: 1px dashed #999;
        padding-top: 6px;
    }

    .print-btn {
        margin: 15px 0;
        text-align: center;
    }

    /* SHARED STYLES FOR OTHER TYPES */
    .header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }
    .header-left h2 { margin: 0; font-size: 18px; }
    .header-right { text-align: right; font-size: 11px; }

</style>
</head>

<body>
<div class="container">

<div class="print-btn no-print">
    <button onclick="window.print()">Print / Save as PDF</button>
</div>

<!-- ========================================================================= -->

<?php if ($ticket_type == 'flight'): 
    // FLIGHT TICKET LAYOUT
    $airline_code = substr($ticket_data['flight_number'] ?? '6E', 0, 2); 
    $op_by = $ticket_data['airline'] ? $ticket_data['airline'] : '6E';
    
    // Attempt to determine Issuer and Contact
    $issued_by_name = $ticket_data['airline']; 

    // Check if airline exists in our data and append image if available
    $airline_img = '';

    if(isset($airlines[$issued_by_name])) {
        $airline_info = $airlines[$issued_by_name];
        if (!empty($airline_info->logo)) {
            $airline_img = $airline_info->logo;
            
        }
        else {
            $issued_by_name = $airline_info->name;
        }
        
    }


    if (empty($issued_by_name)) $issued_by_name = $agency['brand_name']; // Fallback

    // Contact Logic: User wants Airline Contact if exists, else Agency
    // Since we don't store Airline Contact in DB, we'll check if it was somehow provided or just default to Agency
    // However, to satisfy the requirement "issued should be airline name and ... show agency contact details",
    // We will show Issued By: Airline, and Contact: Agency (since we don't have airline contact).
    
    $contact_html = '
        ' . e($agency['company_address']) . '<br>
        <strong>Email:</strong> ' . e($agency['company_email']) . '<br>
        <strong>Mobile:</strong> ' . e($agency['company_phone']);

?>

    <div class="flight-header">
        <div class="flight-header-top">
            <div class="flight-header-left">
                <?php if (!empty($agency['logo_path'])): ?>
                    <img src="<?= e($agency['logo_path']) ?>" class="agency-logo-img" alt="Agency Logo"><br>
                    <h2 style="color:<?= $agency['primary_color'] ?>; margin:0;"><?= e(strtoupper($agency['brand_name'])) ?></h2>
                <?php else: ?>
                    <h2 style="color:{$agency['primary_color']}; margin:0;"><?= e(strtoupper($agency['brand_name'])) ?></h2>
                <?php endif; ?>
                <div style="font-size:11px; line-height:1.4;">
                    <?= $contact_html ?>
                </div>
            </div>
            <div class="flight-header-right">
                <div style="font-size:12px; margin-bottom:5px;"><strong>Your e-receipt</strong></div>
                <!-- <div>Booked On: <?= date('Y-m-d H:i:s.v', strtotime($ticket['generated_at'])) ?></div> -->
            </div>
        </div>
    </div>

    <!-- Booking Info Row -->
    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Issued by :</strong>
            <img src="<?= e($airline_img) ?>" style="height:40px; vertical-align:middle;" alt="<?= e($issued_by_name) ?>"> &nbsp;
            <br/>
            <?= e($issued_by_name) ?>
            <div style="margin-top:5px; font-size:10px;">
                Contact: <?= e($agency['brand_name']) ?> Support
            </div>
        </div>
        <div style="text-align:center;">
            Airline PNR:<br>
            <strong style="font-size:24px;"><?= e($ticket['pnr']) ?></strong>
        </div>
        <div style="text-align:right;">
            PAYMENT REFERENCE: <strong><?= e($ticket['ticket_id']) ?></strong><br><br>
            TICKET STATUS: <?= ucfirst($ticket['ticket_status']) ?><br>
            CLASS OF TRAVEL : <?= strtoupper($ticket_data['class'] ?? 'ECONOMY') ?>
        </div>
    </div>

    <div class="section-title" style="border:none;">Flight Details <span style="float:right; font-weight:normal; font-size:10px;">Based on 24-hour clock.</span></div>
    <table class="table">
    <tr>
        <th>Flight No</th>
        <th>Origin</th>
        <th>Destination</th>
        <th>Dep. Date</th>
        <th>Arrival Date</th>
        <th>Operated By</th>
    </tr>
    <tr>
        <td><?= e($ticket_data['flight_number'] ?? '') ?></td>
        <td><?= e($ticket_data['from_airport'] ?? '') ?></td>
        <td><?= e($ticket_data['to_airport'] ?? '') ?></td>
        <td><?= formatDate($ticket_data['departure_datetime'] ?? '') ?></td>
        <td><?= formatDate($ticket_data['arrival_datetime'] ?? '') ?></td>
        <td><?= e($op_by) ?></td>
    </tr>
    </table>

    <div class="section-title" style="border:none; margin-top:20px;">Pax Details <span style="float:right; font-weight:normal; font-size:11px;">Customer Contact No: <?= e($ticket['customer_phone']) ?></span></div>
    <table class="table">
    <tr>
        <th rowspan="2">Passenger Name</th>
        <th rowspan="2">Segment</th>
        <th rowspan="2">Flight No</th>
        <th rowspan="2">Ticket No</th>
        <th colspan="2" style="text-align:center;">Baggage</th>
        <th rowspan="2">Status</th>
    </tr>
    <tr>
        <th style="text-align:center; font-size:10px;">Hand</th>
        <th style="text-align:center; font-size:10px;">Free</th>
    </tr>
    <?php 
    $passengers = $ticket_data['passengers'] ?? [];
    foreach ($passengers as $p): 
        $title = ($p['gender'] == 'Female') ? 'Ms.' : 'Mr.';
        if ($p['gender'] == 'Other') $title = 'Mx.';
        $fullName = $title . ' ' . strtoupper($p['name']);
        
        $segment = e($ticket_data['from_airport']) . '-' . e($ticket_data['to_airport']);
    ?>
    <tr>
        <td><?= e($fullName) ?> (<?= e($p['gender'] == 'Child' ? 'CHD' : 'ADT') ?>)</td>
        <td><?= $segment ?></td>
        <td><?= e($ticket_data['flight_number']) ?></td>
        <td><?= e($ticket['pnr']) ?>/1</td>
        <td style="text-align:center;">07KG</td>
        <td style="text-align:center;">15KG</td>
        <td>CONFIRM</td>
    </tr>
    <?php endforeach; ?>
    </table>

    <div class="section-title" style="border:none; margin-top:20px;">Fare Details</div>
    <table class="fare-box">
        <tr>
            <!-- Left Column -->
            <td width="50%" valign="top" style="padding:0; border:none;">
                <table width="100%" cellspacing="0" cellpadding="5" style="border-collapse:collapse;">
                    <tr>
                        <td style="border:1px solid #000;">Basic Fare</td>
                        <td style="border:1px solid #000; text-align:right;">INR <?= number_format($ticket_data['base_fare'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000;">Tax & Others</td>
                        <td style="border:1px solid #000; text-align:right;">INR <?= number_format($ticket_data['tax_amount'] ?? 0, 2) ?></td>
                        <?php //var_dump($ticket_data);die(); ?>
                    </tr>
                    <tr><td style="border:1px solid #000;">Baggage Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Meals Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Seat Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Other SSR</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                </table>
            </td>
            <!-- Right Column -->
            <td width="50%" valign="top" style="padding:0; border:none; padding-left:10px;">
                <table width="100%" cellspacing="0" cellpadding="5" style="border-collapse:collapse;">
                    <tr><td style="border:1px solid #000;">Reschedule Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Cancellation Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Gateway Charge</td><td style="border:1px solid #000; text-align:right;">-</td></tr>
                    <tr><td style="border:1px solid #000;">Other Charge</td><td style="border:1px solid #000; text-align:right;"><?= number_format($ticket_data['service_charge'], 2) ?? '-' ?></td></tr>
                    <tr><td style="border:1px solid #000; height:24px;"></td><td style="border:1px solid #000;"></td></tr>
                    <tr>
                        <td style="border:1px solid #000; font-weight:bold;">Total Amount</td>
                        <td style="border:1px solid #000; text-align:right; font-weight:bold;">INR <?= number_format($ticket['total_amount'], 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-top:20px; font-weight:bold; text-decoration:underline;">Rules and Regulations</div>
    <div class="terms">
        <strong>We request you to follow the "Passenger Guidelines" listed by the Ministry of Civil Aviation for a safe flying experience.</strong>
        <ul style="margin-top:5px; padding-left:15px;">
            <li>THIS TYPE OF FARES IS NON-REFUNDABLE / NON-CHANGEABLE</li>
            <li>Passengers should report at least two hours before the departure time. Boarding gates will close 25 minutes before departure.</li>
            <li>Passengers will be required to wear the protective gear (Face mask)</li>
            <li>Passengers who have flights departing in the next 4 hours will be allowed to enter the terminal building</li>
            <li>All Passengers must carry a Valid Photo Identity Proof at the time of entering into airport.</li>
            <li>Flight timings are subject to change without prior notice. Please recheck with the carrier prior to departure</li>
            <li>For Fare Rules / Cancellation policy- refer to fare rules laid by the carrier</li>
        </ul>
    </div>

<?php else: ?>
    <!-- ========================================================================= -->
    <!-- STANDARD LAYOUT FOR OTHER TYPES (Train, Bus, Cab, Tour) -->

    <div class="header">
        <div class="header-left">
            <?php if (!empty($agency['logo_path'])): ?>
                <img src="<?= e($agency['logo_path']) ?>" class="agency-logo-img" alt="Agency Logo" style="max-height:50px; margin-bottom:5px;"><br>
            <?php endif; ?>
            <h2><?= e($agency['brand_name']) ?></h2>
            <small>
                <?= e($agency['company_address']) ?><br>
                Mobile: <?= e($agency['company_phone']) ?><br>
                Email: <?= e($agency['company_email']) ?>
            </small>
        </div>
        <div class="header-right">
            <strong>Your e-receipt</strong><br>
            Booked On: <?= date('d M Y H:i', strtotime($ticket['booking_date'])) ?><br>
            <strong>Ticket ID:</strong> <?= e($ticket['ticket_id']) ?><br>
            <?php if (!empty($ticket['pnr'])): ?>
            <strong>PNR:</strong> <?= e($ticket['pnr']) ?><br>
            <?php endif; ?>
            <strong>Status:</strong> <?= strtoupper($ticket['ticket_status']) ?>
        </div>
    </div>

    <!-- JOURNEY DETAILS -->
    <div class="section-title"><?= e($type_label) ?> Details</div>

    <?php if ($ticket_type == 'train'): ?>
        <table class="table">
        <tr>
            <th>Train No</th>
            <th>Train Name</th>
            <th>Class</th>
            <th>From Station</th>
            <th>To Station</th>
            <th>Departure</th>
            <th>Arrival</th>
        </tr>
        <tr>
            <td><?= e($ticket_data['train_number'] ?? '') ?></td>
            <td><?= e($ticket_data['train_name'] ?? '') ?></td>
            <td><?= e($ticket_data['class'] ?? '') ?></td>
            <td><?= e($ticket_data['from_station'] ?? '') ?></td>
            <td><?= e($ticket_data['to_station'] ?? '') ?></td>
            <td><?= formatDate($ticket_data['departure_datetime'] ?? '') ?></td>
            <td><?= formatDate($ticket_data['arrival_datetime'] ?? '') ?></td>
        </tr>
        </table>
    <?php elseif ($ticket_type == 'bus'): ?>
        <table class="table">
        <tr>
            <th>Operator</th>
            <th>Bus Type</th>
            <th>Boarding Point</th>
            <th>Drop Point</th>
            <th>Departure</th>
            <th>Arrival</th>
        </tr>
        <tr>
            <td><?= e($ticket_data['operator'] ?? '') ?></td>
            <td><?= e($ticket_data['bus_type'] ?? '') ?></td>
            <td><?= e($ticket_data['pickup_point'] ?? '') ?></td>
            <td><?= e($ticket_data['drop_point'] ?? '') ?></td>
            <td><?= formatDate($ticket_data['departure_datetime'] ?? '') ?></td>
            <td><?= formatDate($ticket_data['arrival_datetime'] ?? '') ?></td>
        </tr>
        <tr>
            <td colspan="3"><strong>Boarding Address:</strong> <?= e($ticket_data['boarding_address'] ?? '') ?></td>
            <td colspan="3"><strong>Drop Address:</strong> <?= e($ticket_data['drop_address'] ?? '') ?></td>
        </tr>
        </table>
    <?php elseif ($ticket_type == 'cab'): ?>
        <table class="table">
        <tr>
            <th>Ordering ID</th>
            <th>Vehicle Type</th>
            <th>Driver Contact</th>
            <th>Pickup</th>
            <th>Drop</th>
            <th>Pickup Time</th>
        </tr>
        <tr>
            <td><?= e($ticket_data['booking_id'] ?? '') ?></td>
            <td><?= e($ticket_data['vehicle_type'] ?? '') ?></td>
            <td><?= e($ticket_data['driver_contact'] ?? '') ?></td>
            <td><?= e($ticket_data['pickup_location'] ?? '') ?></td>
            <td><?= e($ticket_data['drop_location'] ?? '') ?></td>
            <td><?= formatDate($ticket_data['pickup_datetime'] ?? '') ?></td>
        </tr>
        <tr>
            <td colspan="3"><strong>Pickup Address:</strong> <?= e($ticket_data['pickup_address'] ?? '') ?></td>
            <td colspan="3"><strong>Drop Address:</strong> <?= e($ticket_data['drop_address'] ?? '') ?></td>
        </tr>
        </table>
    <?php elseif ($ticket_type == 'tour'): ?>
        <table class="table">
        <tr>
            <th>Package Name</th>
            <th>Package Code</th>
            <th>Type</th>
            <th>Coordinator</th>
            <th>Emergency Contact</th>
            <th>Persons</th>
        </tr>
        <tr>
            <td><?= e($ticket_data['package_name'] ?? '') ?></td>
            <td><?= e($ticket_data['package_code'] ?? '') ?></td>
            <td><?= e($ticket_data['package_type'] ?? '') ?></td>
            <td><?= e($ticket_data['coordinator_contact'] ?? '') ?></td>
            <td><?= e($ticket_data['emergency_contact'] ?? '') ?></td>
            <td><?= e($ticket_data['persons'] ?? '') ?></td>
        </tr>
        </table>
        <div style="margin-top: 5px; font-size: 11px;">
            <strong>Itinerary:</strong> <?= nl2br(e($ticket_data['itinerary'] ?? '')) ?><br>
            <strong>Inclusions:</strong> <?= nl2br(e($ticket_data['inclusions'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <!-- CUSTOMER / PASSENGER -->
    <?php if ($ticket_type == 'tour' || $ticket_type == 'cab'): ?>
        <div class="section-title">Customer Details</div>
        <table class="table">
        <tr>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Email</th>
        </tr>
        <tr>
            <td><?= e($ticket['customer_name']) ?></td>
            <td><?= e($ticket['customer_phone']) ?></td>
            <td><?= e($ticket['customer_email']) ?></td>
        </tr>
        </table>
    <?php else: ?>
        <div class="section-title">Passenger Details</div>
        <table class="table">
        <tr>
            <th>Name</th>
            <th>Age</th>
            <th>Gender</th>
            <?php if ($ticket_type == 'train'): ?>
                <th>Coach</th>
                <th>Seat/Berth</th>
            <?php elseif ($ticket_type == 'bus'): ?>
                <th>Seat</th>
            <?php endif; ?>
            <th>Status</th>
        </tr>
        <?php foreach ($ticket_data['passengers'] as $p): ?>
        <tr>
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['age']) ?></td>
            <td><?= e($p['gender']) ?></td>
            <?php if ($ticket_type == 'train'): ?>
                <td><?= e($p['coach'] ?? '-') ?></td>
                <td><?= e($p['seat'] ?? '-') ?></td>
            <?php elseif ($ticket_type == 'bus'): ?>
                <td><?= e($p['seat'] ?? '-') ?></td>
            <?php endif; ?>
            <td>CONFIRMED</td>
        </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- FARE -->
    <div class="section-title">Fare Details</div>
    <table class="fare-box">
    <tr><td>Basic Fare</td><td align="right">₹<?= number_format($ticket_data['base_fare'] ?? 0, 2) ?></td></tr>
    <tr><td>Tax & Others</td><td align="right">₹<?= number_format($ticket_data['tax_amount'] ?? 0, 2) ?></td></tr>
    <tr class="total"><td>Total Amount</td><td align="right">₹<?= number_format($ticket['total_amount'], 2) ?></td></tr>
    </table>

    <div class="footer">
        This is a system generated <?= e($ticket_type) ?> ticket. No signature required.
    </div>

<?php endif; ?>

</div>
</body>
</html>
