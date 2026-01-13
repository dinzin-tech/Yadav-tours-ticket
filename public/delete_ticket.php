<?php
// public/delete_ticket.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ticket_list.php');
    exit();
}

$ticket_id = $_GET['id'];

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Load database
require_once APP_PATH . '/config/database.php';

try {
    $db = getDB();
    
    // Get ticket details to check ownership
    $stmt = $db->prepare("SELECT generated_by FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        $_SESSION['error'] = "Ticket not found.";
        header('Location: ticket_list.php');
        exit();
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $is_admin = ($_SESSION['role'] ?? '') === 'admin';
    
    // Check permission
    if (!$is_admin && $ticket['generated_by'] != $user_id) {
        $_SESSION['error'] = "You don't have permission to delete this ticket.";
        header('Location: ticket_list.php');
        exit();
    }
    
    // Delete the ticket
    $delete_stmt = $db->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $delete_stmt->execute([$ticket_id]);
    
    $_SESSION['success'] = "Ticket deleted successfully.";
    header('Location: ticket_list.php');
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error deleting ticket: " . $e->getMessage();
    header('Location: ticket_list.php');
    exit();
}
?>