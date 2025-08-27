<?php
require_once __DIR__ . "/config/config.php";

$db = getDbConnection();

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'logout':
        $user->logout();
        header("Location: Signin/index.php?logged_out=true");
        exit();

    case 'get_users':
        if (!$user->is_logged_in() || !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }

        $users = $user->get_all_users();
        header('Content-Type: application/json');
        echo json_encode($users);
        exit();

    case 'update_role':
        if (!$user->is_logged_in() || !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'] ?? null;
        $role = $data['role'] ?? null;

        if (empty($user_id) || empty($role)) {
            http_response_code(400);
            echo json_encode(["message" => "User ID and role are required."]);
            exit();
        }

        if ($user->update_user_role($user_id, $role)) {
            echo json_encode(["message" => "Role updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update role."]);
        }
        exit();

    case 'update_verification':
        if (!$user->is_logged_in() || !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'] ?? null;
        $status = $data['status'] ?? null;

        if (empty($user_id) || $status === null) {
            http_response_code(400);
            echo json_encode(["message" => "User ID and status are required."]);
            exit();
        }

        if ($user->update_verification_status($user_id, $status)) {
            echo json_encode(["message" => "Verification status updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update verification status."]);
        }
        exit();

    case 'delete_user':
        if (!$user->is_logged_in() || !$user->is_admin()) {
            http_response_code(403);
            echo json_encode(["message" => "Access denied."]);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'] ?? null;

        if (empty($user_id)) {
            http_response_code(400);
            echo json_encode(["message" => "User ID is required."]);
            exit();
        }

        if ($user->delete_user($user_id)) {
            echo json_encode(["message" => "User deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete user."]);
        }
        exit();

    default:
        // If no action specified, redirect to appropriate page
        if ($user->is_logged_in()) {
            if ($user->is_admin()) {
                header("Location: admin/dashboard.php");
            } elseif ($user->is_vendor()) {
                header("Location: seller/dashboard.php");
            } else {
                header("Location: index.php");
            }
        } else {
            header("Location: Signin/index.php");
        }
        exit();
}
?>