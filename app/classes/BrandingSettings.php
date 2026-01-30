<?php
// app/classes/BrandingSettings.php

class BrandingSettings {
    private static $instance = null;
    private $settings = [];
    private $cacheTime = 300; // 5 minutes cache
    private $defaultSettings = [
        'brand_name' => 'Ticket System',
        'company_address' => '123 Travel Street, City, Country',
        'company_phone' => '+91 1234567890',
        'company_email' => 'info@ticketsystem.com',
        'website_url' => 'https://ticketsystem.com',
        'primary_color' => '#0d6efd',
        'secondary_color' => '#6c757d',
        'logo_path' => '/assets/logo.png',
        'favicon_path' => '/assets/favicon.ico',
        'terms_conditions' => '<p>1. This is a computer generated ticket, no signature required.</p><p>2. Please carry valid ID proof during journey.</p><p>3. Reporting time: 30 minutes before departure.</p>',
        'footer_text' => '© 2024 Ticket System. All rights reserved.',
        'email_signature' => 'Best Regards,\nTicket System Team',
        'invoice_prefix' => 'INV-',
        'currency_symbol' => '₹',
        'tax_percentage' => '18',
        'enable_email_notifications' => '1',
        'enable_sms_notifications' => '0'
    ];
    
    private function __construct($db = null) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    public static function getInstance($db = null) {
        if (self::$instance === null) {
            self::$instance = new BrandingSettings($db);
        }
        return self::$instance;
    }

    public static function getAllSettings() {
        return self::getInstance()->settings;
    }
    
    private function loadSettings() {
        // Load from cache first
        $cacheFile = dirname(__DIR__) . '/cache/branding_settings.cache';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTime) {
            $this->settings = json_decode(file_get_contents($cacheFile), true);
            return;
        }
        
        // Try to load from database if connection exists
        if ($this->db) {
            try {
                $stmt = $this->db->query("SELECT setting_key, setting_value, setting_type FROM branding_settings");
                $rows = $stmt->fetchAll();
                
                foreach ($rows as $row) {
                    $this->settings[$row['setting_key']] = $this->formatValue($row['setting_value'], $row['setting_type']);
                }
                
                // Save to cache
                if (!is_dir(dirname($cacheFile))) {
                    mkdir(dirname($cacheFile), 0755, true);
                }
                file_put_contents($cacheFile, json_encode($this->settings));
                
            } catch (Exception $e) {
                // If database error, use defaults
                error_log("BrandingSettings DB Error: " . $e->getMessage());
                $this->settings = $this->defaultSettings;
            }
        } else {
            // No database connection, use defaults
            $this->settings = $this->defaultSettings;
        }
    }
    
    private function formatValue($value, $type) {
        switch ($type) {
            case 'number':
                return (float)$value;
            case 'checkbox':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
    
    public function get($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }
    
    public function getAll() {
        return $this->settings;
    }
    
    public function update($key, $value, $type = 'text') {
        if (!$this->db) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO branding_settings (setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?, updated_at = NOW()
            ");
            
            $stmt->execute([$key, $value, $type, $value, $type]);
            
            // Clear cache
            $cacheFile = dirname(__DIR__) . '/cache/branding_settings.cache';
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            
            // Reload settings
            $this->loadSettings();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Update branding setting error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLogoUrl() {
        $logo = $this->get('logo_path', '/assets/logo.png');
        if (strpos($logo, 'http') === 0) {
            return $logo;
        }
        return 'http://localhost:8080' . $logo;
    }
    
    public function getFaviconUrl() {
        $favicon = $this->get('favicon_path', '/assets/favicon.ico');
        if (strpos($favicon, 'http') === 0) {
            return $favicon;
        }
        return 'http://localhost/ticket-system/public' . $favicon;
    }
    
    public function getPrimaryColor() {
        return $this->get('primary_color', '#0d6efd');
    }
    
    public function getSecondaryColor() {
        return $this->get('secondary_color', '#6c757d');
    }
    
    public function getCompanyName() {
        return $this->get('brand_name', 'Ticket System');
    }
    
    public function getTermsConditions() {
        return $this->get('terms_conditions', '');
    }
    
    public function isBrandingEnabled() {
        return !empty($this->settings);
    }
}
?>