<?php
require_once dirname(__DIR__) . "/config/config.php";

// Endpoint for initiating M-Pesa STK Push
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "initiate_stk_push") {
    if (!$user->is_logged_in()) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $phone_number = $data["phone_number"] ?? "";
    $amount = $data["amount"] ?? 0;
    $email = $data["email"] ?? "";
    $narrative = $data["narrative"] ?? "Payment for order";
    $order_id = $data["order_id"] ?? null;

    if (empty($phone_number) || empty($amount) || empty($email) || empty($order_id)) {
        http_response_code(400);
        echo json_encode(["message" => "Phone number, amount, email, and order ID are required."]);
        exit();
    }

    $response = $mpesa->initiate_stk_push($phone_number, $amount, $email, $narrative, $order_id);

    if ($response["success"]) {
        echo json_encode(["message" => "STK Push initiated successfully.", "invoice_id" => $response["invoice_id"]]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => $response["message"]]);
    }
    exit();
}

// Endpoint for M-Pesa callback (IntaSend webhook)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "callback") {
    $callback_data = json_decode(file_get_contents("php://input"), true);

    if ($mpesa->handle_callback($callback_data)) {
        http_response_code(200);
        echo json_encode(["message" => "Callback received and processed."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Failed to process callback."]);
    }
    exit();
}

// Endpoint for releasing escrow funds (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "release_escrow") {
    if (!$user->is_admin()) {
        http_response_code(403);
        echo json_encode(["message" => "Access denied. Admin privileges required."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $order_id = $data["order_id"] ?? null;

    if (empty($order_id)) {
        http_response_code(400);
        echo json_encode(["message" => "Order ID is required."]);
        exit();
    }

    $response = $mpesa->release_escrow_funds($order_id, $_SESSION["user_id"]);

    if ($response["success"]) {
        echo json_encode(["message" => $response["message"]]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => $response["message"]]);
    }
    exit();
}

// Endpoint for reversing escrow funds (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["action"]) && $_GET["action"] == "reverse_escrow") {
    if (!$user->is_admin()) {
        http_response_code(403);
        echo json_encode(["message" => "Access denied. Admin privileges required."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $order_id = $data["order_id"] ?? null;
    $reason = $data["reason"] ?? "";

    if (empty($order_id) || empty($reason)) {
        http_response_code(400);
        echo json_encode(["message" => "Order ID and reason are required."]);
        exit();
    }

    $response = $mpesa->reverse_escrow_funds($order_id, $_SESSION["user_id"], $reason);

    if ($response["success"]) {
        echo json_encode(["message" => $response["message"]]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => $response["message"]]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid request."]);

?>

