<?php
class ErrorHandler
{
    private $db;
    private $security;

    public function __construct($db)
    {
        $this->db = $db;
        $this->security = new Security($db);
    }

    public function handleError($error, $context = [])
    {
        // Log error
        $this->logError($error, $context);

        // Get user-friendly message
        $message = $this->getUserFriendlyMessage($error);

        // Return formatted response
        return [
            'success' => false,
            'message' => $message,
            'error_code' => $this->getErrorCode($error)
        ];
    }

    private function logError($error, $context)
    {
        $stmt = $this->db->prepare("
            INSERT INTO error_logs 
            (error_message, error_code, context, stack_trace, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $error->getMessage(),
            $error->getCode(),
            json_encode($context),
            $error->getTraceAsString()
        ]);
    }

    private function getUserFriendlyMessage($error)
    {
        $errorCode = $error->getCode();
        $errorMessage = $error->getMessage();

        // Map error codes to user-friendly messages
        $errorMessages = [
            'VALIDATION_ERROR' => 'Please check your input and try again.',
            'AUTHENTICATION_ERROR' => 'Please log in to continue.',
            'AUTHORIZATION_ERROR' => 'You do not have permission to perform this action.',
            'PAYMENT_ERROR' => 'There was an error processing your payment. Please try again.',
            'NETWORK_ERROR' => 'Please check your internet connection and try again.',
            'DATABASE_ERROR' => 'We are experiencing technical difficulties. Please try again later.',
            'MPESA_ERROR' => 'There was an error with the M-Pesa payment. Please try again.',
            'DELIVERY_ERROR' => 'There was an error processing your delivery. Please try again.',
            'STOCK_ERROR' => 'The requested quantity is not available.',
            'RATE_LIMIT_ERROR' => 'Too many attempts. Please try again later.'
        ];

        // Check if we have a mapped message
        if (isset($errorMessages[$errorCode])) {
            return $errorMessages[$errorCode];
        }

        // For unknown errors, return a generic message
        return 'An unexpected error occurred. Please try again later.';
    }

    private function getErrorCode($error)
    {
        $errorMessage = strtolower($error->getMessage());

        // Map error messages to error codes
        if (strpos($errorMessage, 'validation') !== false) {
            return 'VALIDATION_ERROR';
        } elseif (strpos($errorMessage, 'authentication') !== false) {
            return 'AUTHENTICATION_ERROR';
        } elseif (strpos($errorMessage, 'authorization') !== false) {
            return 'AUTHORIZATION_ERROR';
        } elseif (strpos($errorMessage, 'payment') !== false) {
            return 'PAYMENT_ERROR';
        } elseif (strpos($errorMessage, 'network') !== false) {
            return 'NETWORK_ERROR';
        } elseif (strpos($errorMessage, 'database') !== false) {
            return 'DATABASE_ERROR';
        } elseif (strpos($errorMessage, 'mpesa') !== false) {
            return 'MPESA_ERROR';
        } elseif (strpos($errorMessage, 'delivery') !== false) {
            return 'DELIVERY_ERROR';
        } elseif (strpos($errorMessage, 'stock') !== false) {
            return 'STOCK_ERROR';
        } elseif (strpos($errorMessage, 'rate limit') !== false) {
            return 'RATE_LIMIT_ERROR';
        }

        return 'UNKNOWN_ERROR';
    }

    public function validateInput($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                if (strpos($rule, 'required') !== false) {
                    $errors[$field] = "This field is required";
                }
                continue;
            }

            $value = $data[$field];

            if (strpos($rule, 'email') !== false && !$this->security->validateEmail($value)) {
                $errors[$field] = "Please enter a valid email address";
            }

            if (strpos($rule, 'phone') !== false && !$this->security->validatePhone($value)) {
                $errors[$field] = "Please enter a valid phone number";
            }

            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $errors[$field] = "This field must be a number";
            }

            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = $matches[1];
                if (strlen($value) < $min) {
                    $errors[$field] = "Minimum length is {$min} characters";
                }
            }

            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = $matches[1];
                if (strlen($value) > $max) {
                    $errors[$field] = "Maximum length is {$max} characters";
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return true;
    }
}

class ValidationException extends Exception
{
    private $errors;

    public function __construct($errors)
    {
        $this->errors = $errors;
        parent::__construct("Validation failed");
    }

    public function getErrors()
    {
        return $this->errors;
    }
}