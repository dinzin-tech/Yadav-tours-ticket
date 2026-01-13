<?php
// public/admin/branding_settings.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Define constants
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('BASE_URL', 'http://localhost/ticket-system/public');

// Load database
require_once APP_PATH . '/config/database.php';

try {
    $db = getDB();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Load BrandingSettings with database connection
require_once APP_PATH . '/classes/BrandingSettings.php';
$branding = BrandingSettings::getInstance($db);
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle text/color/select fields
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'branding_') === 0) {
                $settingKey = substr($key, 9); // Remove 'branding_' prefix
                $branding->update($settingKey, $value);
            }
        }
        
        // Handle checkbox fields
        $checkboxes = ['enable_email_notifications', 'enable_sms_notifications'];
        foreach ($checkboxes as $checkbox) {
            $value = isset($_POST['branding_' . $checkbox]) ? '1' : '0';
            $branding->update($checkbox, $value, 'checkbox');
        }
        
        // Handle file uploads
        if (isset($_FILES['branding_logo_path']) && $_FILES['branding_logo_path']['error'] === 0) {
            handleFileUpload($db, 'logo_path', $_FILES['branding_logo_path']);
        }
        
        if (isset($_FILES['branding_favicon_path']) && $_FILES['branding_favicon_path']['error'] === 0) {
            handleFileUpload($db, 'favicon_path', $_FILES['branding_favicon_path']);
        }
        
        $message = 'Branding settings updated successfully!';
        
    } catch (Exception $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Helper function for file upload
function handleFileUpload($db, $settingKey, $file) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/ticket-system/public/assets/uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/x-icon'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF, SVG, ICO');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Max size: 2MB');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $settingKey . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativePath = '/assets/uploads/' . $filename;
        
        // Update in database
        $stmt = $db->prepare("
            INSERT INTO branding_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, 'file') 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$settingKey, $relativePath, $relativePath]);
        
        // Clear cache
        $cacheFile = APP_PATH . '/cache/branding_settings.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
    } else {
        throw new Exception('Failed to upload file');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings - Ticket System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= $branding->getPrimaryColor() ?>;
            --secondary-color: <?= $branding->getSecondaryColor() ?>;
        }
        .brand-preview {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            background: white;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 10px;
            border: 1px solid #ddd;
        }
        .logo-preview {
            max-height: 80px;
            max-width: 200px;
            margin: 10px 0;
        }
        .setting-group {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .preview-header {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation for Admin -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-user-shield me-1"></i> Admin Panel
            </span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-palette"></i> Branding Settings</h2>
                <p class="text-muted">Customize the appearance and information of your ticket system</p>
                
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" id="brandingForm">
                    <!-- Branding Preview -->
                    <div class="brand-preview">
                        <h4>Live Preview</h4>
                        <div class="preview-header">
                            <div class="d-flex align-items-center">
                                <?php if ($branding->getLogoUrl()): ?>
                                <img src="<?= htmlspecialchars($branding->getLogoUrl()) ?>" alt="Logo" class="logo-preview me-3">
                                <?php endif; ?>
                                <h4 class="mb-0"><?= htmlspecialchars($branding->getCompanyName()) ?></h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <strong>Primary Color:</strong>
                                    <div class="color-preview" style="background-color: <?= $branding->getPrimaryColor() ?>"></div>
                                    <?= $branding->getPrimaryColor() ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <strong>Secondary Color:</strong>
                                    <div class="color-preview" style="background-color: <?= $branding->getSecondaryColor() ?>"></div>
                                    <?= $branding->getSecondaryColor() ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <strong>Contact:</strong>
                                    <div class="small"><?= htmlspecialchars($branding->get('company_phone')) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- General Settings -->
                    <div class="setting-group">
                        <h4><i class="fas fa-cog"></i> General Settings</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Brand Name</label>
                                    <input type="text" class="form-control" name="branding_brand_name" 
                                           value="<?= htmlspecialchars($branding->get('brand_name')) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Footer Text</label>
                                    <input type="text" class="form-control" name="branding_footer_text" 
                                           value="<?= htmlspecialchars($branding->get('footer_text')) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance -->
                    <div class="setting-group">
                        <h4><i class="fas fa-paint-brush"></i> Appearance</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Primary Color</label>
                                    <input type="color" class="form-control form-control-color" name="branding_primary_color" 
                                           value="<?= htmlspecialchars($branding->getPrimaryColor()) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Secondary Color</label>
                                    <input type="color" class="form-control form-control-color" name="branding_secondary_color" 
                                           value="<?= htmlspecialchars($branding->getSecondaryColor()) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Logo</label>
                                    <input type="file" class="form-control" name="branding_logo_path" 
                                           accept="image/*">
                                    <?php if ($branding->get('logo_path')): ?>
                                    <small class="text-muted">Current: <?= basename($branding->get('logo_path')) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Favicon</label>
                                    <input type="file" class="form-control" name="branding_favicon_path" 
                                           accept="image/x-icon,image/png">
                                    <?php if ($branding->get('favicon_path')): ?>
                                    <small class="text-muted">Current: <?= basename($branding->get('favicon_path')) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="setting-group">
                        <h4><i class="fas fa-address-book"></i> Contact Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Address</label>
                                    <textarea class="form-control" name="branding_company_address" rows="3"><?= htmlspecialchars($branding->get('company_address')) ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="branding_company_phone" 
                                           value="<?= htmlspecialchars($branding->get('company_phone')) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="branding_company_email" 
                                           value="<?= htmlspecialchars($branding->get('company_email')) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Website URL</label>
                                    <input type="url" class="form-control" name="branding_website_url" 
                                           value="<?= htmlspecialchars($branding->get('website_url')) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Legal Documents -->
                    <div class="setting-group">
                        <h4><i class="fas fa-file-contract"></i> Legal Documents</h4>
                        <div class="mb-3">
                            <label class="form-label">Terms & Conditions</label>
                            <textarea class="form-control" name="branding_terms_conditions" rows="6"><?= htmlspecialchars($branding->getTermsConditions()) ?></textarea>
                            <small class="text-muted">HTML is allowed. Use for ticket printouts and invoices.</small>
                        </div>
                    </div>
                    
                    <!-- Invoice Settings -->
                    <div class="setting-group">
                        <h4><i class="fas fa-file-invoice"></i> Invoice Settings</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Invoice Prefix</label>
                                    <input type="text" class="form-control" name="branding_invoice_prefix" 
                                           value="<?= htmlspecialchars($branding->get('invoice_prefix')) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Currency Symbol</label>
                                    <input type="text" class="form-control" name="branding_currency_symbol" 
                                           value="<?= htmlspecialchars($branding->get('currency_symbol')) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Tax Percentage (%)</label>
                                    <input type="number" class="form-control" name="branding_tax_percentage" 
                                           value="<?= htmlspecialchars($branding->get('tax_percentage')) ?>" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <div class="setting-group">
                        <h4><i class="fas fa-toggle-on"></i> Features</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="branding_enable_email_notifications" 
                                           value="1" <?= $branding->get('enable_email_notifications') ? 'checked' : '' ?>>
                                    <label class="form-check-label">Email Notifications</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="branding_enable_sms_notifications" 
                                           value="1" <?= $branding->get('enable_sms_notifications') ? 'checked' : '' ?>>
                                    <label class="form-check-label">SMS Notifications</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewChanges()">
                            <i class="fas fa-eye"></i> Preview Changes
                        </button>
                        <a href="../dashboard.php" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live preview update
        function updatePreview() {
            const form = document.getElementById('brandingForm');
            const formData = new FormData(form);
            
            // Update colors in preview
            document.documentElement.style.setProperty(
                '--primary-color', 
                formData.get('branding_primary_color') || '#0d6efd'
            );
            document.documentElement.style.setProperty(
                '--secondary-color', 
                formData.get('branding_secondary_color') || '#6c757d'
            );
            
            // Update color preview boxes
            document.querySelectorAll('.color-preview')[0].style.backgroundColor = 
                formData.get('branding_primary_color') || '#0d6efd';
            document.querySelectorAll('.color-preview')[1].style.backgroundColor = 
                formData.get('branding_secondary_color') || '#6c757d';
            
            // Update brand name preview
            const brandName = document.querySelector('[name="branding_brand_name"]').value || 'Ticket System';
            document.querySelector('.preview-header h4').textContent = brandName;
        }
        
        // Attach event listeners
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('input', updatePreview);
            element.addEventListener('change', updatePreview);
        });
        
        // Handle logo preview
        document.querySelector('[name="branding_logo_path"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let img = document.querySelector('.logo-preview');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'logo-preview me-3';
                        document.querySelector('.preview-header .d-flex').prepend(img);
                    }
                    img.src = e.target.result;
                    img.alt = 'Logo Preview';
                }
                reader.readAsDataURL(file);
            }
        });
        
        function previewChanges() {
            alert('Preview will be visible on the dashboard after saving.');
        }
        
        // Initial preview update
        updatePreview();
    </script>
</body>
</html>