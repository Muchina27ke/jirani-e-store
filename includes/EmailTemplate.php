<?php
/**
 * Email Template Engine for Jirani Platform
 * Handles loading and rendering of HTML email templates
 */

class EmailTemplate
{
    private $templateDir;
    private $defaultVars;

    public function __construct($templateDir = null)
    {
        // Always use the project root email_templates directory
        $this->templateDir = $templateDir ?: dirname(__DIR__) . '/email_templates/';
        $this->defaultVars = [
            'logo_url' => SITE_URL . 'title_logo.jpg',
            'current_year' => date('Y'),
            'site_url' => SITE_URL,
            'support_email' => 'support@jirani.com',
            'support_phone' => '+254 700 000 000',
            'help_url' => SITE_URL . 'help',
            'privacy_url' => SITE_URL . 'privacy',
            'terms_url' => SITE_URL . 'terms',
            'unsubscribe_url' => SITE_URL . 'unsubscribe'
        ];
    }

    /**
     * Render an email template with variables
     */
    public function render($templateName, $variables = [])
    {
        $templatePath = $this->templateDir . $templateName . '.html';

        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: {$templateName}");
        }

        $template = file_get_contents($templatePath);
        $allVars = array_merge($this->defaultVars, $variables);

        // Simple template variable replacement
        foreach ($allVars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Handle conditional blocks (basic implementation)
        $template = $this->processConditionals($template, $allVars);

        // Handle loops (basic implementation)
        $template = $this->processLoops($template, $allVars);

        return $template;
    }

    /**
     * Process conditional blocks like {{#if_approved}}...{{/if_approved}}
     */
    private function processConditionals($template, $variables)
    {
        // Match conditional blocks
        preg_match_all('/\{\{#if_(\w+)\}\}(.*?)\{\{\/if_\1\}\}/s', $template, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $condition = $match[1];
            $content = $match[2];
            $fullMatch = $match[0];

            // Check if condition is true
            $shouldShow = false;
            if (isset($variables[$condition]) && $variables[$condition]) {
                $shouldShow = true;
            } elseif (isset($variables['status']) && $variables['status'] === $condition) {
                $shouldShow = true;
            }

            // Replace with content or empty string
            $replacement = $shouldShow ? $content : '';
            $template = str_replace($fullMatch, $replacement, $template);
        }

        return $template;
    }

    /**
     * Process loop blocks like {{#each items}}...{{/each}}
     */
    private function processLoops($template, $variables)
    {
        // Match loop blocks
        preg_match_all('/\{\{#each (\w+)\}\}(.*?)\{\{\/each\}\}/s', $template, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $arrayName = $match[1];
            $loopTemplate = $match[2];
            $fullMatch = $match[0];

            $replacement = '';

            if (isset($variables[$arrayName]) && is_array($variables[$arrayName])) {
                foreach ($variables[$arrayName] as $item) {
                    $itemHtml = $loopTemplate;

                    // Replace variables in loop template
                    foreach ($item as $key => $value) {
                        $itemHtml = str_replace('{{' . $key . '}}', $value, $itemHtml);
                    }

                    $replacement .= $itemHtml;
                }
            }

            $template = str_replace($fullMatch, $replacement, $template);
        }

        return $template;
    }

    /**
     * Send email using PHPMailer and SMTP
     */
    public function send($to, $subject, $templateName, $variables = [], $fromEmail = null, $fromName = null)
    {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $fromEmail = $fromEmail ?: SMTP_FROM;
        $fromName = $fromName ?: SMTP_FROM_NAME;

        $variables['recipient_email'] = $to;
        $variables['subject'] = $subject;

        $htmlContent = $this->render($templateName, $variables);

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlContent;

            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Preview template in browser (for testing)
     */
    public function preview($templateName, $variables = [])
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo $this->render($templateName, $variables);
        exit;
    }
}

/**
 * Email notification helper functions
 */
class EmailNotifications
{
    private $emailTemplate;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->emailTemplate = new EmailTemplate();
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $resetToken, $resetCode)
    {
        $variables = [
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'reset_url' => SITE_URL . 'reset-password?token=' . $resetToken,
            'reset_code' => $resetCode,
            'expiry_time' => '30',
            'request_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'request_time' => date('Y-m-d H:i:s')
        ];

        return $this->emailTemplate->send(
            $user['email'],
            'Reset Your Jirani Password',
            'password_reset',
            $variables
        );
    }

    /**
     * Send verification status update
     */
    public function sendVerificationUpdate($vendor, $status, $rejectionReason = '')
    {
        $variables = [
            'vendor_name' => $vendor['business_name'],
            'vendor_email' => $vendor['email'],
            'business_name' => $vendor['business_name'],
            'verification_status' => ucfirst($status),
            'submission_date' => date('M d, Y', strtotime($vendor['created_at'])),
            'review_date' => date('M d, Y'),
            'rejection_reason' => $rejectionReason,
            'vendor_dashboard_url' => SITE_URL . 'seller/',
            'resubmit_url' => SITE_URL . 'seller/verification.php',
            'check_status_url' => SITE_URL . 'seller/verification.php',
            'vendor_guidelines_url' => SITE_URL . 'vendor-guidelines'
        ];

        // Set conditional flags
        $variables['if_approved'] = ($status === 'approved');
        $variables['if_rejected'] = ($status === 'rejected');
        $variables['if_pending'] = ($status === 'pending');

        $subject = match ($status) {
            'approved' => 'Congratulations! Your Jirani Vendor Account is Approved',
            'rejected' => 'Jirani Vendor Verification Update Required',
            'pending' => 'Jirani Vendor Verification Under Review',
            default => 'Jirani Vendor Verification Update'
        };

        return $this->emailTemplate->send(
            $vendor['email'],
            $subject,
            'verification_status',
            $variables
        );
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($order, $orderItems, $customer, $vendor)
    {
        $variables = [
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'] ?? 'N/A',
            'order_number' => str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'order_date' => date('M d, Y H:i', strtotime($order['created_at'])),
            'total_amount' => number_format($order['total_amount'] ?? 0, 2),
            'delivery_address' => $order['delivery_address'],
            'estimated_delivery' => date('M d, Y', strtotime('+2 days')),
            'delivery_instructions' => $order['delivery_instructions'] ?? 'None',
            'payment_method' => 'M-Pesa',
            'payment_status' => 'Completed',
            'transaction_id' => $order['mpesa_transaction_id'] ?? 'N/A',
            'vendor_name' => $vendor['business_name'],
            'vendor_phone' => $vendor['phone'] ?? 'N/A',
            'track_order_url' => SITE_URL . 'track-order?id=' . $order['id'],
            'order_history_url' => SITE_URL . 'orders',
            'order_items' => $this->renderOrderItemsTable($orderItems)
        ];

        return $this->emailTemplate->send(
            $customer['email'],
            'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'order_confirmation',
            $variables
        );
    }

    /**
     * Render an HTML table of order items for email templates
     */
    private function renderOrderItemsTable($orderItems) {
        if (empty($orderItems)) return '<p>No items found.</p>';
        $html = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:14px;">
            <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
        foreach ($orderItems as $item) {
            $name = htmlspecialchars($item['product_name'] ?? $item['name'] ?? '');
            $qty = (int)($item['quantity'] ?? 1);
            $price = number_format((float)($item['price'] ?? 0), 2);
            $subtotal = number_format($qty * (float)($item['price'] ?? 0), 2);
            $html .= "<tr><td>{$name}</td><td>{$qty}</td><td>KSh {$price}</td><td>KSh {$subtotal}</td></tr>";
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($user, $userType = 'customer')
    {
        $variables = [
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'user_type' => $userType,
            'dashboard_url' => $userType === 'vendor' ? SITE_URL . 'seller/' : SITE_URL . 'dashboard',
            'getting_started_url' => SITE_URL . 'getting-started'
        ];

        return $this->emailTemplate->send(
            $user['email'],
            'Welcome to Jirani!',
            'welcome',
            $variables
        );
    }

    /**
     * Log email activity
     */
    private function logEmail($recipient, $subject, $template, $status)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO email_logs (recipient, subject, template, status, sent_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssss", $recipient, $subject, $template, $status);
        $stmt->execute();
    }
}
?>