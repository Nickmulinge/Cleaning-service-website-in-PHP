<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Payment Model
 * Handles payment processing and tracking
 */
class Payment {
    private $conn;
    private $table_name = "payments";

    public $id;
    public $booking_id;
    public $amount;
    public $payment_method;
    public $payment_status;
    public $transaction_id;
    public $card_last_four;
    public $payment_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new payment record
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET booking_id=:booking_id, amount=:amount, 
                      payment_method=:payment_method, payment_status=:payment_status,
                      transaction_id=:transaction_id, card_last_four=:card_last_four,
                      payment_date=:payment_date";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));
        $this->transaction_id = htmlspecialchars(strip_tags($this->transaction_id));
        $this->card_last_four = htmlspecialchars(strip_tags($this->card_last_four));

        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":transaction_id", $this->transaction_id);
        $stmt->bindParam(":card_last_four", $this->card_last_four);
        $stmt->bindParam(":payment_date", $this->payment_date);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Get payment by booking ID
     */
    public function getByBookingId($booking_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE booking_id = :booking_id 
                  ORDER BY created_at DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update payment status
     */
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET payment_status=:payment_status, payment_date=:payment_date 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->payment_status = htmlspecialchars(strip_tags($this->payment_status));

        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Process credit card payment (simulation)
     */
    public function processCreditCard($card_number, $cvv, $expiry, $amount) {
        // This is a simulation - in production, integrate with Stripe, PayPal, etc.
        
        // Validate card number (basic Luhn algorithm check)
        if (!$this->validateCardNumber($card_number)) {
            return ['success' => false, 'message' => 'Invalid card number'];
        }

        // Simulate payment processing
        $this->transaction_id = 'TXN' . time() . rand(1000, 9999);
        $this->card_last_four = substr($card_number, -4);
        $this->payment_date = date('Y-m-d H:i:s');
        $this->payment_status = 'completed';
        $this->amount = $amount;

        return [
            'success' => true,
            'transaction_id' => $this->transaction_id,
            'message' => 'Payment processed successfully'
        ];
    }

    /**
     * Validate card number using Luhn algorithm
     */
    private function validateCardNumber($number) {
        $number = preg_replace('/\D/', '', $number);
        
        if (strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }

        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = intval($number[$i]);
            if ($i % 2 == $parity) {
                $digit *= 2;
            }
            if ($digit > 9) {
                $digit -= 9;
            }
            $sum += $digit;
        }

        return ($sum % 10) == 0;
    }
}
?>
