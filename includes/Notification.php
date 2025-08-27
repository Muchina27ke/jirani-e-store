<?php
require_once(dirname(__DIR__) . '/config/config.php');

class Notification
{
    private $conn;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
    }

    public function send($userId, $type, $message, $data = [])
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id,
                type,
                message,
                data,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");

        $dataJson = json_encode($data);
        $stmt->bind_param("isss", $userId, $type, $message, $dataJson);
        $stmt->execute();

        // Get user's notification preferences
        $stmt = $this->conn->prepare("
            SELECT email_notifications, sms_notifications 
            FROM notification_settings 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();

        // Send email if enabled
        if ($settings['email_notifications']) {
            $this->sendEmail($userId, $type, $message, $data);
        }

        // Send SMS if enabled
        if ($settings['sms_notifications']) {
            $this->sendSMS($userId, $message);
        }

        return true;
    }

    private function sendEmail($userId, $type, $message, $data)
    {
        // Get user email
        $stmt = $this->conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user['email']) {
            return false;
        }

        // Get email template
        $stmt = $this->conn->prepare("
            SELECT subject, body 
            FROM notification_templates 
            WHERE type = ? AND channel = 'email'
        ");
        $stmt->bind_param("s", $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();

        if (!$template) {
            return false;
        }

        // Replace placeholders
        $subject = $this->replacePlaceholders($template['subject'], $data);
        $body = $this->replacePlaceholders($template['body'], $data);

        // Send email
        $headers = [
            'From: Jirani <noreply@jirani.com>',
            'Content-Type: text/html; charset=UTF-8'
        ];

        // Use EmailConfig for sending emails
        global $emailConfig;
        if ($emailConfig) {
            return $emailConfig->sendEmail($user['email'], $subject, $body, false);
        }
        return false;
    }

    private function sendSMS($userId, $message)
    {
        // Get user phone
        $stmt = $this->conn->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user['phone']) {
            return false;
        }

        // TODO: Implement SMS sending using your preferred provider
        // For now, just log the SMS
        error_log("SMS to {$user['phone']}: $message");
        return true;
    }

    private function replacePlaceholders($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }

    public function getUnreadCount($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND read_at IS NULL
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'];
    }

    public function markAsRead($notificationId, $userId)
    {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $notificationId, $userId);
        return $stmt->execute();
    }

    public function getNotifications($userId, $limit = 10, $offset = 0)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $row['data'] = json_decode($row['data'], true);
            $notifications[] = $row;
        }

        return $notifications;
    }
}