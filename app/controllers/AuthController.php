<?php
// app/controllers/AuthController.php

class AuthController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function login($username, $password) {
        try {
            // Check if user exists and is active
            $stmt = $this->db->prepare("
                SELECT id, username, password_hash, role, is_active, failed_login_attempts, lockout_until 
                FROM users 
                WHERE username = ? 
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if user exists
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Check if account is locked
            if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
                $lockoutTime = date('H:i:s', strtotime($user['lockout_until']));
                return ['success' => false, 'message' => "Account locked. Try again after $lockoutTime"];
            }
            
            // Check if account is active
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is deactivated'];
            }
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct - reset failed attempts
                $this->resetFailedAttempts($user['id']);
                
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Store session in database
                $this->storeSession($user['id']);
                
                // Update last login
                $this->updateLastLogin($user['id']);
                
                return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
            } else {
                // Password is wrong - increment failed attempts
                $this->incrementFailedAttempts($user['id']);
                
                $attemptsLeft = MAX_LOGIN_ATTEMPTS - ($user['failed_login_attempts'] + 1);
                
                if ($attemptsLeft <= 0) {
                    // Lock account
                    $this->lockAccount($user['id']);
                    return ['success' => false, 'message' => 'Account locked due to too many failed attempts'];
                }
                
                return ['success' => false, 'message' => "Invalid username or password. $attemptsLeft attempts left"];
            }
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error. Please try again.'];
        }
    }
    
    private function incrementFailedAttempts($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Failed to increment login attempts: " . $e->getMessage());
        }
    }
    
    private function resetFailedAttempts($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET failed_login_attempts = 0,
                    lockout_until = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Failed to reset login attempts: " . $e->getMessage());
        }
    }
    
    private function lockAccount($userId) {
        try {
            $lockoutTime = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $stmt = $this->db->prepare("
                UPDATE users 
                SET lockout_until = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$lockoutTime, $userId]);
        } catch (PDOException $e) {
            error_log("Failed to lock account: " . $e->getMessage());
        }
    }
    
    private function storeSession($userId) {
        try {
            $sessionId = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Remove old sessions for this user (optional)
            $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?")
                     ->execute([$userId]);
            
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$sessionId, $userId, $ip, $userAgent]);
        } catch (PDOException $e) {
            error_log("Failed to store session: " . $e->getMessage());
        }
    }
    
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_login = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
    
    public function isAuthenticated() {
        // Check if session variables are set
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        // Verify session exists in database (optional security)
        if (defined('VERIFY_SESSION_DB') && VERIFY_SESSION_DB === true) {
            try {
                $stmt = $this->db->prepare("
                    SELECT 1 FROM user_sessions 
                    WHERE session_id = ? AND user_id = ? 
                    AND last_activity > NOW() - INTERVAL ? MINUTE
                ");
                $stmt->execute([session_id(), $_SESSION['user_id'], (SESSION_TIMEOUT / 60)]);
                
                if (!$stmt->fetch()) {
                    return false;
                }
                
                // Update last activity
                $this->updateSessionActivity();
            } catch (PDOException $e) {
                error_log("Session verification error: " . $e->getMessage());
                // Continue without DB verification on error
            }
        }
        
        return true;
    }
    
    public function logout() {
        try {
            // Remove session from database
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
            $stmt->execute([session_id()]);
        } catch (PDOException $e) {
            error_log("Failed to delete session: " . $e->getMessage());
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    private function updateSessionActivity() {
        try {
            $stmt = $this->db->prepare("
                UPDATE user_sessions 
                SET last_activity = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([session_id()]);
        } catch (PDOException $e) {
            error_log("Failed to update session activity: " . $e->getMessage());
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$newPasswordHash, $userId]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error. Please try again.'];
        }
    }
    
    public function createUser($data) {
        // Only admin can create users
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Permission denied'];
        }
        
        try {
            // Validate input
            $required = ['username', 'email', 'password', 'full_name', 'role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "$field is required"];
                }
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email address'];
            }
            
            // Check if username/email already exists
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1
            ");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, email, full_name, role, agency_name, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $passwordHash,
                $data['email'],
                $data['full_name'],
                $data['role'],
                $data['agency_name'] ?? $_SESSION['agency_name'] ?? 'Travel Agency',
                $data['is_active'] ?? 1
            ]);
            
            $userId = $this->db->lastInsertId();
            
            return [
                'success' => true, 
                'message' => 'User created successfully',
                'user_id' => $userId
            ];
            
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'System error. Please try again.'];
        }
    }
    
    public function getUser($userId = null) {
        try {
            if ($userId) {
                // Get specific user
                $stmt = $this->db->prepare("
                    SELECT id, username, email, full_name, role, agency_name, 
                           is_active, created_at, last_login 
                    FROM users 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                return $stmt->fetch();
            } else {
                // Get all users (admin only)
                if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
                    return [];
                }
                
                $stmt = $this->db->prepare("
                    SELECT id, username, email, full_name, role, agency_name, 
                           is_active, created_at, last_login 
                    FROM users 
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                return $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return [];
        }
    }
}
?>