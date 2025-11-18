<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();

$staff_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: bookings.php');
        exit();
    }
    
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    $update_query = "UPDATE bookings SET status = :status, notes = :notes WHERE id = :id AND employee_id = :staff_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':notes', $notes);
    $update_stmt->bindParam(':id', $booking_id);
    $update_stmt->bindParam(':staff_id', $staff_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = 'Booking status updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update booking status';
    }
    
    header('Location: bookings.php');
    exit();
}

// Filter
$status_filter = $_GET['status'] ?? 'all';

// Get bookings
$query = "SELECT 
    b.id,
    b.booking_date,
    b.booking_time,
    b.status,
    b.address,
    b.total_price,
    b.payment_status,
    b.special_instructions,
    s.name as service_name,
    s.duration,
    u.first_name as customer_first_name,
    u.last_name as customer_last_name,
    u.phone as customer_phone,
    u.email as customer_email
FROM bookings b
JOIN services s ON b.service_id = s.id
JOIN users u ON b.customer_id = u.id
WHERE b.employee_id = :staff_id";

if ($status_filter != 'all') {
    $query .= " AND b.status = :status";
}

$query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':staff_id', $staff_id);
if ($status_filter != 'all') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --staff-purple: #7E57C2;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .booking-card {
            border: none;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--staff-purple);
        }
        
        .booking-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }
        
        .payment-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .payment-paid {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .payment-unpaid {
            background: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">My Bookings</h2>
                    
                    <div class="btn-group">
                        <a href="?status=all" class="btn btn-sm <?php echo $status_filter == 'all' ? 'btn-purple' : 'btn-outline-secondary'; ?>">All</a>
                        <a href="?status=pending" class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-purple' : 'btn-outline-secondary'; ?>">Pending</a>
                        <a href="?status=in_progress" class="btn btn-sm <?php echo $status_filter == 'in_progress' ? 'btn-purple' : 'btn-outline-secondary'; ?>">In Progress</a>
                        <a href="?status=completed" class="btn btn-sm <?php echo $status_filter == 'completed' ? 'btn-purple' : 'btn-outline-secondary'; ?>">Completed</a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while ($booking = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="card booking-card">
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                                                <div class="text-muted small">
                                                    <i class="fas fa-hashtag me-1"></i>Booking ID: <?php echo $booking['id']; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                                </span>
                                                <br>
                                                <span class="payment-badge payment-<?php echo $booking['payment_status']; ?> mt-2 d-inline-block">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1">
                                                    <i class="fas fa-calendar me-2"></i>Date & Time
                                                </div>
                                                <div class="fw-semibold">
                                                    <?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?>
                                                </div>
                                                <div class="text-muted">
                                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?> 
                                                    (<?php echo $booking['duration']; ?> mins)
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1">
                                                    <i class="fas fa-user me-2"></i>Customer
                                                </div>
                                                <div class="fw-semibold">
                                                    <?php echo htmlspecialchars($booking['customer_first_name'] . ' ' . $booking['customer_last_name']); ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($booking['customer_phone']); ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($booking['customer_email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="small text-muted mb-1">
                                                <i class="fas fa-map-marker-alt me-2"></i>Service Address
                                            </div>
                                            <div><?php echo htmlspecialchars($booking['address']); ?></div>
                                        </div>
                                        
                                        <?php if (!empty($booking['notes'])): ?>
                                            <div class="alert alert-info mb-0">
                                                <strong><i class="fas fa-sticky-note me-2"></i>Notes:</strong>
                                                <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4 border-start">
                                        <h6 class="fw-bold mb-3">Update Status</h6>
                                        
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo $booking['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Service Notes</label>
                                                <textarea name="notes" class="form-control" rows="4" placeholder="Add any notes about the service..."><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-purple w-100">
                                                <i class="fas fa-save me-2"></i>Update Booking
                                            </button>
                                        </form>
                                        
                                        <div class="mt-3 pt-3 border-top">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">Total Price:</span>
                                                <span class="fw-bold">${<?php echo number_format($booking['total_price'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No bookings found</h5>
                            <p class="text-muted">You don't have any <?php echo $status_filter != 'all' ? $status_filter : ''; ?> bookings assigned yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    .btn-purple {
        background: linear-gradient(135deg, #7E57C2, #9575cd);
        color: white;
        border: none;
    }
    
    .btn-purple:hover {
        background: linear-gradient(135deg, #5e35b1, #7E57C2);
        color: white;
    }
</style>
