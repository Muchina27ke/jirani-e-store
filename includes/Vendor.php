<?php

class Vendor
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function get_all_vendors()
    {
        $query = "SELECT u.id, u.name, u.email, u.phone, v.business_name, v.business_cert, v.status FROM users u JOIN vendors v ON u.id = v.user_id";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_vendor_by_user_id($user_id)
    {
        $query = "SELECT u.id, u.name, u.email, u.phone, v.business_name, v.business_cert, v.status FROM users u JOIN vendors v ON u.id = v.user_id WHERE u.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * @deprecated Use VendorVerification for all admin approval/rejection actions to ensure unified logic and notifications.
     */
    public function update_vendor_status($user_id, $status, $rejection_reason = null)
    {
        // Deprecated: Use VendorVerification for admin approval/rejection
        $query = "UPDATE vendors SET status = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $user_id);
        $success = $stmt->execute();

        if ($success) {
            // Update verification status in verifications table
            $query_verification = "UPDATE verifications SET status = ?, rejection_reason = ? WHERE vendor_id = ?";
            $stmt_verification = $this->conn->prepare($query_verification);
            $stmt_verification->bind_param("ssi", $status, $rejection_reason, $user_id);
            $stmt_verification->execute();

            // Also update user's verified status in users table
            $verified_status = ($status === 'approved') ? 1 : 0;
            $query_user = "UPDATE users SET verified = ? WHERE id = ?";
            $stmt_user = $this->conn->prepare($query_user);
            $stmt_user->bind_param("ii", $verified_status, $user_id);
            $stmt_user->execute();
        }
        return $success;
    }

    public function register_vendor_details($user_id, $business_name, $business_cert)
    {
        $query = "INSERT INTO vendors (user_id, business_name, business_cert, status) VALUES (?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE business_name = VALUES(business_name), business_cert = VALUES(business_cert), status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $business_name, $business_cert);
        $success = $stmt->execute();

        if ($success) {
            // Also insert into verifications table
            $query_verification = "INSERT INTO verifications (vendor_id, business_doc_url, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE business_doc_url = VALUES(business_doc_url), status = 'pending'";
            $stmt_verification = $this->conn->prepare($query_verification);
            $stmt_verification->bind_param("is", $user_id, $business_cert);
            $stmt_verification->execute();
        }
        return $success;
    }

    public function upload_id_document($vendor_id, $id_doc_url)
    {
        $query = "INSERT INTO verifications (vendor_id, id_doc_url, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE id_doc_url = VALUES(id_doc_url), status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $vendor_id, $id_doc_url);
        return $stmt->execute();
    }

    public function get_verification_details($vendor_id)
    {
        $query = "SELECT id_doc_url, business_doc_url, status, rejection_reason FROM verifications WHERE vendor_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function count_active_vendors()
    {
        $query = "SELECT COUNT(*) as active_vendors_count FROM vendors WHERE status = 'approved'";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['active_vendors_count'] ?? 0;
        }
        return 0;
    }

    public function count_pending_verifications()
    {
        $query = "SELECT COUNT(*) as pending_verifications_count FROM verifications WHERE status = 'pending'";
        $result = $this->conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['pending_verifications_count'] ?? 0;
        }
        return 0;
    }

    /**
     * Update a vendor's M-Pesa number.
     *
     * @param int $user_id The ID of the user (vendor).
     * @param string $mpesa_number The M-Pesa number to set.
     * @return bool True on success, false on failure.
     */
    public function update_mpesa_number($user_id, $mpesa_number)
    {
        $query = "UPDATE vendors SET mpesa_number = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $mpesa_number, $user_id);
        return $stmt->execute();
    }

    /**
     * Update vendor and user details.
     * @param int $user_id The vendor's user ID
     * @param array $data Associative array of fields to update
     * @return bool True on success, false on failure
     */
    public function update_vendor($user_id, $data)
    {
        // Update vendors table
        $vendorFields = ['business_name', 'business_cert', 'description', 'location', 'status'];
        $vendorUpdates = [];
        $vendorParams = [];
        foreach ($vendorFields as $field) {
            if (isset($data[$field])) {
                $vendorUpdates[] = "$field = ?";
                $vendorParams[] = $data[$field];
            }
        }
        if ($vendorUpdates) {
            $query = "UPDATE vendors SET " . implode(", ", $vendorUpdates) . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $types = str_repeat('s', count($vendorParams)) . 'i';
            $vendorParams[] = $user_id;
            $stmt->bind_param($types, ...$vendorParams);
            $success = $stmt->execute();
            if (!$success)
                return false;
        }
        // Update users table
        $userFields = ['name', 'email', 'phone'];
        $userUpdates = [];
        $userParams = [];
        foreach ($userFields as $field) {
            if (isset($data[$field])) {
                $userUpdates[] = "$field = ?";
                $userParams[] = $data[$field];
            }
        }
        if ($userUpdates) {
            $query = "UPDATE users SET " . implode(", ", $userUpdates) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $types = str_repeat('s', count($userParams)) . 'i';
            $userParams[] = $user_id;
            $stmt->bind_param($types, ...$userParams);
            $success = $stmt->execute();
            if (!$success)
                return false;
        }
        return true;
    }

    /**
     * Get statistics for a vendor: total products, orders, revenue, and active products.
     * @param int $user_id
     * @return array
     */
    public function get_vendor_statistics($user_id)
    {
        $stats = [
            'total_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0.0,
            'active_products' => 0
        ];
        // Total products
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_products'] = (int) $row['count'];
        }
        // Total orders
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM orders WHERE vendor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_orders'] = (int) $row['count'];
        }
        // Total revenue
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(p.price * oi.quantity), 0) as revenue FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE o.vendor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_revenue'] = (float) $row['revenue'];
        }
        // Active products
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ? AND status = 'active'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['active_products'] = (int) $row['count'];
        }
        return $stats;
    }
}


