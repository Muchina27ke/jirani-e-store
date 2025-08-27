<?php

class Order
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create_order($customer_id, $vendor_id, $delivery_address, $location_lat, $location_lng, $items)
    {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Insert into orders table
            $query_order = "INSERT INTO orders (customer_id, vendor_id, delivery_address, location_lat, location_lng) VALUES (?, ?, ?, ?, ?)";
            $stmt_order = $this->conn->prepare($query_order);
            $stmt_order->bind_param("iissd", $customer_id, $vendor_id, $delivery_address, $location_lat, $location_lng);
            $stmt_order->execute();
            $order_id = $this->conn->insert_id;

            // Insert into order_items table
            foreach ($items as $item) {
                $query_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt_item = $this->conn->prepare($query_item);
                $stmt_item->bind_param("iiid", $order_id, $item["product_id"], $item["quantity"], $item["price"]);
                $stmt_item->execute();
            }

            // Calculate total amount for the order
            $total_amount = 0;
            $query_items = "SELECT quantity, price FROM order_items WHERE order_id = ?";
            $stmt_items = $this->conn->prepare($query_items);
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $result = $stmt_items->get_result();
            while ($row = $result->fetch_assoc()) {
                $total_amount += $row['quantity'] * $row['price'];
            }

            // After successful order creation, handle payment and escrow
            // These values will eventually come from the M-Pesa API response
            $mpesa_transaction_id = "PENDING_" . $order_id; // Placeholder
            $payment_method = "M-Pesa";
            $payment_status = "Pending"; // Initial status for escrow payment
            $escrow_status = "Held";

            if (!$this->_handle_payment_and_escrow($order_id, $total_amount, $mpesa_transaction_id, $payment_method, $payment_status, $escrow_status)) {
                throw new Exception("Failed to create payment or escrow record.");
            }

            $this->conn->commit();
            return $order_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Order creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function get_order_by_id($order_id)
    {
        $query = "SELECT o.*, u.name as customer_name, v.business_name as vendor_name FROM orders o JOIN users u ON o.customer_id = u.id JOIN vendors v ON o.vendor_id = v.user_id WHERE o.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if ($order) {
            $query_items = "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
            $stmt_items = $this->conn->prepare($query_items);
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $order["items"] = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return $order;
    }

    public function get_orders_by_customer($customer_id)
    {
        $query = "SELECT o.*, v.business_name as vendor_name FROM orders o JOIN vendors v ON o.vendor_id = v.user_id WHERE o.customer_id = ? ORDER BY o.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_orders_by_vendor($vendor_id)
    {
        $query = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                (
                    SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id = o.id
                ) as total_items,
                (
                    SELECT SUM(quantity * price) FROM order_items oi WHERE oi.order_id = o.id
                ) as total_amount
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.vendor_id = ?
            ORDER BY o.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_all_orders()
    {
        $query = "SELECT o.*, u.name as customer_name, v.business_name as vendor_name FROM orders o JOIN users u ON o.customer_id = u.id JOIN vendors v ON o.vendor_id = v.user_id ORDER BY o.created_at DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function update_order_status($order_id, $status, $cancellation_reason = null)
    {
        $query = "UPDATE orders SET status = ?";
        $params = "si";
        $values = [$status, $order_id];

        if ($status === 'cancelled' && $cancellation_reason !== null) {
            $query .= ", cancellation_reason = ?";
            $params .= "s";
            $values = [$status, $cancellation_reason, $order_id];
        }

        $query .= " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($params, ...$values);
        return $stmt->execute();
    }

    private function _handle_payment_and_escrow($order_id, $amount, $mpesa_transaction_id, $method, $payment_status, $escrow_status)
    {
        try {
            // Insert into payments table
            $query_payment = "INSERT INTO payments (order_id, amount, mpesa_transaction_id, status, method, is_escrow) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_payment = $this->conn->prepare($query_payment);
            $is_escrow = 1; // Always 1 for escrow payments
            $stmt_payment->bind_param("idsssi", $order_id, $amount, $mpesa_transaction_id, $payment_status, $method, $is_escrow);
            $stmt_payment->execute();
            $payment_id = $this->conn->insert_id;

            // Insert into escrow_payments table
            $query_escrow = "INSERT INTO escrow_payments (order_id, escrow_status) VALUES (?, ?)";
            $stmt_escrow = $this->conn->prepare($query_escrow);
            $stmt_escrow->bind_param("is", $order_id, $escrow_status);
            $stmt_escrow->execute();

            return true;
        } catch (Exception $e) {
            error_log("Payment/Escrow creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function release_escrow($order_id, $released_by)
    {
        try {
            $this->conn->begin_transaction();

            // Update escrow_payments table
            $query_escrow = "UPDATE escrow_payments SET escrow_status = ?, released_by = ?, released_at = NOW() WHERE order_id = ?";
            $stmt_escrow = $this->conn->prepare($query_escrow);
            $status = "Released";
            $stmt_escrow->bind_param("ssi", $status, $released_by, $order_id);
            $stmt_escrow->execute();

            // Update payments table (optional, but good for consistency)
            $query_payment = "UPDATE payments SET status = ? WHERE order_id = ? AND is_escrow = 1";
            $stmt_payment = $this->conn->prepare($query_payment);
            $payment_status = "Completed";
            $stmt_payment->bind_param("si", $payment_status, $order_id);
            $stmt_payment->execute();

            // --- M-Pesa Daraja B2C Logic ---
            $vendor = $this->get_order_vendor($order_id);
            $payment = $this->get_order_payment($order_id);

            if ($vendor && $payment) {
                // Use Daraja M-Pesa API for escrow release
                global $darajaMpesa;
                if (!$darajaMpesa) {
                    throw new Exception("Daraja M-Pesa API not initialized");
                }
                
                $vendor_phone = $vendor['phone'];
                $amount = $payment['amount'];
                $remarks = 'Escrow Release for Order #' . $order_id;
                $occasion = 'Jirani Payout';
                
                $b2cResult = $darajaMpesa->b2cPayment(
                    $vendor_phone,
                    $amount,
                    $remarks,
                    $occasion
                );

                if (!$b2cResult['success']) {
                    throw new Exception("M-Pesa B2C payout failed: " . ($b2cResult['message'] ?? 'Unknown error'));
                }
            } else {
                throw new Exception("Vendor or payment details missing for B2C payout.");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Escrow release failed: " . $e->getMessage());
            return false;
        }
    }

    public function reverse_escrow($order_id, $reversed_by, $reason = "")
    {
        try {
            $this->conn->begin_transaction();

            // Update escrow_payments table
            $query_escrow = "UPDATE escrow_payments SET escrow_status = ?, released_by = ?, released_at = NOW(), reversal_reason = ? WHERE order_id = ?";
            $stmt_escrow = $this->conn->prepare($query_escrow);
            $status = "Reversed";
            $stmt_escrow->bind_param("sssi", $status, $reversed_by, $reason, $order_id);
            $stmt_escrow->execute();

            // Update payments table
            $query_payment = "UPDATE payments SET status = ? WHERE order_id = ? AND is_escrow = 1";
            $stmt_payment = $this->conn->prepare($query_payment);
            $payment_status = "Reversed";
            $stmt_payment->bind_param("si", $payment_status, $order_id);
            $stmt_payment->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Escrow reversal failed: " . $e->getMessage());
            return false;
        }
    }

    public function get_total_escrow_amount()
    {
        $query = "SELECT SUM(p.amount) as total_escrow_amount FROM escrow_payments ep JOIN payments p ON ep.order_id = p.order_id WHERE ep.escrow_status = 'Held' AND p.is_escrow = 1";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total_escrow_amount'] ?? 0;
        }
        return 0;
    }

    public function get_order_items($order_id)
    {
        $query = "SELECT oi.*, p.name as product_name, p.image as product_image
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_order_payment($order_id)
    {
        $query = "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function log_order_action($order_id, $action, $details = [])
    {
        $admin_id = isset($details['admin_id']) ? $details['admin_id'] : null;
        $details_json = json_encode($details);
        $query = "INSERT INTO order_logs (order_id, action, details, admin_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issi", $order_id, $action, $details_json, $admin_id);
        $stmt->execute();
    }

    public function get_order_customer($order_id)
    {
        $query = "SELECT u.* FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function get_order_vendor($order_id)
    {
        $query = "SELECT v.*, u.phone, u.email FROM orders o 
                  JOIN vendors v ON o.vendor_id = v.user_id 
                  JOIN users u ON v.user_id = u.id
                  WHERE o.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function get_order_status($order_id)
    {
        $query = "SELECT status FROM orders WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? $row['status'] : null;
    }

    // Get payment by payment ID
    public function get_payment_by_id($paymentId)
    {
        $query = "SELECT * FROM payments WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Refund payment (calls M-Pesa refund if needed, updates DB)
    public function refund_payment($paymentId, $reason = 'Refunded by admin')
    {
        $payment = $this->get_payment_by_id($paymentId);
        if (!$payment)
            return false;

        // Only refund if M-Pesa and completed
        if (strtolower($payment['method']) === 'mpesa' && $payment['status'] === 'completed') {
            // Get customer phone
            $order = $this->get_order_by_id($payment['order_id']);
            if (!$order)
                return false;
            $customer = $this->get_order_customer($payment['order_id']);
            if (!$customer || empty($customer['phone']))
                return false;

            require_once dirname(__DIR__) . '/includes/Mpesa.php';
            $mpesa = new Mpesa($this->conn, MPESA_API_KEY, MPESA_PUBLISHABLE_KEY, MPESA_ENV);
            $b2cResult = $mpesa->b2cPaymentDaraja($customer['phone'], $payment['amount'], 'Refund for Order #' . $payment['order_id'], 'Jirani Refund');
            if (!$b2cResult['success'])
                return false;
        }

        // Update payment status
        $stmt = $this->conn->prepare("UPDATE payments SET status = 'refunded', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        return true;
    }

    // Update payment status
    public function update_payment_status($paymentId, $newStatus)
    {
        $stmt = $this->conn->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $paymentId);
        return $stmt->execute();
    }

    // Log payment action
    public function log_payment_action($orderId, $action, $details = [])
    {
        $admin_id = isset($details['admin_id']) ? $details['admin_id'] : null;
        $details_json = json_encode($details);
        $query = "INSERT INTO payment_logs (order_id, action, data, admin_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issi", $orderId, $action, $details_json, $admin_id);
        $stmt->execute();
    }

    // Get payments with filters (for export and table reload)
    public function get_payments_with_filters($filters = [])
    {
        $where = [];
        $params = [];
        $types = '';
        if (!empty($filters['status'])) {
            $where[] = "p.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['method'])) {
            $where[] = "p.method = ?";
            $params[] = $filters['method'];
            $types .= 's';
        }
        if (isset($filters['escrow']) && $filters['escrow'] !== '') {
            $where[] = "p.is_escrow = ?";
            $params[] = $filters['escrow'];
            $types .= 'i';
        }
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(p.created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(p.created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        $where_clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $query = "SELECT p.*, o.id as order_id, o.status as order_status, u.name as customer_name, v.business_name as vendor_name, ep.escrow_status FROM payments p LEFT JOIN orders o ON p.order_id = o.id LEFT JOIN users u ON o.customer_id = u.id LEFT JOIN vendors v ON o.vendor_id = v.user_id LEFT JOIN escrow_payments ep ON p.order_id = ep.order_id $where_clause ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        return $payments;
    }

    // Get payment statistics
    public function get_payment_statistics()
    {
        $query = "SELECT COUNT(*) as total_payments, SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed_payments, SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_payments, COALESCE(SUM(CASE WHEN p.is_escrow = 1 AND p.status = 'pending' THEN p.amount ELSE 0 END), 0) as escrow_amount FROM payments p";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_assoc() : [
            'total_payments' => 0,
            'completed_payments' => 0,
            'pending_payments' => 0,
            'escrow_amount' => 0
        ];
    }

    public function mark_cod_order_delivered($order_id, $vendor_id)
    {
        require dirname(__DIR__) . '/config/config.php';
        // Get payment and order info
        $stmt = $this->conn->prepare("SELECT p.id as payment_id, p.amount, p.method, o.vendor_id FROM payments p JOIN orders o ON p.order_id = o.id WHERE o.id = ? AND o.vendor_id = ? AND p.method = 'cash_on_delivery' LIMIT 1");
        $stmt->bind_param("ii", $order_id, $vendor_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        if (!$payment)
            return false;
        $commissionRate = isset($globalSettings['commission_rate']) ? floatval($globalSettings['commission_rate']) : 5.0;
        $orderAmount = floatval($payment['amount']);
        $commission = round($orderAmount * ($commissionRate / 100), 2);
        $vendorPayout = $orderAmount - $commission;
        // Mark payment as paid
        $stmt = $this->conn->prepare("UPDATE payments SET status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $payment['payment_id']);
        $stmt->execute();
        // Mark order as delivered
        $stmt = $this->conn->prepare("UPDATE orders SET status = 'delivered', payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        // Store commission record
        $stmt = $this->conn->prepare("INSERT INTO commissions (order_id, vendor_id, commission_amount, rate, payout_amount, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiddd", $order_id, $vendor_id, $commission, $commissionRate, $vendorPayout);
        $stmt->execute();
        return true;
    }
}


