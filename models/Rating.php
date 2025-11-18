<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Rating Model
 * Handles service ratings and reviews
 */
class Rating {
    private $conn;
    private $table_name = "service_ratings"; // ✅ make sure it's consistent

    public $id;
    public $booking_id;
    public $customer_id;
    public $rating;
    public $comment;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new rating
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (booking_id, customer_id, rating, comment) 
                  VALUES (:booking_id, :customer_id, :rating, :comment)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->customer_id = htmlspecialchars(strip_tags($this->customer_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":customer_id", $this->customer_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        return $stmt->execute();
    }

    /**
     * Check if booking has been rated
     */
    public function hasRating($booking_id) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE booking_id = :booking_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }

    /**
     * Get rating by booking ID
     */
    public function getByBookingId($booking_id) {
        $query = "SELECT * 
                  FROM " . $this->table_name . " 
                  WHERE booking_id = :booking_id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get average rating for a service
     */
    public function getAverageRatingByService($service_id) {
        $query = "SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as total_ratings
                  FROM " . $this->table_name . " r
                  INNER JOIN bookings b ON r.booking_id = b.id
                  WHERE b.service_id = :service_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":service_id", $service_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all ratings (for admin)
     */
    public function readAll() {
        $query = "SELECT r.*, b.booking_date, s.name as service_name,
                         u.first_name, u.last_name
                  FROM " . $this->table_name . " r
                  INNER JOIN bookings b ON r.booking_id = b.id
                  INNER JOIN services s ON b.service_id = s.id
                  INNER JOIN users u ON r.customer_id = u.id
                  ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>
