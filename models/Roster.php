<?php
require_once 'config/database.php';

class Roster {
    private $conn;
    private $table_name = "rosters";

    public $id;
    public $staff_id;
    public $booking_id;
    public $date;
    public $start_time;
    public $end_time;
    public $location;
    public $status;
    public $notes;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET staff_id=:staff_id, booking_id=:booking_id, date=:date, 
                      start_time=:start_time, end_time=:end_time, location=:location, 
                      status=:status, notes=:notes";

        $stmt = $this->conn->prepare($query);

        $this->staff_id = htmlspecialchars(strip_tags($this->staff_id));
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->date = htmlspecialchars(strip_tags($this->date));
        $this->start_time = htmlspecialchars(strip_tags($this->start_time));
        $this->end_time = htmlspecialchars(strip_tags($this->end_time));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        $stmt->bindParam(":staff_id", $this->staff_id);
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":start_time", $this->start_time);
        $stmt->bindParam(":end_time", $this->end_time);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":notes", $this->notes);

        return $stmt->execute();
    }

    public function getStaffRoster($staff_id, $start_date, $end_date) {
        $query = "SELECT r.*, b.service_name, u.first_name, u.last_name
                  FROM " . $this->table_name . " r
                  LEFT JOIN bookings b ON r.booking_id = b.id
                  LEFT JOIN users u ON b.customer_id = u.id
                  WHERE r.staff_id = :staff_id 
                  AND r.date BETWEEN :start_date AND :end_date
                  ORDER BY r.date, r.start_time";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":staff_id", $staff_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status=:status, updated_at=NOW() 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function getAllRosters($limit = 50, $offset = 0) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.username,
                         b.service_name, c.first_name as customer_first_name, 
                         c.last_name as customer_last_name
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.staff_id = u.id
                  LEFT JOIN bookings b ON r.booking_id = b.id
                  LEFT JOIN users c ON b.customer_id = c.id
                  ORDER BY r.date DESC, r.start_time
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
}
?>
