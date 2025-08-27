<?php

class Setting
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function get($name)
    {
        $query = "SELECT value FROM settings WHERE name = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['value'] ?? null;
    }

    public function set($name, $value)
    {
        $query = "INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $name, $value);
        return $stmt->execute();
    }

    public function get_all()
    {
        $query = "SELECT name, value FROM settings";
        $result = $this->conn->query($query);
        $all_settings = [];
        while ($row = $result->fetch_assoc()) {
            $all_settings[$row['name']] = $row['value'];
        }
        return $all_settings;
    }
}