<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$customer_id = $_GET['id'] ?? 0;

// Get customer info
$customer_query = "SELECT * FROM users WHERE id = :id AND role = 'customer'";
$stmt = $db->prepare($customer_query);
$stmt->bindParam(':id', $customer_id);
$stmt->execute();
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo '<div class="alert alert-danger">Customer not found.</div>';
    exit();
}

// Get customer bookings
// Get customer bookings
$bookings_query = "
    SELECT b.*, 
           s.name AS service_name, 
           COALESCE(p.payment_status, 'unpaid') AS payment_status
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.customer_id = :id
    ORDER BY b.booking_date DESC
    LIMIT 10
";
$stmt = $db->prepare($bookings_query);
$stmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Customer Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Name:</th>
                <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($customer['email']); ?></td>
            </tr>
            <tr>
                <th>Phone:</th>
                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($customer['status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Joined:</th>
                <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Booking Statistics</h6>
        <div class="row g-3">
            <div class="col-6">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo count($bookings); ?></h3>
                        <small class="text-muted">Total Bookings</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h3 class="mb-0"><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'completed')); ?></h3>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h6 class="fw-bold text-primary mb-3">Recent Bookings</h6>
        <?php if (count($bookings) > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>#<?php echo $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                        <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($booking['status']) {
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'in_progress' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $booking['payment_status'] === 'completed' ? 'success' : 'danger'; ?>">
                                <?php echo $booking['payment_status'] === 'completed' ? 'Paid' : 'Unpaid'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No bookings found for this customer.</p>
        <?php endif; ?>
    </div>
</div>
