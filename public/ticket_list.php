<?php
// public/ticket_list.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Load database
require_once APP_PATH . '/config/database.php';

// Create database connection
try {
    $db = getDB();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Get user ID
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Get filter parameters
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$where = $is_admin ? "WHERE 1=1" : "WHERE generated_by = ?";
$params = $is_admin ? [] : [$user_id];

if (!empty($type)) {
    $where .= " AND ticket_type = ?";
    $params[] = $type;
}

if (!empty($search)) {
    $where .= " AND (customer_name LIKE ? OR pnr LIKE ? OR ticket_id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($date_from)) {
    $where .= " AND DATE(generated_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where .= " AND DATE(generated_at) <= ?";
    $params[] = $date_to;
}

// Get total count
$count_query = "SELECT COUNT(*) FROM tickets $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_tickets = $stmt->fetchColumn();
$total_pages = ceil($total_tickets / $limit);

// Get tickets - FIXED VERSION
$query = "SELECT ticket_id, ticket_type, pnr, customer_name, customer_email, 
                 customer_phone, total_amount, generated_at, generated_by, 
                 payment_status, ticket_status 
          FROM tickets $where 
          ORDER BY generated_at DESC 
          LIMIT ? OFFSET ?";

// Prepare statement
$stmt = $db->prepare($query);

// Bind all parameters
$paramIndex = 1;
foreach ($params as $value) {
    if (is_int($value)) {
        $stmt->bindValue($paramIndex++, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($paramIndex++, $value);
    }
}

// Bind limit and offset as integers
$stmt->bindValue($paramIndex++, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$tickets = $stmt->fetchAll();

// Get ticket type counts for filter
$type_counts_query = $is_admin 
    ? "SELECT ticket_type, COUNT(*) as count FROM tickets GROUP BY ticket_type"
    : "SELECT ticket_type, COUNT(*) as count FROM tickets WHERE generated_by = ? GROUP BY ticket_type";
    
$type_stmt = $db->prepare($type_counts_query);
$type_stmt->execute($is_admin ? [] : [$user_id]);
$type_counts = $type_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get agent names for admin view
$agents = [];
if ($is_admin) {
    $agent_stmt = $db->query("SELECT id, username FROM users WHERE is_active = 1");
    $agents = $agent_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tickets - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .ticket-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .ticket-train { background-color: #e3f2fd; color: #0d47a1; }
        .ticket-flight { background-color: #e8f5e8; color: #1b5e20; }
        .ticket-bus { background-color: #fff3e0; color: #e65100; }
        .ticket-cab { background-color: #f3e5f5; color: #4a148c; }
        .ticket-tour { background-color: #e0f2f1; color: #004d40; }
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .ticket-issued { background-color: #d1ecf1; color: #0c5460; }
        .ticket-cancelled { background-color: #f8d7da; color: #721c24; }
        .agent-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
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
                        <a class="nav-link" href="create_ticket.php"><i class="fas fa-plus-circle"></i> Create Ticket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ticket_list.php"><i class="fas fa-list"></i> All Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link text-white">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        <?php if ($is_admin): ?><span class="badge bg-danger">Admin</span><?php endif; ?>
                    </span>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1"><i class="fas fa-ticket-alt text-primary"></i> All Tickets</h2>
                                <p class="text-muted mb-0">
                                    Total: <?= $total_tickets ?> tickets
                                    <?php if ($is_admin): ?>
                                    | <span class="text-primary">Admin View: Showing all tickets</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <a href="create_ticket.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create New Ticket
                                </a>
                                <?php if ($is_admin): ?>
                                <button class="btn btn-outline-secondary" onclick="exportTickets()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Tickets</h5>
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Ticket Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="train" <?= $type == 'train' ? 'selected' : '' ?>>Train</option>
                            <option value="flight" <?= $type == 'flight' ? 'selected' : '' ?>>Flight</option>
                            <option value="bus" <?= $type == 'bus' ? 'selected' : '' ?>>Bus</option>
                            <option value="cab" <?= $type == 'cab' ? 'selected' : '' ?>>Cab</option>
                            <option value="tour" <?= $type == 'tour' ? 'selected' : '' ?>>Tour</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Name, PNR, Ticket ID" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="ticket_list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <div class="text-muted">
                            Showing <?= min($offset + 1, $total_tickets) ?> - 
                            <?= min($offset + $limit, $total_tickets) ?> of <?= $total_tickets ?> tickets
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <td>
    <div class="btn-group btn-group-sm">
        <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
           class="btn btn-outline-primary" title="View">
            <i class="fas fa-eye"></i>
        </a>
        <?php if ($is_admin || $ticket['generated_by'] == $user_id): ?>
        <a href="edit_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
           class="btn btn-outline-warning" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <a href="generate_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
           class="btn btn-outline-success" title="Download PDF">
            <i class="fas fa-download"></i>
        </a>
        <button type="button" class="btn btn-outline-danger" 
                onclick="confirmDelete('<?= $ticket['ticket_id'] ?>')" 
                title="Delete">
            <i class="fas fa-trash"></i>
        </button>
        <?php endif; ?>
    </div>
</td>

        <!-- Type Counts -->
        <?php if (!empty($type_counts)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <a href="ticket_list.php" 
                       class="btn btn-sm btn-<?= empty($type) ? 'primary' : 'light' ?> d-flex align-items-center">
                        <span class="me-2">All Types</span>
                        <span class="badge bg-secondary"><?= $total_tickets ?></span>
                    </a>
                    <?php foreach ($type_counts as $type_name => $count): 
                        $active_class = ($type == $type_name) ? 'border-primary border-2' : '';
                        $color_class = "ticket-$type_name";
                    ?>
                    <a href="?type=<?= $type_name ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="btn btn-sm btn-light d-flex align-items-center <?= $active_class ?>">
                        <span class="ticket-badge <?= $color_class ?> me-2">
                            <i class="fas fa-<?= 
                                $type_name == 'flight' ? 'plane' :
                                ($type_name == 'train' ? 'train' :
                                ($type_name == 'bus' ? 'bus' :
                                ($type_name == 'cab' ? 'taxi' : 'suitcase')))
                            ?>"></i>
                            <?= ucfirst($type_name) ?>
                        </span>
                        <span class="badge bg-secondary"><?= $count ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tickets Table -->
        <div class="table-container">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <h4>No tickets found</h4>
                    <p class="text-muted">
                        <?= empty($search) && empty($type) && empty($date_from) && empty($date_to) 
                            ? 'Create your first ticket to get started!' 
                            : 'No tickets match your filters.' 
                        ?>
                    </p>
                    <?php if (!empty($search) || !empty($type) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="ticket_list.php" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                    <?php else: ?>
                    <a href="create_ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Ticket
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Type</th>
                            <th>PNR/Ref</th>
                            <th>Customer</th>
                            <?php if ($is_admin): ?>
                            <th>Agent</th>
                            <?php endif; ?>
                            <th>Journey Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): 
                            $typeClass = 'ticket-' . $ticket['ticket_type'];
                            $paymentStatusClass = 'status-' . $ticket['payment_status'];
                            $ticketStatusClass = 'ticket-' . $ticket['ticket_status'];
                        ?>
                        <tr>
                            <td>
                                <strong class="font-monospace"><?= htmlspecialchars($ticket['ticket_id']) ?></strong>
                            </td>
                            <td>
                                <span class="ticket-badge <?= $typeClass ?>">
                                    <i class="fas fa-<?= 
                                        $ticket['ticket_type'] === 'flight' ? 'plane' :
                                        ($ticket['ticket_type'] === 'train' ? 'train' :
                                        ($ticket['ticket_type'] === 'bus' ? 'bus' :
                                        ($ticket['ticket_type'] === 'cab' ? 'taxi' : 'suitcase')))
                                    ?>"></i>
                                    <?= ucfirst($ticket['ticket_type']) ?>
                                </span>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($ticket['pnr']) ?></code>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($ticket['customer_name']) ?></div>
                                <small class="text-muted">
                                    <?php if (!empty($ticket['customer_email'])): ?>
                                        <?= htmlspecialchars($ticket['customer_email']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($ticket['customer_phone'])): ?>
                                        <br><?= htmlspecialchars($ticket['customer_phone']) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <?php if ($is_admin): ?>
                            <td>
                                <span class="agent-badge">
                                    <?= htmlspecialchars($agents[$ticket['generated_by']] ?? 'User ' . $ticket['generated_by']) ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?= date('d/m/Y', strtotime($ticket['generated_at'])) ?>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    ₹<?= number_format($ticket['total_amount'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $paymentStatusClass ?> me-1">
                                    <?= ucfirst($ticket['payment_status']) ?>
                                </span>
                                <span class="status-badge <?= $ticketStatusClass ?>">
                                    <?= ucfirst($ticket['ticket_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($ticket['generated_at'])) ?><br>
                                <small class="text-muted"><?= date('H:i', strtotime($ticket['generated_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                       class="btn btn-outline-primary" title="View" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="generate_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                       class="btn btn-outline-success" title="Download PDF" data-bs-toggle="tooltip">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php if ($is_admin || $ticket['generated_by'] == $user_id): ?>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmDelete('<?= $ticket['ticket_id'] ?>')" 
                                            title="Delete" data-bs-toggle="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= 
                        http_build_query(array_merge($_GET, ['page' => $page - 1])) 
                    ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                </li>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= 
                            http_build_query(array_merge($_GET, ['page' => $i])) 
                        ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= 
                            http_build_query(array_merge($_GET, ['page' => $total_pages])) 
                        ?>">
                            <?= $total_pages ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= 
                        http_build_query(array_merge($_GET, ['page' => $page + 1])) 
                    ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Summary Stats -->
        <?php if (!empty($tickets)): ?>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Revenue</h6>
                                <h4 class="mb-0">
                                    ₹<?= number_format(array_sum(array_column($tickets, 'total_amount')), 2) ?>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-rupee-sign fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Paid Tickets</h6>
                                <h4 class="mb-0">
                                    <?= count(array_filter($tickets, fn($t) => $t['payment_status'] == 'paid')) ?>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Pending Payments</h6>
                                <h4 class="mb-0">
                                    <?= count(array_filter($tickets, fn($t) => $t['payment_status'] == 'pending')) ?>
                                </h4>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize datepickers
        flatpickr("input[type='date']", {
            dateFormat: "Y-m-d",
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

                // Confirm delete
        function confirmDelete(ticketId) {
            if (confirm('Are you sure you want to delete ticket ' + ticketId + '?\nThis action cannot be undone.')) {
                window.location.href = 'delete_ticket.php?id=' + ticketId;
            }
        }

        // Export function
        function exportTickets() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'export_tickets.php?' + params.toString();
        }

        // Quick filter by date
        function quickFilter(days) {
            const today = new Date();
            const fromDate = new Date();
            fromDate.setDate(today.getDate() - days);
            
            document.querySelector('[name="date_from"]').value = fromDate.toISOString().split('T')[0];
            document.querySelector('[name="date_to"]').value = today.toISOString().split('T')[0];
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>