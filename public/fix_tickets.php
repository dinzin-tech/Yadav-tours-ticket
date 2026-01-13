<?php
// public/fix_tickets.php
require_once '../app/config/database.php';

try {
    $db = getDB();
    
    echo "<h2>Fixing Tickets Table</h2>";
    
    // Check current structure
    $stmt = $db->query("SHOW COLUMNS FROM tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>";
    
    // Check if 'id' column exists
    if (!in_array('id', $columns)) {
        echo "<p style='color: orange;'>⚠ 'id' column doesn't exist</p>";
        
        // Option 1: Add id column
        echo "<h3>Option 1: Add ID Column</h3>";
        echo "<p><a href='javascript:void(0)' onclick=\"runSQL('ALTER TABLE tickets ADD COLUMN id INT PRIMARY KEY AUTO_INCREMENT FIRST')\">Click to add ID column</a></p>";
        
        // Option 2: Continue without id column
        echo "<h3>Option 2: Continue without ID column</h3>";
        echo "<p>All your code will need to use ticket_id instead of id.</p>";
        
    } else {
        echo "<p style='color: green;'>✓ 'id' column exists</p>";
    }
    
    // Show some sample data
    $stmt = $db->query("SELECT * FROM tickets LIMIT 3");
    $tickets = $stmt->fetchAll();
    
    echo "<h3>Sample Tickets:</h3>";
    echo "<pre>";
    print_r($tickets);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<script>
function runSQL(sql) {
    if (confirm('Run this SQL?\n\n' + sql)) {
        window.location.href = 'run_sql.php?sql=' + encodeURIComponent(sql);
    }
}
</script>