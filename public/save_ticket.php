<?php
// public/save_ticket.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Load database
require_once APP_PATH . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_ticket.php');
    exit();
}

try {
    $db = getDB();
    
    // Get form data
    $data = $_POST;
    $ticket_type = $data['ticket_type'] ?? 'train';
    
    // Generate ticket ID
    $ticket_id = 'T' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Calculate total amount
    $base_fare = floatval($data['base_fare'] ?? 0);
    $tax_percent = floatval($data['tax_percent'] ?? 18);
    $tax_amount = ($base_fare * $tax_percent) / 100;
    $service_charge = floatval($data['service_charge'] ?? 0);
    $total_amount = $base_fare + $tax_amount + $service_charge;
    
    // Prepare ticket data
    $ticket_data = $data;
    $ticket_data['generated_by'] = $_SESSION['username'] ?? 'admin';
    $ticket_data['generated_at'] = date('Y-m-d H:i:s');
    $ticket_data['ticket_id'] = $ticket_id;
    $ticket_data['tax_amount'] = $tax_amount;
    $ticket_data['total_amount'] = $total_amount;
    $ticket_data['booking_date'] = date('Y-m-d');
    
    // Prepare passengers array
    if (isset($data['passenger_name']) && is_array($data['passenger_name'])) {
        $passengers = [];
        $count = count($data['passenger_name']);
        for ($i = 0; $i < $count; $i++) {
            $passenger = [
                'name' => $data['passenger_name'][$i] ?? '',
                'age' => $data['passenger_age'][$i] ?? '',
                'gender' => $data['passenger_gender'][$i] ?? '',
                'id_type' => $data['passenger_id_type'][$i] ?? '',
                'id_number' => $data['passenger_id_number'][$i] ?? '',
                'seat' => $data['passenger_seat'][$i] ?? '',
                'coach' => $data['passenger_coach'][$i] ?? ''
            ];
            $passengers[] = $passenger;
        }
        $ticket_data['passengers'] = $passengers;
    }
    
    // Convert to JSON
    $ticket_data_json = json_encode($ticket_data, JSON_UNESCAPED_UNICODE);
    
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
        $data['pnr'] ?? 'N/A',
        date('Y-m-d'),
        $data['customer_name'],
        $data['customer_email'] ?? null,
        $data['customer_phone'] ?? null,
        $total_amount,
        $ticket_data_json,
        $_SESSION['user_id'] ?? 1
    ]);
    
    // Redirect to view ticket
    header('Location: view_ticket.php?id=' . $ticket_id);
    exit();
    
} catch (Exception $e) {
    die("Error saving ticket: " . $e->getMessage());
}