<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Invoice Model
 * Handles invoice generation and management
 */
class Invoice {
    private $conn;
    private $table_name = "invoices";

    public $id;
    public $booking_id;
    public $invoice_number;
    public $amount;
    public $tax_amount;
    public $total_amount;
    public $status;
    public $due_date;
    public $issued_date;
    public $paid_date;
    public $notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new invoice
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET booking_id=:booking_id, invoice_number=:invoice_number,
                      amount=:amount, tax_amount=:tax_amount, total_amount=:total_amount,
                      status=:status, due_date=:due_date, issued_date=:issued_date,
                      notes=:notes";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->invoice_number = htmlspecialchars(strip_tags($this->invoice_number));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->tax_amount = htmlspecialchars(strip_tags($this->tax_amount));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":invoice_number", $this->invoice_number);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":tax_amount", $this->tax_amount);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":issued_date", $this->issued_date);
        $stmt->bindParam(":notes", $this->notes);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Get invoices by customer ID
     */
    public function getByCustomerId($customer_id) {
        $query = "SELECT i.*, b.booking_date, b.booking_time, s.name as service_name
                  FROM " . $this->table_name . " i
                  INNER JOIN bookings b ON i.booking_id = b.id
                  INNER JOIN services s ON b.service_id = s.id
                  WHERE b.customer_id = :customer_id
                  ORDER BY i.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Get invoice by booking ID
     */
    public function getByBookingId($booking_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE booking_id = :booking_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber() {
        $prefix = 'INV';
        $date = date('Ymd');
        $random = rand(1000, 9999);
        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Update invoice status
     */
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status=:status, paid_date=:paid_date 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":paid_date", $this->paid_date);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Get all invoices (for admin)
     */
    public function readAll() {
        $query = "SELECT i.*, b.booking_date, s.name as service_name,
                         u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " i
                  INNER JOIN bookings b ON i.booking_id = b.id
                  INNER JOIN services s ON b.service_id = s.id
                  INNER JOIN users u ON b.customer_id = u.id
                  ORDER BY i.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>
