<?php
require_once(dirname(__DIR__) . '/config/config.php');

class Escrow
{
    private $conn;
    private $notification;
    private $autoReleaseDays = 7; // Auto-release after 7 days if no dispute

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->notification = new Notification($conn);
    }

    public function holdPayment($paymentId, $orderId)
    {
        try {
            $this->conn->begin_transaction();

            // Update payment status to held
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET is_held = 1,
                    updated_at = NOW()
                WHERE id = ? AND order_id = ?
            ");
            $stmt->execute([$paymentId, $orderId]);

            // Create escrow record
            $stmt = $this->conn->prepare("
                INSERT INTO escrow_payments 
                (payment_id, escrow_status, created_at, auto_release_date)
                VALUES (?, 'held', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))
            ");
            $stmt->execute([$paymentId, $this->autoReleaseDays]);

            // Notify vendor
            $this->notification->send(
                'escrow_held',
                $this->getVendorId($orderId),
                'Payment held in escrow',
                'Payment for order #' . $orderId . ' has been held in escrow.'
            );

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function releasePayment($paymentId, $releasedBy, $reason = null)
    {
        try {
            $this->conn->begin_transaction();

            // Get escrow details
            $stmt = $this->conn->prepare("
                SELECT ep.*, p.order_id 
                FROM escrow_payments ep
                JOIN payments p ON p.id = ep.payment_id
                WHERE ep.payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            $escrow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$escrow) {
                throw new Exception("Escrow payment not found");
            }

            // Update escrow status
            $stmt = $this->conn->prepare("
                UPDATE escrow_payments 
                SET escrow_status = 'released',
                    released_by = ?,
                    release_reason = ?,
                    released_at = NOW()
                WHERE payment_id = ?
            ");
            $stmt->execute([$releasedBy, $reason, $paymentId]);

            // Update payment status
            $stmt = $this->conn->prepare("
                UPDATE payments 
                SET is_held = 0,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$paymentId]);

            // Notify vendor
            $this->notification->send(
                'escrow_released',
                $this->getVendorId($escrow['order_id']),
                'Escrow payment released',
                'Payment for order #' . $escrow['order_id'] . ' has been released from escrow.'
            );

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function initiateDispute($paymentId, $initiatedBy, $reason)
    {
        try {
            $this->conn->begin_transaction();

            // Get escrow details
            $stmt = $this->conn->prepare("
                SELECT ep.*, p.order_id 
                FROM escrow_payments ep
                JOIN payments p ON p.id = ep.payment_id
                WHERE ep.payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            $escrow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$escrow) {
                throw new Exception("Escrow payment not found");
            }

            // Create dispute record
            $stmt = $this->conn->prepare("
                INSERT INTO escrow_disputes 
                (escrow_payment_id, initiated_by, reason, status, created_at)
                VALUES (?, ?, ?, 'open', NOW())
            ");
            $stmt->execute([$escrow['id'], $initiatedBy, $reason]);

            // Update escrow status
            $stmt = $this->conn->prepare("
                UPDATE escrow_payments 
                SET escrow_status = 'disputed',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$escrow['id']]);

            // Notify admin
            $this->notification->send(
                'escrow_dispute',
                'admin',
                'New escrow dispute',
                'A new dispute has been initiated for order #' . $escrow['order_id']
            );

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function resolveDispute($disputeId, $resolvedBy, $resolution, $notes = null)
    {
        try {
            $this->conn->begin_transaction();

            // Get dispute details
            $stmt = $this->conn->prepare("
                SELECT d.*, ep.payment_id, p.order_id
                FROM escrow_disputes d
                JOIN escrow_payments ep ON ep.id = d.escrow_payment_id
                JOIN payments p ON p.id = ep.payment_id
                WHERE d.id = ?
            ");
            $stmt->execute([$disputeId]);
            $dispute = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dispute) {
                throw new Exception("Dispute not found");
            }

            // Update dispute status
            $stmt = $this->conn->prepare("
                UPDATE escrow_disputes 
                SET status = 'resolved',
                    resolution = ?,
                    resolved_by = ?,
                    resolution_notes = ?,
                    resolved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$resolution, $resolvedBy, $notes, $disputeId]);

            // If resolution is to release payment
            if ($resolution === 'release') {
                $this->releasePayment($dispute['payment_id'], $resolvedBy, 'Released after dispute resolution');
            }

            // Notify all parties
            $this->notification->send(
                'dispute_resolved',
                $this->getVendorId($dispute['order_id']),
                'Dispute resolved',
                'The dispute for order #' . $dispute['order_id'] . ' has been resolved.'
            );

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function checkAutoRelease()
    {
        $stmt = $this->conn->prepare("
            SELECT ep.*, p.order_id
            FROM escrow_payments ep
            JOIN payments p ON p.id = ep.payment_id
            WHERE ep.escrow_status = 'held'
            AND ep.auto_release_date <= NOW()
            AND NOT EXISTS (
                SELECT 1 FROM escrow_disputes d 
                WHERE d.escrow_payment_id = ep.id 
                AND d.status = 'open'
            )
        ");
        $stmt->execute();
        $payments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payments[] = $row;
        }

        foreach ($payments as $payment) {
            try {
                $this->releasePayment(
                    $payment['payment_id'],
                    'system',
                    'Automatically released after ' . $this->autoReleaseDays . ' days'
                );
            } catch (Exception $e) {
                // Log error but continue processing other payments
                error_log("Failed to auto-release payment {$payment['payment_id']}: " . $e->getMessage());
            }
        }
    }

    private function getVendorId($orderId)
    {
        $stmt = $this->conn->prepare("
            SELECT vendor_id 
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['vendor_id'] : null;
    }

    public function getEscrowStatus($paymentId)
    {
        $stmt = $this->conn->prepare("
            SELECT ep.*, p.order_id, p.amount, p.customer_id, p.vendor_id
            FROM escrow_payments ep
            JOIN payments p ON ep.payment_id = p.id
            WHERE ep.payment_id = ?
        ");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        return $result->fetch_assoc();
    }
}