<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);

$booking->id = $_GET['id'] ?? 0;
$details = $booking->readOne();

if (!$details) {
    echo '<div class="alert alert-danger">Booking not found.</div>';
    exit();
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Customer Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Name:</th>
                <td><?php echo htmlspecialchars($details['customer_first_name'] . ' ' . $details['customer_last_name']); ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo htmlspecialchars($details['customer_email']); ?></td>
            </tr>
            <tr>
                <th>Phone:</th>
                <td><?php echo htmlspecialchars($details['customer_phone']); ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Booking Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Booking ID:</th>
                <td>#<?php echo $details['id']; ?></td>
            </tr>
            <tr>
                <th>Service:</th>
                <td><?php echo htmlspecialchars($details['service_name']); ?></td>
            </tr>
            <tr>
                <th>Date & Time:</th>
                <td><?php echo date('M j, Y g:i A', strtotime($details['booking_date'] . ' ' . $details['booking_time'])); ?></td>
            </tr>
            <tr>
                <th>Duration:</th>
                <td><?php echo $details['duration_minutes']; ?> minutes</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-<?php 
                        echo match($details['status']) {
                            'pending' => 'warning',
                            'confirmed' => 'info',
                            'in_progress' => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $details['status'])); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Service Details</h6>
        <p class="text-muted small"><?php echo htmlspecialchars($details['service_description']); ?></p>
        <p class="mb-0"><strong>Price:</strong> <span class="text-success fw-bold">$<?php echo number_format($details['total_price'], 2); ?></span></p>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">Assignment & Payment</h6>
        <p class="mb-2">
            <strong>Assigned Employee:</strong> 
            <?php if ($details['employee_first_name']): ?>
                <span class="badge bg-info"><?php echo htmlspecialchars($details['employee_first_name'] . ' ' . $details['employee_last_name']); ?></span>
            <?php else: ?>
                <span class="badge bg-secondary">Unassigned</span>
            <?php endif; ?>
        </p>
        <p class="mb-2">
            <strong>Payment Status:</strong> 
            <span class="badge badge-payment-<?php echo $details['payment_status'] === 'completed' ? 'paid' : 'unpaid'; ?>">
                <?php echo $details['payment_status'] === 'completed' ? 'Paid' : 'Unpaid'; ?>
            </span>
        </p>
        <p class="mb-0">
            <strong>Payment Method:</strong> <?php echo ucfirst($details['payment_method'] ?? 'Not specified'); ?>
        </p>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h6 class="fw-bold text-primary mb-3">Address</h6>
        <p class="mb-0"><?php echo htmlspecialchars($details['address']); ?></p>
    </div>
</div>

<?php if ($details['special_instructions']): ?>
<div class="row mt-3">
    <div class="col-12">
        <h6 class="fw-bold text-primary mb-3">Special Instructions</h6>
        <div class="alert alert-info mb-0">
            <?php echo nl2br(htmlspecialchars($details['special_instructions'])); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .badge-payment-paid {
        background-color: #28a745;
    }
    
    .badge-payment-unpaid {
        background-color: #dc3545;
    }
</style>
