<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Service.php';

class BookingController {
    private $db;
    private $booking;
    private $service;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->booking = new Booking($this->db);
        $this->service = new Service($this->db);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: customer/book-service.php');
                exit();
            }

            // Get service details for pricing and duration
            $this->service->id = $_POST['service_id'];
            $this->service->readOne();

            $this->booking->customer_id = $_SESSION['user_id'];
            $this->booking->service_id = $_POST['service_id'];
            $this->booking->booking_date = $_POST['booking_date'];
            $this->booking->booking_time = $_POST['booking_time'];
            $this->booking->duration_minutes = $this->service->duration_minutes;
            $this->booking->total_price = $this->service->base_price;
            $this->booking->special_instructions = $_POST['special_instructions'];
            $this->booking->address = $_POST['address'];

            // Check availability
            if (!$this->booking->checkAvailability(
                $this->booking->booking_date, 
                $this->booking->booking_time, 
                $this->booking->duration_minutes
            )) {
                $_SESSION['error'] = 'Selected time slot is not available';
                header('Location: customer/book-service.php');
                exit();
            }

            if ($this->booking->create()) {
                $_SESSION['success'] = 'Booking created successfully!';
                header('Location: dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = 'Failed to create booking';
                header('Location: customer/book-service.php');
                exit();
            }
        }
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                return false;
            }

            $this->booking->id = $_POST['booking_id'];
            $this->booking->status = $_POST['status'];

            if ($this->booking->updateStatus()) {
                $_SESSION['success'] = 'Booking status updated successfully!';
                return true;
            } else {
                $_SESSION['error'] = 'Failed to update booking status';
                return false;
            }
        }
    }
}
?>
