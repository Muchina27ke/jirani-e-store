<?php

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function register($name, $phone, $email, $password, $role = 'customer')
    {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);

        $query = "INSERT INTO users (name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssss", $name, $phone, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            return true;
        } else {
            // Log error for debugging
            error_log("User registration failed: " . $stmt->error);
            return false;
        }
    }

    public function login($email_or_phone, $password)
    {
        $query = "SELECT id, name, phone, email, password, role, verified FROM users WHERE email = ? OR phone = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $email_or_phone, $email_or_phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Start session and set user data
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_verified'] = $user['verified'];
                return true;
            }
        }
        return false;
    }

    public function logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Remove remember token if set
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }


        // Clear all session data



        $_SESSION = [];
        session_unset();
        session_destroy();
        return true;
    }

    public function is_logged_in()
    {
        return isset($_SESSION['user_id']);
    }

    public function get_user_role()
    {
        return $_SESSION['user_role'] ?? null;
    }

    public function is_admin()
    {
        return $this->get_user_role() === 'admin' || $this->get_user_role() === 'super_admin';
    }

    public function is_vendor()
    {
        return $this->get_user_role() === 'vendor';
    }

    public function is_customer()
    {
        return $this->get_user_role() === 'customer';
    }

    public function get_all_users()
    {
        $query = "SELECT id, name, phone, email, role, verified, created_at FROM users";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_user_by_id($id)
    {
        $query = "SELECT id, name, phone, email, role, verified FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function update_user_role($user_id, $new_role)
    {
        $query = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $new_role, $user_id);
        return $stmt->execute();
    }

    public function update_user_verification_status($user_id, $status)
    {
        $query = "UPDATE users SET verified = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $status, $user_id);
        return $stmt->execute();
    }

    public function delete_user($user_id)
    {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
}


