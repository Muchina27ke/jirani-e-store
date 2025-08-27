<?php
class Security
{
    private $db;
    private $tokenExpiry = 3600; // 1 hour

    public function __construct($db)
    {
        $this->db = $db;
        // Removed session_start() from here
    }

    public function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }

        if (time() - $_SESSION['csrf_token_time'] > $this->tokenExpiry) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }

    public function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhone($phone)
    {
        // Kenyan phone number format
        return preg_match('/^(?:\+254|0)[17]\d{8}$/', $phone);
    }

    public function validateAmount($amount)
    {
        return is_numeric($amount) && $amount > 0;
    }

    public function logSecurityEvent($event, $details)
    {
        $stmt = $this->db->prepare("
            INSERT INTO security_logs 
            (event, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $event,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    }

    public function checkRateLimit($action, $userId, $maxAttempts = 5, $timeWindow = 300)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts
            FROM rate_limits
            WHERE user_id = ? 
            AND action = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");

        $stmt->execute([$userId, $action, $timeWindow]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['attempts'] >= $maxAttempts) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (user_id, action, created_at)
            VALUES (?, ?, NOW())
        ");

        $stmt->execute([$userId, $action]);
        return true;
    }

    public function validateSession()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT last_activity 
            FROM user_sessions 
            WHERE user_id = ? 
            AND session_id = ?
        ");

        $stmt->execute([$_SESSION['user_id'], session_id()]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        // Update last activity
        $stmt = $this->db->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = ? 
            AND session_id = ?
        ");

        $stmt->execute([$_SESSION['user_id'], session_id()]);
        return true;
    }
}