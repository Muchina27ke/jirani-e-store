<?php
/**
 * AfriGear.tech Admin — Auth Guard
 * Include at the top of every admin page.
 * Redirects to login if not authenticated as admin.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: login.php?redirect=' . $redirect);
    exit;
}
