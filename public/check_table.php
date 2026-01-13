<?php
// public/check_table.php
require_once '../app/config/database.php';

try {
    $db = getDB();
    
    echo "<h2>Checking Tickets Table Structure</h2>";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tickets'");
    if (!$stmt->fetch()) {
        echo "<p style='color: red;'>✗ Tickets table doesn't exist!</p>";
        
        // Create table
        echo "<h3>Create Tickets Table:</h3>";
        echo "<pre>
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id VARCHAR(20) UNIQUE NOT NULL,
    ticket_type VARCHAR(20) NOT NULL,
    pnr VARCHAR(20),
    booking_date DATE DEFAULT CURRENT_DATE,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(10,2) NOT NULL,
    ticket_data JSON,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
        </pre>";
        exit();
    }
    
    // Show table structure
    $stmt = $db->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h3>Sample Data in Tickets Table:</h3>";
    $stmt = $db->query("SELECT * FROM tickets LIMIT 5");
    $tickets = $stmt->fetchAll();
    
    if (empty($tickets)) {
        echo "<p>No tickets found in table.</p>";
    } else {
        echo "<pre>";
        print_r($tickets);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>