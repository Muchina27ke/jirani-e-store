<?php
/**
 * Vendor Verification System for Jirani Platform
 * Handles vendor registration, document verification, and approval process
 */

class VendorVerification
{
    private $conn;
    private $uploadDir;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->uploadDir = __DIR__ . '/../uploads/vendor_documents/';

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Submit vendor verification documents
     */
    public function submitVerification($vendorId, $documents, $businessInfo)
    {
        try {
            $this->conn->begin_transaction();

            // Update vendor business information
            $stmt = $this->conn->prepare("
                UPDATE vendors SET 
                    business_name = ?, 
                    business_type = ?, 
                    business_address = ?, 
                    business_phone = ?, 
                    business_email = ?, 
                    tax_pin = ?, 
                    status = 'pending',
                    submitted_at = NOW()
                WHERE user_id = ?
            ");

            $stmt->bind_param(
                "ssssssi",
                $businessInfo['business_name'],
                $businessInfo['business_type'],
                $businessInfo['business_address'],
                $businessInfo['business_phone'],
                $businessInfo['business_email'],
                $businessInfo['tax_pin'],
                $vendorId
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update vendor information");
            }

            // Process and save documents
            $documentTypes = ['business_permit', 'tax_certificate', 'id_document', 'bank_statement'];

            foreach ($documentTypes as $docType) {
                if (isset($documents[$docType]) && $documents[$docType]['error'] === UPLOAD_ERR_OK) {
                    $result = $this->saveDocument($vendorId, $docType, $documents[$docType]);
                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                }
            }

            // Log verification submission
            $this->logVerificationActivity($vendorId, 'documents_submitted', 'Vendor submitted verification documents');

            $this->conn->commit();

            // Send notification to admin
            $this->notifyAdminNewSubmission($vendorId);

            return ['success' => true, 'message' => 'Verification documents submitted successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Vendor verification submission error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Save uploaded document
     */
    private function saveDocument($vendorId, $documentType, $file)
    {
        try {
            // Validate file
            $validation = $this->validateDocument($file);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $vendorId . '_' . $documentType . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'message' => 'Failed to save document'];
            }

            // Save document record in database
            $stmt = $this->conn->prepare("
                INSERT INTO vendor_documents (vendor_id, document_type, filename, original_name, file_size, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')
                ON DUPLICATE KEY UPDATE 
                    filename = VALUES(filename), 
                    original_name = VALUES(original_name), 
                    file_size = VALUES(file_size), 
                    status = 'pending',
                    uploaded_at = NOW()
            ");

            $stmt->bind_param(
                "isssi",
                $vendorId,
                $documentType,
                $filename,
                $file['name'],
                $file['size']
            );

            if (!$stmt->execute()) {
                unlink($filepath); // Remove file if database insert fails
                return ['success' => false, 'message' => 'Failed to save document record'];
            }

            return ['success' => true, 'filename' => $filename];

        } catch (Exception $e) {
            error_log("Document save error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save document'];
        }
    }

    /**
     * Validate uploaded document
     */
    private function validateDocument($file)
    {
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['valid' => false, 'message' => 'File size must be less than 5MB'];
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'message' => 'Only JPEG, PNG, and PDF files are allowed'];
        }

        // Check for malicious content (basic check)
        if ($mimeType === 'application/pdf') {
            $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
            if (strpos($content, '%PDF') !== 0) {
                return ['valid' => false, 'message' => 'Invalid PDF file'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get vendor verification status
     */
    public function getVerificationStatus($vendorId)
    {
        $stmt = $this->conn->prepare("
            SELECT v.*, u.name, u.email, u.phone 
            FROM vendors v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.user_id = ?
        ");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $vendor = $stmt->get_result()->fetch_assoc();

        if (!$vendor) {
            return null;
        }

        // Get documents
        $stmt = $this->conn->prepare("
            SELECT * FROM vendor_documents 
            WHERE vendor_id = ? 
            ORDER BY document_type
        ");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get verification history
        $stmt = $this->conn->prepare("
            SELECT * FROM vendor_verification_logs 
            WHERE vendor_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'vendor' => $vendor,
            'documents' => $documents,
            'history' => $history
        ];
    }

    /**
     * Admin: Get pending verifications
     */
    public function getPendingVerifications($limit = 50)
    {
        $stmt = $this->conn->prepare("
            SELECT v.*, u.name, u.email, u.phone, u.created_at as registration_date,
                   COUNT(vd.id) as document_count
            FROM vendors v 
            JOIN users u ON v.user_id = u.id 
            LEFT JOIN vendor_documents vd ON v.user_id = vd.vendor_id
            WHERE v.status = 'pending'
            GROUP BY v.user_id
            ORDER BY v.submitted_at ASC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Admin: Approve vendor verification
     */
    public function approveVerification($vendorId, $adminId, $notes = '')
    {
        try {
            $this->conn->begin_transaction();

            // Update vendor status
            $stmt = $this->conn->prepare("
                UPDATE vendors 
                SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? 
                WHERE user_id = ?
            ");
            $stmt->bind_param("isi", $adminId, $notes, $vendorId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to approve vendor");
            }

            // Update all documents status
            $stmt = $this->conn->prepare("
                UPDATE vendor_documents 
                SET status = 'approved' 
                WHERE vendor_id = ?
            ");
            $stmt->bind_param("i", $vendorId);
            $stmt->execute();

            // Log approval
            $this->logVerificationActivity($vendorId, 'approved', $notes, $adminId);

            $this->conn->commit();

            // Send approval notification
            $this->sendVerificationNotification($vendorId, 'approved');

            return ['success' => true, 'message' => 'Vendor approved successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Vendor approval error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Admin: Reject vendor verification
     */
    public function rejectVerification($vendorId, $adminId, $reason)
    {
        try {
            $this->conn->begin_transaction();

            // Update vendor status
            $stmt = $this->conn->prepare("
                UPDATE vendors 
                SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? 
                WHERE user_id = ?
            ");
            $stmt->bind_param("isi", $adminId, $reason, $vendorId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to reject vendor");
            }

            // Update documents status
            $stmt = $this->conn->prepare("
                UPDATE vendor_documents 
                SET status = 'rejected' 
                WHERE vendor_id = ?
            ");
            $stmt->bind_param("i", $vendorId);
            $stmt->execute();

            // Log rejection
            $this->logVerificationActivity($vendorId, 'rejected', $reason, $adminId);

            $this->conn->commit();

            // Send rejection notification
            $this->sendVerificationNotification($vendorId, 'rejected', $reason);

            return ['success' => true, 'message' => 'Vendor rejected successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Vendor rejection error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Admin: Revoke vendor verification
     */
    public function revokeVerification($vendorId, $adminId, $reason = 'Verification revoked')
    {
        try {
            $this->conn->begin_transaction();

            // Update vendor status to 'pending'
            $stmt = $this->conn->prepare("
                UPDATE vendors 
                SET status = 'pending', approved_by = NULL, approved_at = NULL, admin_notes = ? 
                WHERE user_id = ?
            ");
            $stmt->bind_param("si", $reason, $vendorId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to revoke vendor verification");
            }

            // Log revocation
            $this->logVerificationActivity($vendorId, 'revoked', $reason, $adminId);

            $this->conn->commit();

            // Optionally, send a notification to the vendor
            // $this->sendVerificationNotification($vendorId, 'revoked', $reason);

            return ['success' => true, 'message' => 'Vendor verification revoked successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Vendor revocation error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update vendor verification status (approve, reject, revoke)
     */
    public function update_verification_status($vendorId, $status, $adminId = null, $note = null)
    {
        $allowed = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowed)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        $stmt = $this->conn->prepare("UPDATE vendors SET status = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("si", $status, $vendorId);
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to update status'];
        }
        // Optionally log the action
        $action = ($status === 'pending') ? 'revoked' : $status;
        $details = $note ? $note : (ucfirst($action) . ' by admin');
        $this->logVerificationActivity($vendorId, $action, $details, $adminId);
        return ['success' => true, 'message' => 'Status updated'];
    }

    /**
     * Get vendor document
     */
    public function getDocument($documentId, $vendorId = null)
    {
        $sql = "SELECT * FROM vendor_documents WHERE id = ?";
        $params = [$documentId];
        $types = "i";

        if ($vendorId) {
            $sql .= " AND vendor_id = ?";
            $params[] = $vendorId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Serve document file (with access control)
     */
    public function serveDocument($documentId, $vendorId = null)
    {
        $document = $this->getDocument($documentId, $vendorId);

        if (!$document) {
            return ['success' => false, 'message' => 'Document not found'];
        }

        $filepath = $this->uploadDir . $document['filename'];

        if (!file_exists($filepath)) {
            return ['success' => false, 'message' => 'File not found'];
        }

        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $document['original_name'],
            'mime_type' => mime_content_type($filepath)
        ];
    }

    /**
     * Delete vendor documents
     */
    public function deleteVendorDocuments($vendorId)
    {
        // Get all documents for vendor
        $stmt = $this->conn->prepare("SELECT filename FROM vendor_documents WHERE vendor_id = ?");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Delete files
        foreach ($documents as $doc) {
            $filepath = $this->uploadDir . $doc['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Delete database records
        $stmt = $this->conn->prepare("DELETE FROM vendor_documents WHERE vendor_id = ?");
        $stmt->bind_param("i", $vendorId);
        $stmt->execute();

        return true;
    }

    /**
     * Log verification activity
     */
    private function logVerificationActivity($vendorId, $action, $details = '', $adminId = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO vendor_verification_logs (vendor_id, action, details, admin_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("issi", $vendorId, $action, $details, $adminId);
        $stmt->execute();
    }

    /**
     * Send verification notification
     */
    private function sendVerificationNotification($vendorId, $status, $reason = '')
    {
        try {
            // Get vendor details
            $stmt = $this->conn->prepare("
                SELECT v.*, u.name, u.email 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.user_id = ?
            ");
            $stmt->bind_param("i", $vendorId);
            $stmt->execute();
            $vendor = $stmt->get_result()->fetch_assoc();

            if ($vendor && $vendor['email']) {
                $emailNotifications = new EmailNotifications($this->conn);
                $emailNotifications->sendVerificationUpdate($vendor, $status, $reason);
            }

        } catch (Exception $e) {
            error_log("Failed to send verification notification: " . $e->getMessage());
        }
    }

    /**
     * Notify admin of new submission
     */
    private function notifyAdminNewSubmission($vendorId)
    {
        try {
            // Get admin emails
            $stmt = $this->conn->prepare("
                SELECT email FROM users 
                WHERE role IN ('admin', 'super_admin') 
                AND email IS NOT NULL
            ");
            $stmt->execute();
            $admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Get vendor details
            $stmt = $this->conn->prepare("
                SELECT v.*, u.name 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.user_id = ?
            ");
            $stmt->bind_param("i", $vendorId);
            $stmt->execute();
            $vendor = $stmt->get_result()->fetch_assoc();

            if ($vendor) {
                foreach ($admins as $admin) {
                    // Send email notification to admin
                    $subject = "New Vendor Verification Submission - {$vendor['business_name']}";
                    $message = "A new vendor verification has been submitted and requires review.\n\n";
                    $message .= "Business Name: {$vendor['business_name']}\n";
                    $message .= "Owner: {$vendor['name']}\n";
                    $message .= "Submitted: " . date('Y-m-d H:i:s') . "\n\n";
                    $message .= "Please review at: " . SITE_URL . "admin/vendors.php";

                    // Use EmailConfig for sending emails
                    global $emailConfig;
                    if ($emailConfig) {
                        $emailConfig->sendEmail($admin['email'], $subject, $message, false);
                    }
                }
            }

        } catch (Exception $e) {
            error_log("Failed to notify admin: " . $e->getMessage());
        }
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats()
    {
        $stats = [];

        // Total vendors by status
        $stmt = $this->conn->prepare("
            SELECT status, COUNT(*) as count 
            FROM vendors 
            GROUP BY status
        ");
        $stmt->execute();
        $statusCounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($statusCounts as $status) {
            $stats[$status['status']] = $status['count'];
        }

        // Recent submissions (last 30 days)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM vendors 
            WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['recent_submissions'] = $stmt->get_result()->fetch_assoc()['count'];

        // Average processing time
        $stmt = $this->conn->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, submitted_at, approved_at)) as avg_hours 
            FROM vendors 
            WHERE status IN ('approved', 'rejected') 
            AND submitted_at IS NOT NULL 
            AND approved_at IS NOT NULL
        ");
        $stmt->execute();
        $stats['avg_processing_hours'] = $stmt->get_result()->fetch_assoc()['avg_hours'];

        return $stats;
    }
}