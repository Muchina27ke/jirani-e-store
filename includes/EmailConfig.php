<?php

class EmailConfig
{
    private $conn;
    private $settings;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->loadSettings();
    }

    private function loadSettings()
    {
        $settingObj = new Setting($this->conn);
        $this->settings = $settingObj->get_all();
    }

    public function getMailerConfig()
    {
        return [
            'host' => $this->settings['smtp_host'] ?? 'smtp.gmail.com',
            'port' => (int)($this->settings['smtp_port'] ?? 587),
            'username' => $this->settings['smtp_username'] ?? '',
            'password' => base64_decode($this->settings['smtp_password'] ?? ''),
            'encryption' => $this->settings['smtp_encryption'] ?? 'tls',
            'from_address' => $this->settings['mail_from_address'] ?? 'noreply@jirani.com',
            'from_name' => $this->settings['mail_from_name'] ?? 'Jirani',
            'enabled' => ($this->settings['email_notifications'] ?? '1') === '1'
        ];
    }

    public function sendEmail($to, $subject, $body, $isHtml = true)
    {
        $config = $this->getMailerConfig();
        
        if (!$config['enabled']) {
            return ['success' => false, 'message' => 'Email notifications are disabled'];
        }

        // Use PHPMailer for sending emails
        require_once __DIR__ . '/../vendor/autoload.php'; // Assuming PHPMailer is installed via Composer
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];

            // Recipients
            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($to);

            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
        }
    }

    public function testConnection()
    {
        $config = $this->getMailerConfig();
        
        if (!$config['enabled']) {
            return ['success' => false, 'message' => 'Email notifications are disabled'];
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            
            // Test connection without sending
            $mail->SMTPDebug = 0;
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'SMTP connection failed: ' . $e->getMessage()];
        }
    }
}
