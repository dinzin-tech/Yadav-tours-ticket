<?php
// app/config/security.php
// ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1); // Enable only on HTTPS
// ini_set('session.use_only_cookies', 1);
// ini_set('session.cookie_samesite', 'Strict');

class Security {
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validatePNR($pnr, $type) {
        switch($type) {
            case 'train':
                return preg_match('/^[A-Z0-9]{10}$/', $pnr);
            case 'flight':
                return preg_match('/^[A-Z0-9]{6}$/', $pnr);
            case 'bus':
                return preg_match('/^[A-Z0-9]{8,12}$/', $pnr);
            default:
                return true;
        }
    }
}
?>