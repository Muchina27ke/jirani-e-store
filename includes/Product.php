<?php

class Product
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function add_product($vendorId, $name, $description, $price, $stock, $location_lat, $location_lng)
    {
        // First, validate that the vendor exists and is approved.
        $vendor_stmt = $this->conn->prepare("SELECT user_id FROM vendors WHERE user_id = ? AND status = 'approved'");
        $vendor_stmt->bind_param('i', $vendorId);
        $vendor_stmt->execute();
        $vendor_result = $vendor_stmt->get_result();
        if ($vendor_result->num_rows === 0) {
            throw new Exception("Vendor with ID $vendorId does not exist or is not approved.");
        }

        // Then, insert the new product.
        $query = "INSERT INTO products (vendor_id, name, description, price, stock, location_lat, location_lng, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $this->conn->prepare($query);
        // Corrected bind_param: Use $vendorId and correct type string 'isdsidd'
        $stmt->bind_param("isdsidd", $vendorId, $name, $description, $price, $stock, $location_lat, $location_lng);
        
        return $stmt->execute();
    }

    public function get_product_by_id($product_id)
    {
        $query = "SELECT p.*, v.business_name as vendor_name, c.name as category_name,
                       (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as order_count,
                       (SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as total_revenue
                FROM products p
                LEFT JOIN vendors v ON p.vendor_id = v.user_id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function get_products_by_vendor($vendor_id)
    {
        $query = "SELECT p.*, c.name as category_name,
                       (SELECT COUNT(*) FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        WHERE oi.product_id = p.id) as total_orders,
                       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        WHERE oi.product_id = p.id) as total_quantity_sold,
                       (SELECT COALESCE(SUM(oi.price * oi.quantity), 0) FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        WHERE oi.product_id = p.id) as total_revenue
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN vendors v ON p.vendor_id = v.user_id
                WHERE p.vendor_id = ?
                  AND p.status = 'active'
                  AND v.status = 'approved'
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_all_products()
    {
        $query = "SELECT p.*, v.business_name as vendor_name, c.name as category_name,
                       (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as order_count,
                       (SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as total_revenue
                FROM products p
                LEFT JOIN vendors v ON p.vendor_id = v.user_id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.stock > 0 AND v.status = 'approved'
                ORDER BY p.created_at DESC";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_products_with_filters($filters = [])
    {
        $where_conditions = [];
        $params = [];
        $param_types = '';

        if (!empty($filters['status'])) {
            $where_conditions[] = "p.status = ?";
            $params[] = $filters['status'];
            $param_types .= 's';
        }

        if (!empty($filters['vendor_id'])) {
            $where_conditions[] = "p.vendor_id = ?";
            $params[] = $filters['vendor_id'];
            $param_types .= 'i';
        }

        if (!empty($filters['category_id'])) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
            $param_types .= 'i';
        }

        if (!empty($filters['price_min'])) {
            $where_conditions[] = "p.price >= ?";
            $params[] = $filters['price_min'];
            $param_types .= 'd';
        }

        if (!empty($filters['price_max'])) {
            $where_conditions[] = "p.price <= ?";
            $params[] = $filters['price_max'];
            $param_types .= 'd';
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $query = "SELECT p.*, v.business_name as vendor_name, c.name as category_name,
                       (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as order_count,
                       (SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id) as total_revenue
                FROM products p
                LEFT JOIN vendors v ON p.vendor_id = v.user_id
                LEFT JOIN categories c ON p.category_id = c.id
                $where_clause
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function update_product($product_id, $data)
    {
        $allowed_fields = ['name', 'description', 'price', 'stock', 'location_lat', 'location_lng', 'status', 'category_id'];
        $update_parts = [];
        $params = [];
        $param_types = '';

        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $update_parts[] = "$field = ?";
                $params[] = $value;
                $param_types .= $this->get_param_type($value);
            }
        }

        if (empty($update_parts)) {
            return false;
        }

        $params[] = $product_id;
        $param_types .= 'i';

        $query = "UPDATE products SET " . implode(', ', $update_parts) . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($param_types, ...$params);
        return $stmt->execute();
    }

    public function update_product_status($product_id, $status)
    {
        $query = "UPDATE products SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $product_id);
        return $stmt->execute();
    }

    public function delete_product($product_id)
    {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        return $stmt->execute();
    }

    public function search_products($keyword)
    {
        $search_keyword = "%" . $keyword . "%";
        $query = "SELECT p.*, v.business_name as vendor_name FROM products p LEFT JOIN vendors v ON p.vendor_id = v.user_id WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.status = 'active' AND p.stock > 0 AND v.status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $search_keyword, $search_keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_product_statistics()
    {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_products,
                    SUM(CASE WHEN stock = 0 OR status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock,
                    COALESCE(SUM(price * stock), 0) as inventory_value,
                    AVG(price) as average_price,
                    COUNT(DISTINCT vendor_id) as unique_vendors
                FROM products";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function get_product_sales_history($product_id, $limit = 10)
    {
        $query = "SELECT o.id as order_id, o.created_at, oi.quantity, oi.price, o.status
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = ?
                ORDER BY o.created_at DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $product_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_product_reviews($product_id)
    {
        $query = "SELECT r.*, u.name as customer_name
                FROM reviews r
                JOIN users u ON r.customer_id = u.id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_inventory_history($product_id, $limit = 20)
    {
        $query = "SELECT * FROM inventory_logs 
                WHERE product_id = ?
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $product_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function bulk_update_products($product_ids, $action)
    {
        if (empty($product_ids)) {
            return 0;
        }

        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

        switch ($action) {
            case 'activate':
                $query = "UPDATE products SET status = 'active' WHERE id IN ($placeholders)";
                break;
            case 'deactivate':
                $query = "UPDATE products SET status = 'inactive' WHERE id IN ($placeholders)";
                break;
            case 'mark_out_of_stock':
                $query = "UPDATE products SET status = 'out_of_stock' WHERE id IN ($placeholders)";
                break;
            default:
                return 0;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function product_has_active_orders($product_id)
    {
        $query = "SELECT COUNT(*) as count FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = ? AND o.status IN ('pending', 'processing', 'shipped')";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    public function get_product_status($product_id)
    {
        $query = "SELECT status FROM products WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['status'] ?? null;
    }

    public function log_product_action($product_id, $action, $details = [])
    {
        $query = "INSERT INTO product_logs (product_id, action, details, admin_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        $details_json = json_encode($details);
        $stmt->bind_param("issi", $product_id, $action, $details_json, $_SESSION['user_id']);
        return $stmt->execute();
    }

    public function generate_product_preview($data)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new Exception('Product name is required');
        }
        if (empty($data['vendor_id']) || $data['vendor_id'] <= 0) {
            throw new Exception('Please select a valid vendor');
        }
        if (empty($data['category_id']) || $data['category_id'] <= 0) {
            throw new Exception('Please select a valid category');
        }
        if (empty($data['price']) || $data['price'] <= 0) {
            throw new Exception('Price must be greater than zero');
        }

        // Get vendor and category information
        $vendor_stmt = $this->conn->prepare("SELECT business_name FROM vendors WHERE user_id = ?");
        $vendor_stmt->bind_param("i", $data['vendor_id']);
        $vendor_stmt->execute();
        $vendor_result = $vendor_stmt->get_result()->fetch_assoc();

        $category_stmt = $this->conn->prepare("SELECT name FROM categories WHERE id = ?");
        $category_stmt->bind_param("i", $data['category_id']);
        $category_stmt->execute();
        $category_result = $category_stmt->get_result()->fetch_assoc();

        if (!$vendor_result) {
            throw new Exception('Invalid vendor selected');
        }
        if (!$category_result) {
            throw new Exception('Invalid category selected');
        }

        // Create preview data
        $previewData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => (float) $data['price'],
            'stock' => (int) ($data['stock'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'vendor_name' => $vendor_result['business_name'],
            'category_name' => $category_result['name'],
            'image_url' => $data['image_url'] ?? null,
            'location_lat' => !empty($data['location_lat']) ? (float) $data['location_lat'] : null,
            'location_lng' => !empty($data['location_lng']) ? (float) $data['location_lng'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'preview' => true
        ];

        // Calculate estimated metrics
        $previewData['estimated_metrics'] = [
            'revenue_potential' => $previewData['price'] * $previewData['stock'],
            'stock_value' => $previewData['price'] * $previewData['stock'],
            'status_class' => $previewData['status'] === 'active' ? 'success' : ($previewData['status'] === 'inactive' ? 'secondary' : 'danger'),
            'stock_class' => $previewData['stock'] > 0 ? 'success' : 'danger'
        ];

        return $previewData;
    }

    private function get_param_type($value)
    {
        if (is_int($value))
            return 'i';
        if (is_float($value) || is_double($value))
            return 'd';
        return 's';
    }

    /**
     * Get products by category, optionally near a location
     */
    public function getProductsByCategory($categoryId, $latitude = null, $longitude = null, $radiusKm = 50)
    {
        $query = "SELECT p.*, v.business_name as vendor_name, c.name as category_name
                  FROM products p
                  LEFT JOIN vendors v ON p.vendor_id = v.user_id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.category_id = ? AND p.status = 'active'";
        $params = "i";
        $values = [$categoryId];

        if ($latitude !== null && $longitude !== null) {
            $query .= " AND (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(p.location_lat)) * COS(RADIANS(p.location_lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(p.location_lat)))) < ?";
            $params .= "dddi";
            $values[] = $latitude;
            $values[] = $longitude;
            $values[] = $latitude;
            $values[] = $radiusKm;
        }

        $query .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($params, ...$values);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get details of a specific category
     */
    public function getCategoryDetails($categoryId)
    {
        $query = "SELECT * FROM categories WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}


