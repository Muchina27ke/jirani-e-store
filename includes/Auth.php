<?php
/**
 * Enhanced Authentication System for Jirani Platform
 * Handles user authentication, authorization, and session management
 */

class Auth
{
    private $conn;
    private $user;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeSession();
    }

    /**
     * Initialize session and load user data
     */
    private function initializeSession()
    {
        // Do not start session here; config/config.php handles it
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        // Load user data if logged in
        if (isset($_SESSION['user_id'])) {
            $this->loadUser($_SESSION['user_id']);
        }
    }

    /**
     * Load user data from database
     */
    private function loadUser($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT u.*, v.business_name, v.status as vendor_status 
            FROM users u 
            LEFT JOIN vendors v ON u.id = v.user_id 
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $this->user = $result->fetch_assoc();
        } else {
            $this->logout();
        }
    }

    /**
     * Authenticate user with email/phone and password
     */
    public function login($identifier, $password, $rememberMe = false)
    {
        // Check if identifier is email or phone
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE {$field} = ? AND verified = 1");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->logActivity(null, 'login_failed', "Failed login attempt for {$identifier}");
            return ['success' => false, 'message' => 'Invalid credentials or account not verified'];
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            $this->logActivity($user['id'], 'login_failed', 'Invalid password');
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Handle remember me
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days

            // Store token in database
            $stmt = $this->conn->prepare("
                INSERT INTO remember_tokens (user_id, token, expires_at) 
                VALUES (?, ?, FROM_UNIXTIME(?))
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
            ");
            $stmt->bind_param("isi", $user['id'], $token, $expiry);
            $stmt->execute();

            // Set cookie
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
        }

        // Update last login
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();

        $this->logActivity($user['id'], 'login_success', 'User logged in successfully');
        $this->loadUser($user['id']);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Register new user
     */
    public function register($data)
    {
        // Validate required fields
        $required = ['name', 'phone', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field {$field} is required"];
            }
        }

        // Validate phone format
        if (!preg_match('/^(\+254|0)[7-9]\d{8}$/', $data['phone'])) {
            return ['success' => false, 'message' => 'Invalid phone number format'];
        }

        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Check if user already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE phone = ? OR (email = ? AND email IS NOT NULL)");
        $stmt->bind_param("ss", $data['phone'], $data['email']);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'User with this phone or email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->conn->prepare("
            INSERT INTO users (name, phone, email, password, role, verified) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $role = $data['role'] ?? 'customer';
        $verified = ($role === 'customer') ? 1 : 0; // Auto-verify customers, vendors need manual verification

        $stmt->bind_param(
            "sssssi",
            $data['name'],
            $data['phone'],
            $data['email'],
            $hashedPassword,
            $role,
            $verified
        );

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to create account'];
        }

        $userId = $this->conn->insert_id;

        // If registering as vendor, create vendor record
        if ($role === 'vendor') {
            $stmt = $this->conn->prepare("
                INSERT INTO vendors (user_id, business_name, status) 
                VALUES (?, ?, 'pending')
            ");
            $businessName = $data['business_name'] ?? $data['name'] . "'s Business";
            $stmt->bind_param("is", $userId, $businessName);
            $stmt->execute();
        }

        $this->logActivity($userId, 'user_registered', "New {$role} account created");

        // Send welcome email
        try {
            $emailNotifications = new EmailNotifications($this->conn);
            $emailNotifications->sendWelcomeEmail([
                'id' => $userId,
                'name' => $data['name'],
                'email' => $data['email']
            ], $role);
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
        }

        return ['success' => true, 'user_id' => $userId, 'message' => 'Account created successfully'];
    }

    /**
     * Logout user
     */
    public function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Remove remember token if set
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        $_SESSION = [];
        session_unset();
        session_destroy();
        $this->user = null;
        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return $this->user !== null;
    }

    /**
     * Get current user data
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get vendor data for current user
     */
    public function getVendor()
    {
        if (!$this->user || $this->user['role'] !== 'vendor') {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT * FROM vendors WHERE user_id = ?");
        $stmt->bind_param("i", $this->user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole($role)
    {
        return $this->user && $this->user['role'] === $role;
    }

    /**
     * Require specific role or redirect
     */
    public function requireRole($role, $redirectUrl = null)
    {
        if (!$this->isLoggedIn()) {
            if ($redirectUrl) {
                header("Location: {$redirectUrl}");
            } else {
                header("Location: " . SITE_URL . "login.php");
            }
            exit;
        }

        if (!$this->hasRole($role)) {
            if ($redirectUrl) {
                header("Location: {$redirectUrl}");
            } else {
                header("HTTP/1.1 403 Forbidden");
                die("Access denied. Required role: {$role}");
            }
            exit;
        }
    }

    /**
     * Check if vendor is verified
     */
    public function isVendorVerified()
    {
        if (!$this->hasRole('vendor')) {
            return false;
        }

        $vendor = $this->getVendor();
        return $vendor && $vendor['status'] === 'approved';
    }

    /**
     * Require vendor verification
     */
    public function requireVendorVerification($redirectUrl = null)
    {
        $this->requireRole('vendor');

        if (!$this->isVendorVerified()) {
            if ($redirectUrl) {
                header("Location: {$redirectUrl}");
            } else {
                header("Location: " . SITE_URL . "seller/verification.php");
            }
            exit;
        }
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($identifier)
    {
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE {$field} = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $code = sprintf("%06d", mt_rand(100000, 999999));
        $expiry = time() + (30 * 60); // 30 minutes

        // Store reset token
        $stmt = $this->conn->prepare("
            INSERT INTO password_resets (user_id, token, code, expires_at) 
            VALUES (?, ?, ?, FROM_UNIXTIME(?))
            ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                code = VALUES(code), 
                expires_at = VALUES(expires_at)
        ");
        $stmt->bind_param("issi", $user['id'], $token, $code, $expiry);
        $stmt->execute();

        // Send reset email
        try {
            $emailNotifications = new EmailNotifications($this->conn);
            $emailNotifications->sendPasswordReset($user, $token, $code);
        } catch (Exception $e) {
            error_log("Failed to send password reset email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send reset email'];
        }

        $this->logActivity($user['id'], 'password_reset_requested', 'Password reset token generated');

        return ['success' => true, 'message' => 'Password reset instructions sent'];
    }

    /**
     * Reset password using token or code
     */
    public function resetPassword($tokenOrCode, $newPassword)
    {
        // Check if it's a token (32+ chars) or code (6 digits)
        $isToken = strlen($tokenOrCode) > 10;
        $field = $isToken ? 'token' : 'code';

        $stmt = $this->conn->prepare("
            SELECT pr.*, u.* 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.{$field} = ? AND pr.expires_at > NOW()
        ");
        $stmt->bind_param("s", $tokenOrCode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }

        $data = $result->fetch_assoc();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $data['user_id']);
        $stmt->execute();

        // Delete reset token
        $stmt = $this->conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $data['user_id']);
        $stmt->execute();

        $this->logActivity($data['user_id'], 'password_reset_completed', 'Password reset successfully');

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $details = '')
    {
        $stmt = $this->conn->prepare("
            INSERT INTO logs (user_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("isss", $userId, $action, $details, $ipAddress);
        $stmt->execute();
    }

    /**
     * Check remember token and auto-login
     */
    public function checkRememberToken()
    {
        if (!isset($_COOKIE['remember_token']) || $this->isLoggedIn()) {
            return false;
        }

        $token = $_COOKIE['remember_token'];

        $stmt = $this->conn->prepare("
            SELECT rt.*, u.* 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();

            // Set session
            $_SESSION['user_id'] = $data['user_id'];
            $_SESSION['user_role'] = $data['role'];
            $_SESSION['login_time'] = time();

            $this->loadUser($data['user_id']);
            $this->logActivity($data['user_id'], 'auto_login', 'Auto-login via remember token');

            return true;
        } else {
            // Invalid token, remove cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }
    }

    /**
     * Static helper methods
     */
    public static function isVendor()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendor';
    }

    public static function isAdmin()
    {
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);
    }

    public static function isCustomer()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
    }
}