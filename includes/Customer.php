<?php

class Customer
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function get_all_customers()
    {
        $query = "SELECT id, name, phone, email, created_at FROM users WHERE role = \'customer\'";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get_customer_by_id($customer_id)
    {
        $query = "SELECT id, name, phone, email, created_at FROM users WHERE id = ? AND role = \'customer\' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function update_customer_profile($customer_id, $name, $phone, $email)
    {
        $query = "UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ? AND role = \'customer\'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $name, $phone, $email, $customer_id);
        return $stmt->execute();
    }

    public function delete_customer($customer_id)
    {
        $query = "DELETE FROM users WHERE id = ? AND role = \'customer\'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        return $stmt->execute();
    }
}


