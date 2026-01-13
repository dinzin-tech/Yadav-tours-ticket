<?php
// public/test_ticket.php
session_start();

// Define base path
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Include required files
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/controllers/TicketController.php';

echo "<h2>Testing Ticket Creation</h2>";

try {
    // Create database connection
    $db = getDB();
    echo "<p>✓ Database connected successfully</p>";
    
    // Get the first user ID from database
    $stmt = $db->query("SELECT id FROM users WHERE is_active = 1 LIMIT 1");
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>✗ No active users found in database!</p>";
        echo "<p><a href='create_user.php'>Create a user first</a></p>";
        exit();
    }
    
    $user_id = $user['id'];
    
    // Set session for testing
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = 'test_user';
    
    echo "<p>✓ Using user ID: $user_id</p>";
    
    // Test ticket controller
    $controller = new TicketController();
    echo "<p>✓ TicketController loaded successfully</p>";
    
    // Test data
    $test_data = [
        'ticket_type' => 'train',
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '1234567890',
        'pnr' => 'ABCD123456',
        'train_number' => '12345',
        'train_name' => 'Rajdhani Express',
        'class' => 'AC 2 Tier',
        'from_station' => 'Mumbai',
        'to_station' => 'Delhi',
        'departure_datetime' => '2024-01-15 15:30',
        'arrival_datetime' => '2024-01-16 08:30',
        'coach' => 'B3',
        'seat' => '12',
        'passenger_name' => ['John Doe'],
        'passenger_age' => [30],
        'passenger_gender' => ['Male'],
        'base_fare' => 1500,
        'tax_percent' => 18,
        'tax_amount' => 270,
        'service_charge' => 100,
        'total_amount' => 1870,
        'booking_date' => date('Y-m-d')
    ];
    
    echo "<h3>Test Data:</h3>";
    echo "<pre>";
    print_r($test_data);
    echo "</pre>";
    
    // Save ticket
    $result = $controller->saveTicket($test_data);
    
    echo "<h3>Result:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Ticket saved successfully! Ticket ID: " . $result['ticket_id'] . "</p>";
        
        // Test retrieving ticket
        $ticket = $controller->getTicket($result['ticket_id']);
        if ($ticket) {
            echo "<p>✓ Ticket retrieved successfully</p>";
            echo "<h4>Ticket Details:</h4>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Ticket ID</td><td>" . $ticket['ticket_id'] . "</td></tr>";
            echo "<tr><td>Customer</td><td>" . $ticket['customer_name'] . "</td></tr>";
            echo "<tr><td>PNR</td><td>" . $ticket['pnr'] . "</td></tr>";
            echo "<tr><td>Amount</td><td>₹" . $ticket['total_amount'] . "</td></tr>";
            echo "<tr><td>Type</td><td>" . $ticket['ticket_type'] . "</td></tr>";
            echo "</table>";
            
            // Show ticket data - FIXED THIS LINE
            echo "<h4>Full Ticket Data:</h4>";
            echo "<pre>";
            // Check if ticket_data is already decoded
            if (is_array($ticket['ticket_data'])) {
                print_r($ticket['ticket_data']);
            } else {
                print_r(json_decode($ticket['ticket_data'], true));
            }
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Failed to retrieve ticket</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to save ticket: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>