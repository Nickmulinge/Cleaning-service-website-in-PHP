<?php
require_once __DIR__ . '/../config/database.php';

class Booking {
    private $conn;
    private $table_name = "bookings";

    public $id;
    public $customer_id;
    public $service_id;
    public $staff_id;
    public $booking_date;
    public $booking_time;
    public $duration_minutes;
    public $total_price;
    public $status;
    public $special_instructions;
    public $address;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET customer_id=:customer_id, service_id=:service_id, 
                      booking_date=:booking_date, booking_time=:booking_time,
                      duration_minutes=:duration_minutes, total_price=:total_price,
                      special_instructions=:special_instructions, address=:address";

        $stmt = $this->conn->prepare($query);

        $this->customer_id = htmlspecialchars(strip_tags($this->customer_id));
        $this->service_id = htmlspecialchars(strip_tags($this->service_id));
        $this->booking_date = htmlspecialchars(strip_tags($this->booking_date));
        $this->booking_time = htmlspecialchars(strip_tags($this->booking_time));
        $this->duration_minutes = htmlspecialchars(strip_tags($this->duration_minutes));
        $this->total_price = htmlspecialchars(strip_tags($this->total_price));
        $this->special_instructions = htmlspecialchars(strip_tags($this->special_instructions));
        $this->address = htmlspecialchars(strip_tags($this->address));

        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":service_id", $this->service_id);
        $stmt->bindParam(":booking_date", $this->booking_date);
        $stmt->bindParam(":booking_time", $this->booking_time);
        $stmt->bindParam(":duration_minutes", $this->duration_minutes);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":special_instructions", $this->special_instructions);
        $stmt->bindParam(":address", $this->address);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readByCustomer($customer_id) {
        $query = "SELECT b.*, s.name as service_name, u.first_name as staff_first_name, u.last_name as staff_last_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN services s ON b.service_id = s.id
                  LEFT JOIN users u ON b.staff_id = u.id
                  WHERE b.customer_id = :customer_id
                  ORDER BY b.booking_date DESC, b.booking_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();
        return $stmt;
    }

    public function readAll() {
        $query = "SELECT b.*, s.name as service_name, 
                         c.first_name as customer_first_name, c.last_name as customer_last_name,
                         st.first_name as staff_first_name, st.last_name as staff_last_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN services s ON b.service_id = s.id
                  LEFT JOIN users c ON b.customer_id = c.id
                  LEFT JOIN users st ON b.staff_id = st.id
                  ORDER BY b.booking_date DESC, b.booking_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " SET status=:status WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function checkAvailability($date, $time, $duration) {
        $end_time = date('H:i:s', strtotime($time) + ($duration * 60));
        
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE booking_date = :date 
                  AND status NOT IN ('cancelled', 'completed')
                  AND (
                      (booking_time <= :time AND DATE_ADD(CONCAT(booking_date, ' ', booking_time), INTERVAL duration_minutes MINUTE) > :datetime) 
                      OR 
                      (booking_time < :end_time AND booking_time >= :time)
                  )";

        $stmt = $this->conn->prepare($query);
        $datetime = $date . ' ' . $time;
        
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":time", $time);
        $stmt->bindParam(":end_time", $end_time);
        $stmt->bindParam(":datetime", $datetime);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }
}
?>
