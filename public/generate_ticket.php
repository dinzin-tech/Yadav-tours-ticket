<?php
// public/generate_ticket.php - UPDATED VERSION
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if ticket ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ticket_list.php');
    exit();
}

$ticket_id = $_GET['id'];

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load required files
require_once APP_PATH . '/config/database.php';

try {
    $db = getDB();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Load BrandingSettings if exists
$branding = null;
if (file_exists(APP_PATH . '/classes/BrandingSettings.php')) {
    require_once APP_PATH . '/classes/BrandingSettings.php';
    $branding = BrandingSettings::getInstance($db);
}

// Get ticket details
try {
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
        die("Access denied. You don't have permission to view this ticket.");
    }
    
    // Get agency info
    $stmt = $db->prepare("
        SELECT agency_name, agency_address, agency_phone, agency_email, 
               agency_logo, website 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$ticket['generated_by']]);
    $agency = $stmt->fetch();
    
    if (!$agency) {
        // Use branding settings if available
        if ($branding) {
            $agency = [
                'agency_name' => $branding->getCompanyName(),
                'agency_address' => $branding->get('company_address'),
                'agency_phone' => $branding->get('company_phone'),
                'agency_email' => $branding->get('company_email'),
                'website' => $branding->get('website_url'),
                'slogan' => 'Your Journey, Our Responsibility'
            ];
        } else {
            // Default agency info
            $agency = [
                'agency_name' => 'Travel Agency',
                'agency_address' => '123 Travel Street, City, Country',
                'agency_phone' => '+91 1234567890',
                'agency_email' => 'info@travelagency.com',
                'slogan' => 'Your Journey, Our Responsibility'
            ];
        }
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

// Generate HTML for ticket based on type
$type = $ticket['ticket_type'] ?? 'train';

// Use branding colors if available
if ($branding) {
    $primary_color = $branding->getPrimaryColor();
    $secondary_color = $branding->getSecondaryColor();
    $company_name = $branding->getCompanyName();
    $terms_conditions = $branding->getTermsConditions();
    $footer_text = $branding->get('footer_text');
} else {
    $primary_color = '#1e3c72';
    $secondary_color = '#2a5298';
    $company_name = 'Travel Agency';
    $terms_conditions = '';
    $footer_text = 'Thank you for choosing our services!';
}

// Logo HTML
$logo_html = '';
if ($branding && $branding->getLogoUrl()) {
    $logo_url = $branding->getLogoUrl();
    // Check if it's a local file
    if (strpos($logo_url, 'http') !== 0) {
        $logo_path = PUBLIC_PATH . str_replace(BASE_URL, '', $logo_url);
        if (file_exists($logo_path)) {
            $logo_html = '<img src="' . $logo_path . '" style="max-height: 60px; margin-bottom: 10px;">';
        }
    } else {
        $logo_html = '<img src="' . $logo_url . '" style="max-height: 60px; margin-bottom: 10px;">';
    }
}

if (empty($logo_html) && !empty($agency['agency_logo'])) {
    $logo_path = PUBLIC_PATH . '/assets/' . basename($agency['agency_logo']);
    if (file_exists($logo_path)) {
        $logo_html = '<img src="' . $logo_path . '" style="max-height: 60px; margin-bottom: 10px;">';
    }
}

if (empty($logo_html)) {
    $logo_html = '<h2 style="margin: 0 0 10px 0; color: white;">' . 
                htmlspecialchars($company_name) . '</h2>';
}

// Function to generate common CSS
function getCommonCSS($primary_color, $secondary_color) {
    return '
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        
        .ticket-container {
            border: 2px solid ' . $primary_color . ';
            border-radius: 10px;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .header {
            background: ' . $primary_color . ';
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        
        .agency-contact {
            font-size: 10px;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .section {
            padding: 15px;
            border-bottom: 1px dashed #ddd;
        }
        
        .section h3 {
            color: ' . $primary_color . ';
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        
        .barcode {
            text-align: center;
            padding: 15px;
            border-top: 2px solid ' . $primary_color . ';
            font-family: monospace;
            letter-spacing: 3px;
        }
        
        .terms {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }
        
        strong {
            color: ' . $primary_color . ';
        }
        
        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid ' . $primary_color . ';
            padding: 10px;
            margin: 10px 0;
        }
        
        .print-controls {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .train-header, .flight-header {
            background: ' . $primary_color . ';
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
    ';
}

// Function to get header HTML
function getHeaderHTML($logo_html, $agency, $branding = null) {
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    $company_address = $branding ? $branding->get('company_address') : ($agency['agency_address'] ?? '');
    $company_phone = $branding ? $branding->get('company_phone') : ($agency['agency_phone'] ?? '');
    $company_email = $branding ? $branding->get('company_email') : ($agency['agency_email'] ?? '');
    $company_website = $branding ? $branding->get('website_url') : ($agency['website'] ?? '');
    
    return '
    <div class="header">
        ' . $logo_html . '
        <p class="agency-contact">
            ' . (!empty($company_address) ? htmlspecialchars($company_address) . '<br>' : '') . '
            ' . (!empty($company_phone) ? 'Phone: ' . htmlspecialchars($company_phone) . ' | ' : '') . '
            ' . (!empty($company_email) ? 'Email: ' . htmlspecialchars($company_email) : '') . '
            ' . (!empty($company_website) ? ' | Website: ' . htmlspecialchars($company_website) : '') . '
        </p>
    </div>';
}

// Function to get passengers HTML
function getPassengersHTML($ticket_data) {
    if (empty($ticket_data['passengers'])) {
        return '';
    }
    
    $html = '<div class="section">
        <h3><i class="fas fa-users"></i> Passenger Details</h3>
        <table border="1" cellpadding="5" style="font-size: 10px;">
            <tr style="background: #f5f5f5;">
                <th width="5%">S.No</th>
                <th width="25%">Name</th>
                <th width="10%">Age</th>
                <th width="10%">Gender</th>
                <th width="25%">ID Proof</th>
                <th width="25%">Seat Details</th>
            </tr>';
    
    foreach ($ticket_data['passengers'] as $index => $passenger) {
        $html .= '
            <tr>
                <td align="center">' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($passenger['name'] ?? '') . '</td>
                <td align="center">' . htmlspecialchars($passenger['age'] ?? '') . '</td>
                <td align="center">' . htmlspecialchars($passenger['gender'] ?? '') . '</td>
                <td>' . htmlspecialchars($passenger['id_type'] ?? '') . ': ' . htmlspecialchars($passenger['id_number'] ?? '') . '</td>
                <td>Coach: ' . htmlspecialchars($passenger['coach'] ?? '') . ' | Seat: ' . htmlspecialchars($passenger['seat'] ?? '') . '</td>
            </tr>';
    }
    
    $html .= '</table></div>';
    return $html;
}

// Function to get fare HTML
function getFareHTML($ticket, $ticket_data) {
    return '<div class="section">
        <h3><i class="fas fa-rupee-sign"></i> Fare Details</h3>
        <table width="60%" style="margin-left: auto; font-size: 11px;">
            <tr>
                <td>Base Fare:</td>
                <td align="right">₹' . number_format($ticket_data['base_fare'] ?? 0, 2) . '</td>
            </tr>
            <tr>
                <td>Taxes & Charges:</td>
                <td align="right">₹' . number_format($ticket_data['tax_amount'] ?? 0, 2) . '</td>
            </tr>
            <tr>
                <td>Service Charge:</td>
                <td align="right">₹' . number_format($ticket_data['service_charge'] ?? 0, 2) . '</td>
            </tr>
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td>Total Amount:</td>
                <td align="right">₹' . number_format($ticket['total_amount'], 2) . '</td>
            </tr>
        </table>
    </div>';
}

// Function to get terms HTML
function getTermsHTML($agency, $branding = null) {
    $company_phone = $branding ? $branding->get('company_phone') : ($agency['agency_phone'] ?? '');
    $company_email = $branding ? $branding->get('company_email') : ($agency['agency_email'] ?? '');
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    $terms_conditions = $branding ? $branding->getTermsConditions() : '';
    $footer_text = $branding ? $branding->get('footer_text') : 'Thank you for choosing our services!';
    
    if (!empty($terms_conditions)) {
        $terms_content = $terms_conditions;
    } else {
        $terms_content = '
        <ol>
            <li>This is a computer generated ticket, no signature required.</li>
            <li>Please carry valid ID proof during journey.</li>
            <li>Reporting time: 30 minutes before departure for trains/buses.</li>
            <li>Cancellation and refund as per railway rules.</li>
            <li>For any queries, contact: ' . htmlspecialchars($company_phone) . ' or ' . htmlspecialchars($company_email) . '</li>
            <li>Ticket issued by: ' . htmlspecialchars($company_name) . '</li>
        </ol>';
    }
    
    return '<div class="section terms">
        <h3>Terms & Conditions</h3>
        ' . $terms_content . '
        <p style="text-align: center; margin-top: 15px; font-style: italic;">
            ' . htmlspecialchars($footer_text) . '
        </p>
    </div>';
}

// Function to get barcode HTML
function getBarcodeHTML($ticket) {
    return '<div class="barcode">
        <p style="font-family: monospace; font-size: 14px; letter-spacing: 3px; margin: 0;">
            |||||| ' . htmlspecialchars($ticket['ticket_id']) . ' |||||| ' . htmlspecialchars($ticket['pnr']) . ' ||||||
        </p>
        <p style="margin: 5px 0; font-size: 10px;">
            Ticket ID: ' . htmlspecialchars($ticket['ticket_id']) . ' | PNR: ' . htmlspecialchars($ticket['pnr']) . '
        </p>
        <p style="font-size: 9px; color: #666;">
            Generated on: ' . date('d/m/Y H:i:s') . ' | Valid for journey only
        </p>
    </div>';
}

// Function to generate train ticket template
function trainTemplate($ticket, $agency, $ticket_data, $logo_html, $primary_color, $secondary_color, $branding = null) {
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    $slogan = $agency['slogan'] ?? ($branding ? '' : 'Safe Journey, Happy Memories');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Train Ticket - ' . $ticket['ticket_id'] . '</title>
        <style>
            ' . getCommonCSS($primary_color, $secondary_color) . '
        </style>
    </head>
    <body>
        <!-- Print controls -->
        <div class="print-controls no-print">
            <h3>Ticket Ready for Printing</h3>
            <p>Click the button below to print or save as PDF.</p>
            <button onclick="window.print()" style="padding: 10px 20px; background: ' . $primary_color . '; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                Tip: Use "Save as PDF" in print dialog to save as PDF file.
            </p>
        </div>
        
        <div class="ticket-container">
            ' . getHeaderHTML($logo_html, $agency, $branding) . '
            
            <div class="train-header">
                <h2 style="margin: 0; font-size: 20px;">' . htmlspecialchars($company_name) . ' - TRAIN E-TICKET</h2>
                ' . (!empty($slogan) ? '<p style="margin: 5px 0; font-size: 12px;">' . htmlspecialchars($slogan) . '</p>' : '') . '
            </div>
            
            <div class="section">
                <h3>Journey Details</h3>
                <div class="highlight-box">
                    <table width="100%">
                        <tr>
                            <td width="30%"><strong>Train No:</strong></td>
                            <td width="70%">' . htmlspecialchars($ticket_data['train_number'] ?? 'N/A') . ' - ' . htmlspecialchars($ticket_data['train_name'] ?? 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>From:</strong></td>
                            <td>' . htmlspecialchars($ticket_data['from_station'] ?? 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>To:</strong></td>
                            <td>' . htmlspecialchars($ticket_data['to_station'] ?? 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>Departure:</strong></td>
                            <td>' . (isset($ticket_data['departure_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>Arrival:</strong></td>
                            <td>' . (isset($ticket_data['arrival_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['arrival_datetime'])) : 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>Class:</strong></td>
                            <td>' . htmlspecialchars($ticket_data['class'] ?? 'N/A') . '</td>
                        </tr>
                        <tr>
                            <td><strong>PNR:</strong></td>
                            <td><strong>' . htmlspecialchars($ticket['pnr']) . '</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            ' . getPassengersHTML($ticket_data) . '
            ' . getFareHTML($ticket, $ticket_data) . '
            ' . getTermsHTML($agency, $branding) . '
            ' . getBarcodeHTML($ticket) . '
        </div>
        
        <script>
            window.onload = function() {
                // Optional: Auto-print when page loads
                // window.print();
            };
        </script>
    </body>
    </html>';
}

// Function to generate flight ticket template
function flightTemplate($ticket, $agency, $ticket_data, $logo_html, $primary_color, $secondary_color, $branding = null) {
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    $slogan = $agency['slogan'] ?? ($branding ? '' : 'Fly Safe, Fly Happy');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Flight Ticket - ' . $ticket['ticket_id'] . '</title>
        <style>
            ' . getCommonCSS($primary_color, $secondary_color) . '
            .flight-segment {
                background: #e3f2fd;
                border: 1px solid ' . $primary_color . ';
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <!-- Print controls -->
        <div class="print-controls no-print">
            <h3>Ticket Ready for Printing</h3>
            <p>Click the button below to print or save as PDF.</p>
            <button onclick="window.print()" style="padding: 10px 20px; background: ' . $primary_color . '; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                Tip: Use "Save as PDF" in print dialog to save as PDF file.
            </p>
        </div>
        
        <div class="ticket-container">
            ' . getHeaderHTML($logo_html, $agency, $branding) . '
            
            <div class="flight-header">
                <h2 style="margin: 0; font-size: 20px;">' . htmlspecialchars($company_name) . ' - E-TICKET</h2>
                ' . (!empty($slogan) ? '<p style="margin: 5px 0; font-size: 12px;">' . htmlspecialchars($slogan) . '</p>' : '') . '
            </div>
            
            <div class="section">
                <h3>Flight Details</h3>
                <div class="flight-segment">
                    <table width="100%">
                        <tr>
                            <td width="40%">
                                <strong>From:</strong><br>
                                ' . htmlspecialchars($ticket_data['from_airport'] ?? 'N/A') . '<br>
                                ' . (isset($ticket_data['departure_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : 'N/A') . '
                            </td>
                            <td width="20%" style="text-align: center;">
                                <div style="font-size: 16px; color: ' . $primary_color . ';">➔</div>
                                <div><strong>' . htmlspecialchars($ticket_data['flight_number'] ?? 'N/A') . '</strong></div>
                                <div style="font-size: 10px;">' . htmlspecialchars($ticket_data['class'] ?? 'Economy') . '</div>
                            </td>
                            <td width="40%">
                                <strong>To:</strong><br>
                                ' . htmlspecialchars($ticket_data['to_airport'] ?? 'N/A') . '<br>
                                ' . (isset($ticket_data['arrival_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['arrival_datetime'])) : 'N/A') . '
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            ' . getPassengersHTML($ticket_data) . '
            ' . getFareHTML($ticket, $ticket_data) . '
            ' . getTermsHTML($agency, $branding) . '
            ' . getBarcodeHTML($ticket) . '
        </div>
        
        <script>
            window.onload = function() {
                // Optional: Auto-print when page loads
                // window.print();
            };
        </script>
    </body>
    </html>';
}

// Function to generate generic ticket template
function genericTemplate($ticket, $agency, $ticket_data, $logo_html, $primary_color, $secondary_color, $branding = null) {
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    $slogan = $agency['slogan'] ?? ($branding ? '' : 'Your Journey, Our Responsibility');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Travel Ticket - ' . $ticket['ticket_id'] . '</title>
        <style>
            ' . getCommonCSS($primary_color, $secondary_color) . '
        </style>
    </head>
    <body>
        <!-- Print controls -->
        <div class="print-controls no-print">
            <h3>Ticket Ready for Printing</h3>
            <p>Click the button below to print or save as PDF.</p>
            <button onclick="window.print()" style="padding: 10px 20px; background: ' . $primary_color . '; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
            <p style="margin-top: 10px; font-size: 11px; color: #666;">
                Tip: Use "Save as PDF" in print dialog to save as PDF file.
            </p>
        </div>
        
        <div class="ticket-container">
            ' . getHeaderHTML($logo_html, $agency, $branding) . '
            
            <div class="train-header">
                <h2 style="margin: 0; font-size: 20px;">' . htmlspecialchars($company_name) . ' - TRAVEL TICKET</h2>
                ' . (!empty($slogan) ? '<p style="margin: 5px 0; font-size: 12px;">' . htmlspecialchars($slogan) . '</p>' : '') . '
            </div>
            
            <div class="section">
                <h3>Travel Details</h3>
                <div class="highlight-box">
                    <table width="100%">
                        <tr>
                            <td width="30%"><strong>Type:</strong></td>
                            <td width="70%">' . ucfirst($ticket['ticket_type']) . ' Ticket</td>
                        </tr>
                        <tr>
                            <td><strong>PNR/Reference:</strong></td>
                            <td><strong>' . htmlspecialchars($ticket['pnr']) . '</strong></td>
                        </tr>
                        <tr>
                            <td><strong>Booking Date:</strong></td>
                            <td>' . date('d/m/Y', strtotime($ticket['booking_date'])) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Customer:</strong></td>
                            <td>' . htmlspecialchars($ticket['customer_name']) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            ' . getPassengersHTML($ticket_data) . '
            ' . getFareHTML($ticket, $ticket_data) . '
            ' . getTermsHTML($agency, $branding) . '
            ' . getBarcodeHTML($ticket) . '
        </div>
        
        <script>
            window.onload = function() {
                // Optional: Auto-print when page loads
                // window.print();
            };
        </script>
    </body>
    </html>';
}

// Function to get highlighted points HTML for each ticket type
function getHighlightedPoints($ticket_type, $ticket_data) {
    $html = '<div class="highlight-section" style="background: #f8f9fa; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; border-radius: 5px;">';
    $html .= '<h4 style="color: #dc3545; margin: 0 0 10px 0; font-size: 16px;"><i class="fas fa-exclamation-circle"></i> IMPORTANT POINTS</h4>';
    $html .= '<ul style="margin: 0; padding-left: 20px;">';
    
    switch($ticket_type) {
        case 'train':
            $html .= '<li><strong>PNR Number:</strong> ' . htmlspecialchars($ticket_data['pnr'] ?? '') . '</li>';
            $html .= '<li><strong>Train:</strong> ' . htmlspecialchars($ticket_data['train_name'] ?? '') . ' (' . htmlspecialchars($ticket_data['train_number'] ?? '') . ')</li>';
            $html .= '<li><strong>Class:</strong> ' . htmlspecialchars($ticket_data['class'] ?? '') . '</li>';
            $html .= '<li><strong>Report Time:</strong> 30 minutes before departure</li>';
            break;
            
        case 'flight':
            $html .= '<li><strong>PNR:</strong> ' . htmlspecialchars($ticket_data['pnr'] ?? '') . '</li>';
            $html .= '<li><strong>Flight:</strong> ' . htmlspecialchars($ticket_data['airline'] ?? '') . ' ' . htmlspecialchars($ticket_data['flight_number'] ?? '') . '</li>';
            $html .= '<li><strong>Class:</strong> ' . htmlspecialchars($ticket_data['class'] ?? '') . '</li>';
            $html .= '<li><strong>Check-in:</strong> 2 hours before domestic, 3 hours before international</li>';
            break;
            
        case 'bus':
            $html .= '<li><strong>Operator:</strong> ' . htmlspecialchars($ticket_data['operator'] ?? '') . '</li>';
            $html .= '<li><strong>PNR:</strong> ' . htmlspecialchars($ticket_data['pnr'] ?? '') . '</li>';
            $html .= '<li><strong>Bus Type:</strong> ' . htmlspecialchars($ticket_data['bus_type'] ?? '') . '</li>';
            if (!empty($ticket_data['bus_manager_contact'])) {
                $html .= '<li><strong>Bus Manager:</strong> ' . htmlspecialchars($ticket_data['bus_manager_contact']) . '</li>';
            }
            break;
            
        case 'cab':
            $html .= '<li><strong>Booking ID:</strong> ' . htmlspecialchars($ticket_data['booking_id'] ?? '') . '</li>';
            $html .= '<li><strong>Vehicle Type:</strong> ' . htmlspecialchars($ticket_data['vehicle_type'] ?? '') . '</li>';
            if (!empty($ticket_data['driver_contact'])) {
                $html .= '<li><strong>Driver Contact:</strong> ' . htmlspecialchars($ticket_data['driver_contact']) . '</li>';
            }
            $html .= '<li><strong>Free Wait Time:</strong> 15 minutes</li>';
            break;
            
        case 'tour':
            $html .= '<li><strong>Package:</strong> ' . htmlspecialchars($ticket_data['package_name'] ?? '') . '</li>';
            if (!empty($ticket_data['coordinator_contact'])) {
                $html .= '<li><strong>Coordinator:</strong> ' . htmlspecialchars($ticket_data['coordinator_contact']) . '</li>';
            }
            if (!empty($ticket_data['emergency_contact'])) {
                $html .= '<li><strong>Emergency Contact:</strong> ' . htmlspecialchars($ticket_data['emergency_contact']) . '</li>';
            }
            $html .= '<li><strong>Persons:</strong> ' . htmlspecialchars($ticket_data['persons'] ?? '1') . '</li>';
            break;
    }
    
    $html .= '</ul></div>';
    return $html;
}

// Function to get address section HTML
function getAddressSection($ticket_type, $ticket_data) {
    $html = '<div class="address-section" style="background: #e8f4fd; border: 1px solid #0d6efd; padding: 15px; margin: 15px 0; border-radius: 5px;">';
    $html .= '<h4 style="color: #0d6efd; margin: 0 0 10px 0; font-size: 16px;"><i class="fas fa-map-marker-alt"></i> ADDRESS DETAILS</h4>';
    
    switch($ticket_type) {
        case 'train':
            $html .= '<div class="row" style="display: flex;">';
            $html .= '<div class="col" style="flex: 1; padding-right: 10px;">';
            $html .= '<strong>From:</strong><br>' . htmlspecialchars($ticket_data['from_station'] ?? '');
            $html .= '</div>';
            $html .= '<div class="col" style="flex: 1; padding-left: 10px;">';
            $html .= '<strong>To:</strong><br>' . htmlspecialchars($ticket_data['to_station'] ?? '');
            $html .= '</div>';
            $html .= '</div>';
            break;
            
        case 'bus':
            $html .= '<div class="row" style="display: flex;">';
            $html .= '<div class="col" style="flex: 1; padding-right: 10px;">';
            $html .= '<strong>Boarding Point:</strong><br>' . nl2br(htmlspecialchars($ticket_data['boarding_address'] ?? ''));
            $html .= '</div>';
            $html .= '<div class="col" style="flex: 1; padding-left: 10px;">';
            $html .= '<strong>Drop Point:</strong><br>' . nl2br(htmlspecialchars($ticket_data['drop_address'] ?? ''));
            $html .= '</div>';
            $html .= '</div>';
            break;
            
        case 'cab':
            $html .= '<div class="row" style="display: flex;">';
            $html .= '<div class="col" style="flex: 1; padding-right: 10px;">';
            $html .= '<strong>Pickup Address:</strong><br>' . nl2br(htmlspecialchars($ticket_data['pickup_address'] ?? ''));
            $html .= '</div>';
            $html .= '<div class="col" style="flex: 1; padding-left: 10px;">';
            $html .= '<strong>Drop Address:</strong><br>' . nl2br(htmlspecialchars($ticket_data['drop_address'] ?? ''));
            $html .= '</div>';
            $html .= '</div>';
            break;
            
        case 'tour':
            if (!empty($ticket_data['start_location']) || !empty($ticket_data['end_location'])) {
                $html .= '<div class="row" style="display: flex;">';
                $html .= '<div class="col" style="flex: 1; padding-right: 10px;">';
                $html .= '<strong>Start Location:</strong><br>' . nl2br(htmlspecialchars($ticket_data['start_location'] ?? ''));
                $html .= '</div>';
                $html .= '<div class="col" style="flex: 1; padding-left: 10px;">';
                $html .= '<strong>End Location:</strong><br>' . nl2br(htmlspecialchars($ticket_data['end_location'] ?? ''));
                $html .= '</div>';
                $html .= '</div>';
            }
            break;
    }
    
    $html .= '</div>';
    return $html;
}

// Function to get clean ticket design
function cleanTicketTemplate($ticket, $agency, $ticket_data, $logo_html, $primary_color, $secondary_color, $branding = null) {
    $type = $ticket['ticket_type'] ?? 'train';
    $company_name = $branding ? $branding->getCompanyName() : ($agency['agency_name'] ?? 'Travel Agency');
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Ticket - ' . $ticket['ticket_id'] . '</title>
        <style>
            @media print {
                @page { size: A4; margin: 0; }
                body { margin: 20px; -webkit-print-color-adjust: exact; }
                .no-print { display: none !important; }
            }
            
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                color: #333;
                line-height: 1.4;
            }
            
            .ticket-container {
                max-width: 800px;
                margin: 0 auto;
                border: 2px solid ' . $primary_color . ';
                border-radius: 10px;
                overflow: hidden;
            }
            
            .ticket-header {
                background: ' . $primary_color . ';
                color: white;
                padding: 20px;
                text-align: center;
            }
            
            .ticket-body {
                padding: 20px;
            }
            
            .ticket-row {
                display: flex;
                margin-bottom: 15px;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
            }
            
            .ticket-label {
                font-weight: bold;
                color: #666;
                width: 120px;
                flex-shrink: 0;
            }
            
            .ticket-value {
                flex-grow: 1;
                color: #333;
            }
            
            .section-title {
                color: ' . $primary_color . ';
                border-bottom: 2px solid ' . $primary_color . ';
                padding-bottom: 5px;
                margin: 20px 0 15px 0;
                font-size: 14px;
            }
            
            .passenger-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            
            .passenger-table th {
                background: #f5f5f5;
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
            }
            
            .passenger-table td {
                padding: 8px;
                border: 1px solid #ddd;
            }
            
            .qr-code {
                text-align: center;
                padding: 20px;
                border-top: 1px dashed #ddd;
                margin-top: 20px;
            }
            
            .terms-box {
                background: #fff8e1;
                border: 1px solid #ffd54f;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
                font-size: 11px;
                line-height: 1.3;
            }
            
            .contact-box {
                background: #e3f2fd;
                border: 1px solid #90caf9;
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        <!-- Print controls -->
        <div class="no-print" style="text-align: center; margin: 20px 0;">
            <button onclick="window.print()" style="padding: 10px 20px; background: ' . $primary_color . '; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <button onclick="window.history.back()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>
        
        <div class="ticket-container">
            <div class="ticket-header">
                ' . $logo_html . '
                <h2 style="margin: 10px 0 5px 0;">' . htmlspecialchars($company_name) . '</h2>
                <p style="margin: 0; opacity: 0.9; font-size: 11px;">
                    ' . ($agency['agency_address'] ?? '') . ' | ' . 
                    ($agency['agency_phone'] ?? '') . ' | ' . 
                    ($agency['agency_email'] ?? '') . '
                </p>
            </div>
            
            <div class="ticket-body">
                <!-- Ticket Info -->
                <div class="ticket-row">
                    <div class="ticket-label">Ticket ID:</div>
                    <div class="ticket-value" style="font-weight: bold; color: ' . $primary_color . ';">
                        ' . htmlspecialchars($ticket['ticket_id']) . '
                    </div>
                </div>
                
                <div class="ticket-row">
                    <div class="ticket-label">Customer:</div>
                    <div class="ticket-value">' . htmlspecialchars($ticket['customer_name']) . '</div>
                </div>
                
                <div class="ticket-row">
                    <div class="ticket-label">Amount:</div>
                    <div class="ticket-value" style="font-weight: bold; color: #28a745;">
                        ₹' . number_format($ticket['total_amount'], 2) . '
                    </div>
                </div>
                
                <!-- Highlighted Points -->
                ' . getHighlightedPoints($type, $ticket_data) . '
                
                <!-- Address Section -->
                ' . getAddressSection($type, $ticket_data) . '
                
                <!-- Journey Details -->
                <div class="section-title">JOURNEY DETAILS</div>
                
                ' . getJourneyDetails($type, $ticket_data) . '
                
                <!-- Passengers -->
                ' . getCleanPassengersHTML($ticket_data) . '
                
                <!-- Fare Details -->
                <div class="section-title">FARE DETAILS</div>
                ' . getCleanFareHTML($ticket, $ticket_data) . '
                
                <!-- Terms & Conditions -->
                <div class="terms-box">
                    <div style="font-weight: bold; margin-bottom: 5px; color: #d84315;">
                        <i class="fas fa-file-contract"></i> TERMS & CONDITIONS:
                    </div>
                    ' . getTypeSpecificTerms($type, $branding) . '
                </div>
                
                <!-- QR Code -->
                <div class="qr-code">
                    <div style="font-family: monospace; font-size: 14px; letter-spacing: 3px; margin: 10px 0;">
                        ||| ' . htmlspecialchars($ticket['ticket_id']) . ' ||| ' . htmlspecialchars($ticket['pnr']) . ' |||
                    </div>
                    <div style="font-size: 10px; color: #666;">
                        Ticket ID: ' . htmlspecialchars($ticket['ticket_id']) . ' | Generated: ' . date('d/m/Y H:i:s') . '
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

// Helper functions for clean template
function getJourneyDetails($type, $ticket_data) {
    $html = '';
    switch($type) {
        case 'train':
            $html .= '<div class="ticket-row">
                <div class="ticket-label">Train:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['train_name'] ?? '') . ' (' . htmlspecialchars($ticket_data['train_number'] ?? '') . ')</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Class:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['class'] ?? '') . '</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Departure:</div>
                <div class="ticket-value">' . (isset($ticket_data['departure_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : '') . '</div>
            </div>';
            break;
            
        case 'flight':
            $html .= '<div class="ticket-row">
                <div class="ticket-label">Flight:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['airline'] ?? '') . ' ' . htmlspecialchars($ticket_data['flight_number'] ?? '') . '</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Class:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['class'] ?? '') . '</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Departure:</div>
                <div class="ticket-value">' . (isset($ticket_data['departure_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : '') . '</div>
            </div>';
            break;
            
        case 'bus':
            $html .= '<div class="ticket-row">
                <div class="ticket-label">Operator:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['operator'] ?? '') . '</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Bus Type:</div>
                <div class="ticket-value">' . htmlspecialchars($ticket_data['bus_type'] ?? '') . '</div>
            </div>
            <div class="ticket-row">
                <div class="ticket-label">Departure:</div>
                <div class="ticket-value">' . (isset($ticket_data['departure_datetime']) ? date('d/m/Y H:i', strtotime($ticket_data['departure_datetime'])) : '') . '</div>
            </div>';
            break;
    }
    return $html;
}

function getCleanPassengersHTML($ticket_data) {
    if (empty($ticket_data['passengers'])) return '';
    
    $html = '<div class="section-title">PASSENGERS (' . count($ticket_data['passengers']) . ')</div>';
    $html .= '<table class="passenger-table">';
    $html .= '<tr><th>#</th><th>Name</th><th>Age</th><th>Gender</th>';
    
    // Add seat column if available
    $has_seats = false;
    foreach ($ticket_data['passengers'] as $p) {
        if (!empty($p['seat'])) {
            $has_seats = true;
            break;
        }
    }
    if ($has_seats) {
        $html .= '<th>Seat</th>';
    }
    $html .= '</tr>';
    
    foreach ($ticket_data['passengers'] as $index => $passenger) {
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . htmlspecialchars($passenger['name'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($passenger['age'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($passenger['gender'] ?? '') . '</td>';
        if ($has_seats) {
            $html .= '<td>' . htmlspecialchars($passenger['seat'] ?? '') . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    return $html;
}

function getCleanFareHTML($ticket, $ticket_data) {
    return '<table style="width: 60%; margin-left: auto; font-size: 11px;">
        <tr>
            <td>Base Fare:</td>
            <td align="right">₹' . number_format($ticket_data['base_fare'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td>Taxes & Charges:</td>
            <td align="right">₹' . number_format($ticket_data['tax_amount'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td>Service Charge:</td>
            <td align="right">₹' . number_format($ticket_data['service_charge'] ?? 0, 2) . '</td>
        </tr>
        <tr style="border-top: 2px solid #000; font-weight: bold;">
            <td>Total Amount:</td>
            <td align="right">₹' . number_format($ticket['total_amount'], 2) . '</td>
        </tr>
    </table>';
}

function getTypeSpecificTerms($type, $branding) {
    $terms = '';
    if ($branding) {
        $terms_json = $branding->get('terms_conditions');
        if ($terms_json) {
            $all_terms = json_decode($terms_json, true);
            $terms = $all_terms[$type] ?? '';
        }
    }
    
    if (empty($terms)) {
        // Default terms
        switch($type) {
            case 'train':
                $terms = '• PNR Number mandatory for ticket verification<br>• Report 30 minutes before departure<br>• Carry valid ID proof<br>• No refund for missed trains';
                break;
            case 'flight':
                $terms = '• PNR/Booking Reference mandatory<br>• Check-in 2 hours before domestic, 3 hours before international<br>• Passport required for international flights';
                break;
            case 'bus':
                $terms = '• Operator Name and PNR mandatory<br>• Report to boarding point 30 minutes before departure<br>• Contact Bus Manager for assistance';
                break;
            case 'cab':
                $terms = '• Driver contact shared 1 hour before pickup<br>• 15 minutes free wait time<br>• Night charges extra (10PM to 6AM)';
                break;
            case 'tour':
                $terms = '• Tour Coordinator contact provided<br>• No refund for unused services<br>• Hotel check-in: 2PM, check-out: 12PM';
                break;
        }
    }
    
    return $terms;
}

// Main function to generate ticket HTML
// Replace the switch statement in generateTicketHTML function with:
function generateTicketHTML($ticket, $agency, $ticket_data, $branding = null) {
    $type = $ticket['ticket_type'] ?? 'train';
    
    if ($branding) {
        $primary_color = $branding->getPrimaryColor();
        $secondary_color = $branding->getSecondaryColor();
        $company_name = $branding->getCompanyName();
    } else {
        $primary_color = '#0d6efd';
        $secondary_color = '#6c757d';
        $company_name = $agency['agency_name'] ?? 'Travel Agency';
    }
    
    // Logo HTML
    $logo_html = '';
    if ($branding && $branding->getLogoUrl()) {
        $logo_url = $branding->getLogoUrl();
        if (strpos($logo_url, 'http') !== 0) {
            $logo_path = PUBLIC_PATH . str_replace(BASE_URL, '', $logo_url);
            if (file_exists($logo_path)) {
                $logo_html = '<img src="' . $logo_path . '" style="max-height: 50px;">';
            }
        } else {
            $logo_html = '<img src="' . $logo_url . '" style="max-height: 50px;">';
        }
    }
    
    if (empty($logo_html)) {
        $logo_html = '<h3 style="margin: 0;">' . htmlspecialchars($company_name) . '</h3>';
    }
    
    // Use clean template for all ticket types
    return cleanTicketTemplate($ticket, $agency, $ticket_data, $logo_html, $primary_color, $secondary_color, $branding);
}

// Generate and output the ticket HTML
echo generateTicketHTML($ticket, $agency, $ticket_data, $branding);
?>

