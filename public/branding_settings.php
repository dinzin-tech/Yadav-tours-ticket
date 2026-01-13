<?php
// public/branding_settings.php - DEBUG VERSION
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

// Define constants
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load database
require_once APP_PATH . '/config/database.php';

// Initialize settings with defaults
$settings = [
    'agency_name' => '',
    'agency_address' => '',
    'agency_phone' => '',
    'agency_email' => '',
    'website' => '',
    'slogan' => '',
    'brand_color' => '#1e3c72',
    'secondary_color' => '#2a5298',
    'agency_logo' => ''
];

$message = '';
$error = '';

// DEBUG: Check database connection and table structure
try {
    $db = getDB();
    $user_id = $_SESSION['user_id'];
    
    // First, let's check what columns actually exist
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<!-- DEBUG: Found columns in users table: " . implode(', ', $columns) . " -->\n";
    
    // Build a safe SELECT query with only existing columns
    $select_fields = [];
    foreach ($settings as $field => $default) {
        if (in_array($field, $columns)) {
            $select_fields[] = $field;
        }
    }
    
    if (!empty($select_fields)) {
        $select_query = "SELECT " . implode(', ', $select_fields) . " FROM users WHERE id = ?";
        echo "<!-- DEBUG: Query: $select_query -->\n";
        
        $stmt = $db->prepare($select_query);
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            // Update settings with actual data
            foreach ($settings as $field => $default) {
                if (isset($user_data[$field])) {
                    $settings[$field] = $user_data[$field];
                }
            }
        }
    } else {
        echo "<!-- DEBUG: No matching columns found in database -->\n";
    }
    
} catch (Exception $e) {
    echo "<!-- DEBUG: Error fetching data: " . htmlspecialchars($e->getMessage()) . " -->\n";
    $error = "Database error. Please check your connection.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];
        
        // Check columns again for update
        $stmt = $db->query("SHOW COLUMNS FROM users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        // Handle file upload
        $logo_filename = null;
        if (isset($_FILES['agency_logo']) && $_FILES['agency_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = PUBLIC_PATH . '/assets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['agency_logo']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $logo_filename = 'logo_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $logo_filename;
                
                if (move_uploaded_file($_FILES['agency_logo']['tmp_name'], $upload_path)) {
                    // Delete old logo if exists
                    if (!empty($settings['agency_logo']) && $settings['agency_logo'] != $logo_filename) {
                        $old_logo_path = $upload_dir . $settings['agency_logo'];
                        if (file_exists($old_logo_path)) {
                            unlink($old_logo_path);
                        }
                    }
                } else {
                    $error = 'Failed to upload logo.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, PNG, GIF, SVG allowed.';
            }
        }
        
        if (empty($error)) {
            $remove_logo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === 'on';
            
            // Build update fields based on what columns exist
            $update_fields = [];
            $params = [];
            
            $fields_to_update = [
                'agency_name' => $_POST['agency_name'] ?? '',
                'agency_address' => $_POST['agency_address'] ?? '',
                'agency_phone' => $_POST['agency_phone'] ?? '',
                'agency_email' => $_POST['agency_email'] ?? '',
                'website' => $_POST['website'] ?? '',
                'slogan' => $_POST['slogan'] ?? '',
                'brand_color' => $_POST['brand_color'] ?? '#1e3c72',
                'secondary_color' => $_POST['secondary_color'] ?? '#2a5298',
            ];
            
            foreach ($fields_to_update as $field => $value) {
                if (in_array($field, $columns)) {
                    $update_fields[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            // Handle logo
            if (in_array('agency_logo', $columns)) {
                if ($remove_logo) {
                    $update_fields[] = "agency_logo = NULL";
                } elseif ($logo_filename) {
                    $update_fields[] = "agency_logo = ?";
                    $params[] = $logo_filename;
                }
            }
            
            // Add updated_at if exists
            if (in_array('updated_at', $columns)) {
                $update_fields[] = "updated_at = NOW()";
            }
            
            if (!empty($update_fields)) {
                $params[] = $user_id;
                $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
                
                echo "<!-- DEBUG: Update query: $update_query -->\n";
                
                $stmt = $db->prepare($update_query);
                $stmt->execute($params);
                
                $message = 'Branding settings updated successfully!';
                
                // Update local settings
                foreach ($fields_to_update as $field => $value) {
                    if (isset($settings[$field])) {
                        $settings[$field] = $value;
                    }
                }
                
                if ($remove_logo) {
                    $settings['agency_logo'] = '';
                } elseif ($logo_filename) {
                    $settings['agency_logo'] = $logo_filename;
                }
            } else {
                $error = 'No valid fields to update.';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
        echo "<!-- DEBUG: Update error: " . htmlspecialchars($e->getMessage()) . " -->\n";
    }
}

// Remove debug comments for production
// Or you can comment out the echo statements starting with <!-- DEBUG
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/css/bootstrap-colorpicker.min.css">
    <style>
        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
            padding: 30px;
            margin-top: 20px;
        }
        .preview-box {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            background: #f8f9fa;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: inline-block;
            margin-right: 10px;
            border: 1px solid #ddd;
        }
        .logo-preview {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 10px;
        }
        .color-input-group .input-group-text {
            cursor: pointer;
        }
        .ticket-preview-header {
            padding: 15px;
            border-radius: 8px;
            color: white;
            margin-bottom: 15px;
        }
        .ticket-preview-body {
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
        }
        .ticket-status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .footer-actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: <?= htmlspecialchars($settings['brand_color']) ?>">
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
                        <a class="nav-link" href="ticket_list.php"><i class="fas fa-list"></i> All Tickets</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="adminDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="branding_settings.php">
                                <i class="fas fa-palette"></i> Branding Settings
                            </a></li>
                            <li><a class="dropdown-item" href="user_management.php">
                                <i class="fas fa-users"></i> User Management
                            </a></li>
                            <li><a class="dropdown-item" href="system_settings.php">
                                <i class="fas fa-sliders-h"></i> System Settings
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link text-white">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                        <span class="badge bg-danger">Admin</span>
                    </span>
                    <a class="nav-link text-white" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="settings-card">
            <h2 class="mb-4"><i class="fas fa-palette"></i> Branding Settings</h2>
            <p class="text-muted mb-4">Customize your agency's branding for tickets and emails.</p>
            
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Database Status Check -->
            <?php
            try {
                $db = getDB();
                $stmt = $db->query("SHOW COLUMNS FROM users");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                $missing_columns = array_diff(array_keys($settings), $columns);
                
                if (!empty($missing_columns)): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Database Columns Missing</h5>
                    <p>The following columns are missing from your database:</p>
                    <ul>
                        <?php foreach ($missing_columns as $col): ?>
                            <li><code><?= $col ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mb-0">Please run this SQL in your database:</p>
                    <pre class="bg-dark text-light p-2 mt-2 rounded"><code>ALTER TABLE users 
<?php 
$alter_sql = [];
foreach ($missing_columns as $col) {
    switch($col) {
        case 'website':
        case 'slogan':
        case 'agency_name':
        case 'agency_email':
            $alter_sql[] = "ADD COLUMN $col VARCHAR(255) DEFAULT NULL";
            break;
        case 'brand_color':
        case 'secondary_color':
            $alter_sql[] = "ADD COLUMN $col VARCHAR(10) DEFAULT '#1e3c72'";
            break;
        case 'agency_phone':
            $alter_sql[] = "ADD COLUMN $col VARCHAR(50) DEFAULT NULL";
            break;
        case 'agency_address':
            $alter_sql[] = "ADD COLUMN $col TEXT DEFAULT NULL";
            break;
        case 'agency_logo':
            $alter_sql[] = "ADD COLUMN $col VARCHAR(255) DEFAULT NULL";
            break;
    }
}
echo implode(",\n", $alter_sql);
?></code></pre>
                </div>
                <?php endif;
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Database connection error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
            
            <form method="POST" action="" enctype="multipart/form-data" id="brandingForm">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Agency Details -->
                        <h4 class="mb-3"><i class="fas fa-building"></i> Agency Details</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Agency Name *</label>
                            <input type="text" class="form-control" name="agency_name" 
                                   value="<?= htmlspecialchars($settings['agency_name']) ?>" required
                                   placeholder="Enter agency name">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Agency Address</label>
                            <textarea class="form-control" name="agency_address" rows="3"
                                      placeholder="Enter agency address"><?= htmlspecialchars($settings['agency_address']) ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" name="agency_phone" 
                                           value="<?= htmlspecialchars($settings['agency_phone']) ?>"
                                           placeholder="+1 (555) 123-4567">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="agency_email" 
                                           value="<?= htmlspecialchars($settings['agency_email']) ?>"
                                           placeholder="contact@agency.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    <input type="url" class="form-control" name="website" 
                                           value="<?= htmlspecialchars($settings['website']) ?>"
                                           placeholder="https://agency.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slogan/Tagline</label>
                                <input type="text" class="form-control" name="slogan" 
                                       value="<?= htmlspecialchars($settings['slogan']) ?>"
                                       placeholder="Your success is our priority">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Branding -->
                        <h4 class="mb-3"><i class="fas fa-paint-brush"></i> Branding</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Agency Logo</label>
                            <input type="file" class="form-control" name="agency_logo" accept="image/*" id="logoInput">
                            <?php if (!empty($settings['agency_logo'])): 
                                $logo_path = PUBLIC_PATH . '/assets/' . $settings['agency_logo'];
                                if (file_exists($logo_path)):
                            ?>
                            <div class="mt-3">
                                <p class="mb-1"><strong>Current Logo:</strong></p>
                                <img src="../assets/<?= htmlspecialchars($settings['agency_logo']) ?>" 
                                     class="logo-preview" alt="Current Logo" id="currentLogo">
                                <p class="text-muted small mb-0"><?= htmlspecialchars($settings['agency_logo']) ?></p>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" id="removeLogo">
                                    <label class="form-check-label text-danger" for="removeLogo">
                                        Remove logo
                                    </label>
                                </div>
                            </div>
                            <?php endif; endif; ?>
                            <small class="text-muted">Recommended: 200x60px, PNG, JPG or SVG (Max: 2MB)</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Primary/Brand Color</label>
                                <div class="input-group colorpicker-component">
                                    <input type="text" class="form-control color-picker-input" name="brand_color" 
                                           value="<?= htmlspecialchars($settings['brand_color']) ?>"
                                           id="brandColor">
                                    <span class="input-group-text color-preview" id="brandColorPreview" 
                                          style="background: <?= htmlspecialchars($settings['brand_color']) ?>">
                                        <i class="fas fa-eye-dropper"></i>
                                    </span>
                                </div>
                                <small class="text-muted">Used for headers, buttons, and accents</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary Color</label>
                                <div class="input-group colorpicker-component">
                                    <input type="text" class="form-control color-picker-input" name="secondary_color" 
                                           value="<?= htmlspecialchars($settings['secondary_color']) ?>"
                                           id="secondaryColor">
                                    <span class="input-group-text color-preview" id="secondaryColorPreview"
                                          style="background: <?= htmlspecialchars($settings['secondary_color']) ?>">
                                        <i class="fas fa-eye-dropper"></i>
                                    </span>
                                </div>
                                <small class="text-muted">Used for highlights and secondary elements</small>
                            </div>
                        </div>
                        
                        <!-- Live Preview -->
                        <div class="preview-box">
                            <h5><i class="fas fa-eye me-2"></i>Live Preview</h5>
                            <p class="text-muted small mb-3">See how your branding will appear on tickets</p>
                            
                            <div class="ticket-preview-header" id="previewHeader" 
                                 style="background: <?= htmlspecialchars($settings['brand_color']) ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1" id="previewAgencyName" 
                                            style="color: <?= 
                                                $settings['brand_color'] ? 
                                                (hexdec(substr($settings['brand_color'], 1, 2)) * 0.299 + 
                                                 hexdec(substr($settings['brand_color'], 3, 2)) * 0.587 + 
                                                 hexdec(substr($settings['brand_color'], 5, 2)) * 0.114) > 186 ? '#000000' : '#ffffff' 
                                                : '#ffffff' ?>">
                                            <?= htmlspecialchars($settings['agency_name'] ?: 'Your Agency Name') ?>
                                        </h5>
                                        <?php if (!empty($settings['slogan'])): ?>
                                        <small class="opacity-75" id="previewSlogan"
                                               style="color: <?= 
                                                $settings['brand_color'] ? 
                                                (hexdec(substr($settings['brand_color'], 1, 2)) * 0.299 + 
                                                 hexdec(substr($settings['brand_color'], 3, 2)) * 0.587 + 
                                                 hexdec(substr($settings['brand_color'], 5, 2)) * 0.114) > 186 ? '#000000' : '#ffffff' 
                                                : '#ffffff' ?>">
                                            <?= htmlspecialchars($settings['slogan']) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($settings['agency_logo']) && file_exists(PUBLIC_PATH . '/assets/' . $settings['agency_logo'])): ?>
                                    <img src="../assets/<?= htmlspecialchars($settings['agency_logo']) ?>" 
                                         alt="Logo" class="logo-preview" id="previewLogo">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ticket-preview-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1">Ticket #TKT-2023-001</h6>
                                        <p class="text-muted small mb-0">Issue: Website login problem</p>
                                    </div>
                                    <span class="ticket-status-badge" id="previewStatusBadge"
                                          style="background: <?= htmlspecialchars($settings['secondary_color']) ?>; 
                                                 color: <?= 
                                                    $settings['secondary_color'] ? 
                                                    (hexdec(substr($settings['secondary_color'], 1, 2)) * 0.299 + 
                                                     hexdec(substr($settings['secondary_color'], 3, 2)) * 0.587 + 
                                                     hexdec(substr($settings['secondary_color'], 5, 2)) * 0.114) > 186 ? '#000000' : '#ffffff' 
                                                    : '#ffffff' ?>">
                                        Open
                                    </span>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="small mb-1"><strong>Customer:</strong> John Doe</p>
                                        <p class="small mb-1"><strong>Priority:</strong> 
                                            <span style="color: <?= htmlspecialchars($settings['secondary_color']) ?>">High</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="small mb-1"><strong>Created:</strong> Today, 10:30 AM</p>
                                        <p class="small mb-1"><strong>Agent:</strong> Sarah Smith</p>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <p class="small mb-1"><strong>Description:</strong></p>
                                    <p class="small text-muted mb-0">Customer unable to login to the website. Receiving "Invalid credentials" error despite using correct password.</p>
                                </div>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    This preview updates automatically as you type
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Actions -->
                <div class="footer-actions">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset to Defaults
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" onclick="window.location.href='dashboard.php'">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Branding Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-colorpicker@3.4.0/dist/js/bootstrap-colorpicker.min.js"></script>
    
    <script>
    // Function to get contrasting text color
    function getContrastColor(hexcolor) {
        if (!hexcolor) return '#ffffff';
        hexcolor = hexcolor.replace("#", "");
        if (hexcolor.length === 3) {
            hexcolor = hexcolor[0] + hexcolor[0] + hexcolor[1] + hexcolor[1] + hexcolor[2] + hexcolor[2];
        }
        const r = parseInt(hexcolor.substr(0, 2), 16);
        const g = parseInt(hexcolor.substr(2, 2), 16);
        const b = parseInt(hexcolor.substr(4, 2), 16);
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 128) ? '#000000' : '#ffffff';
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize color pickers
        $('.colorpicker-component').colorpicker({
            format: 'hex',
            useAlpha: false
        });
        
        // Update preview when colors change
        $('#brandColor').on('colorpickerChange', function(event) {
            const color = event.color.toString('hex');
            $('#brandColorPreview').css('background', color);
            $('#previewHeader').css('background', color);
            const textColor = getContrastColor(color);
            $('#previewAgencyName, #previewSlogan').css('color', textColor);
            if (color.length === 7) {
                $('#previewSlogan').css('color', textColor + '80');
            }
        });
        
        $('#secondaryColor').on('colorpickerChange', function(event) {
            const color = event.color.toString('hex');
            $('#secondaryColorPreview').css('background', color);
            $('#previewStatusBadge').css('background', color);
            $('#previewStatusBadge').css('color', getContrastColor(color));
        });
        
        // Live preview updates for text fields
        $('input[name="agency_name"]').on('input', function() {
            $('#previewAgencyName').text($(this).val() || 'Your Agency Name');
        });
        
        $('input[name="slogan"]').on('input', function() {
            const slogan = $(this).val();
            if (slogan) {
                $('#previewSlogan').text(slogan).show();
            } else {
                $('#previewSlogan').hide();
            }
        });
        
        // Logo preview
        $('#logoInput').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (!$('#previewLogo').length) {
                        $('#previewHeader .d-flex').append('<img src="" class="logo-preview" id="previewLogo" alt="Logo">');
                    }
                    $('#previewLogo').attr('src', e.target.result).show();
                    $('#currentLogo').hide();
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Remove logo checkbox
        $('#removeLogo').on('change', function() {
            if ($(this).is(':checked')) {
                $('#currentLogo').hide();
                $('#previewLogo').hide();
            } else {
                $('#currentLogo').show();
                if ($('#logoInput').val()) {
                    $('#previewLogo').show();
                }
            }
        });
    });
    
    // Reset form to defaults
    function resetForm() {
        if (confirm('Are you sure you want to reset all branding settings to defaults? This cannot be undone.')) {
            // Reset form values
            document.querySelector('input[name="agency_name"]').value = '';
            document.querySelector('textarea[name="agency_address"]').value = '';
            document.querySelector('input[name="agency_phone"]').value = '';
            document.querySelector('input[name="agency_email"]').value = '';
            document.querySelector('input[name="website"]').value = '';
            document.querySelector('input[name="slogan"]').value = '';
            document.querySelector('input[name="brand_color"]').value = '#1e3c72';
            document.querySelector('input[name="secondary_color"]').value = '#2a5298';
            
            // Reset file input
            document.querySelector('input[name="agency_logo"]').value = '';
            
            // Uncheck remove logo
            document.querySelector('#removeLogo').checked = false;
            
            // Update previews
            $('#brandColorPreview').css('background', '#1e3c72');
            $('#secondaryColorPreview').css('background', '#2a5298');
            $('#previewHeader').css('background', '#1e3c72');
            $('#previewAgencyName').text('Your Agency Name');
            $('#previewSlogan').hide();
            $('#previewLogo').hide();
            $('#currentLogo').show();
            
            // Update status badge
            $('#previewStatusBadge').css('background', '#2a5298');
            $('#previewStatusBadge').css('color', getContrastColor('#2a5298'));
            
            alert('Form has been reset to default values.');
        }
    }
    
    // Form validation
    document.getElementById('brandingForm').addEventListener('submit', function(e) {
        const agencyName = document.querySelector('input[name="agency_name"]').value.trim();
        if (!agencyName) {
            e.preventDefault();
            alert('Agency Name is required!');
            document.querySelector('input[name="agency_name"]').focus();
            return false;
        }
        
        // Validate color format
        const brandColor = document.querySelector('input[name="brand_color"]').value;
        const secondaryColor = document.querySelector('input[name="secondary_color"]').value;
        const hexColorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        
        if (!hexColorRegex.test(brandColor)) {
            e.preventDefault();
            alert('Please enter a valid hex color code for Brand Color (e.g., #1e3c72)');
            document.querySelector('input[name="brand_color"]').focus();
            return false;
        }
        
        if (!hexColorRegex.test(secondaryColor)) {
            e.preventDefault();
            alert('Please enter a valid hex color code for Secondary Color (e.g., #2a5298)');
            document.querySelector('input[name="secondary_color"]').focus();
            return false;
        }
        
        return true;
    });
    </script>
    
    <!-- Include jQuery for Bootstrap Colorpicker -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>