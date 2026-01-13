<?php
// public/test_links.php
session_start();
if (!isset($_SESSION['logged_in'])) {
    die("Please login first.");
}

require_once '../app/config/database.php';

try {
    $db = getDB();
    
    echo "<h2>Test Ticket Links</h2>";
    
    // Get all tickets
    $stmt = $db->query("SELECT ticket_id, customer_name, ticket_type FROM tickets ORDER BY generated_at DESC");
    $tickets = $stmt->fetchAll();
    
    if (empty($tickets)) {
        echo "<p>No tickets found.</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Ticket ID</th><th>Customer</th><th>Type</th><th>Actions</th></tr>";
        
        foreach ($tickets as $ticket) {
            echo "<tr>";
            echo "<td>" . $ticket['ticket_id'] . "</td>";
            echo "<td>" . $ticket['customer_name'] . "</td>";
            echo "<td>" . $ticket['ticket_type'] . "</td>";
            echo "<td>
                    <a href='view_ticket.php?id=" . $ticket['ticket_id'] . "' target='_blank'>View</a> | 
                    <a href='generate_ticket.php?id=" . $ticket['ticket_id'] . "' target='_blank'>PDF</a>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>