<?php
// Helper for logging admin actions and system events
class AdminLog {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    // Log an admin action (generic)
    public function log($table, $data) {
        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $types = '';
        foreach ($data as $v) {
            $types .= is_int($v) ? 'i' : 's';
        }
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...array_values($data));
        $stmt->execute();
        $stmt->close();
    }
    // Convenience for order log
    public function order($order_id, $action, $details, $admin_id) {
        $this->log('order_logs', [
            'order_id' => $order_id,
            'action' => $action,
            'details' => $details,
            'admin_id' => $admin_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    // Convenience for security log
    public function security($event, $details, $ip, $user_agent) {
        $this->log('security_logs', [
            'event' => $event,
            'details' => $details,
            'ip_address' => $ip,
            'user_agent' => $user_agent,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    // Add more log types as needed
}
