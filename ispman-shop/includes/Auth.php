<?php
/**
 * AfriGear.tech — Auth class
 * Handles customer registration, login, logout, session management.
 */
class Auth {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Register a new customer.
     * Returns new user ID on success, false on failure.
     */
    public function register(string $name, string $email, string $phone, string $password): int|false {
        // Check email not already taken
        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) return false; // duplicate

        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, phone, password, role)
            VALUES (:name, :email, :phone, :password, 'customer')
        ");
        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':phone'    => $phone,
            ':password' => $this->hashPassword($password),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Attempt login. Returns user array on success, false on failure.
     */
    public function login(string $email, string $password): array|false {
        $stmt = $this->pdo->prepare("
            SELECT id, name, email, phone, password, role
            FROM users WHERE email = :email LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            return false;
        }
        return $user;
    }

    /** Destroy session and log out. */
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']);
    }

    public function isAdmin(): bool {
        return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public function currentUser(): array|null {
        if (!$this->isLoggedIn()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name']  ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'phone' => $_SESSION['user_phone'] ?? '',
            'role'  => $_SESSION['user_role']  ?? 'customer',
        ];
    }

    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /** Write user data into session after successful login. */
    public function setSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_role']  = $user['role'];
    }
}
