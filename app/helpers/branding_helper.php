<?php
// app/helpers/branding_helper.php

function applyBrandingStyles() {
    static $branding = null;
    
    if ($branding === null) {
        require_once APP_PATH . '/classes/BrandingSettings.php';
        $branding = BrandingSettings::getInstance();
    }
    
    $css = "
    <style>
        :root {
            --primary-color: {$branding->getPrimaryColor()};
            --secondary-color: {$branding->getSecondaryColor()};
        }
        .btn-primary, .bg-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        .btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .border-primary {
            border-color: var(--primary-color) !important;
        }
    </style>
    ";
    
    return $css;
}

function getCompanyLogo() {
    static $branding = null;
    
    if ($branding === null) {
        require_once APP_PATH . '/classes/BrandingSettings.php';
        $branding = BrandingSettings::getInstance();
    }
    
    $logoUrl = $branding->getLogoUrl();
    
    if ($logoUrl) {
        return '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($branding->getCompanyName()) . '" height="40">';
    }
    
    return '<i class="fas fa-ticket-alt"></i> ' . htmlspecialchars($branding->getCompanyName());
}
?>