<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';
require_once '../models/User.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle booking status updates and employee assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: bookings.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $query = "UPDATE bookings SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_POST['booking_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Booking status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update booking status.';
        }
    } elseif ($action === 'assign_employee') {
        $query = "UPDATE bookings SET employee_id = :employee_id WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':employee_id', $_POST['employee_id']);
        $stmt->bindParam(':id', $_POST['booking_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Employee assigned successfully!';
        } else {
            $_SESSION['error'] = 'Failed to assign employee.';
        }
    }

    header('Location: bookings.php');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$payment_filter = $_GET['payment'] ?? 'all';

// Build query with filters
$query = "SELECT b.*, 
          u.first_name as customer_first_name, u.last_name as customer_last_name, 
          u.email as customer_email, u.phone as customer_phone,
          s.name as service_name, s.base_price,
          e.first_name as employee_first_name, e.last_name as employee_last_name,
          COALESCE(p.payment_status, 'unpaid') as payment_status,
          p.amount as payment_amount
          FROM bookings b
          JOIN users u ON b.customer_id = u.id
          JOIN services s ON b.service_id = s.id
          LEFT JOIN users e ON b.employee_id = e.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND b.status = :status_filter";
}

if ($payment_filter === 'paid') {
    $query .= " AND p.payment_status = 'completed'";
} elseif ($payment_filter === 'unpaid') {
    $query .= " AND (p.payment_status IS NULL OR p.payment_status != 'completed')";
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
if ($status_filter !== 'all') {
    $stmt->bindParam(':status_filter', $status_filter);
}
$stmt->execute();

// Get employees for assignment dropdown
$employees_query = "SELECT id, first_name, last_name FROM users WHERE role = 'staff' AND status = 'active' ORDER BY first_name, last_name";
$employees_stmt = $db->prepare($employees_query);
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --admin-dark: #2c3e50;
            --admin-darker: #1a252f;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .admin-navbar {
            background: linear-gradient(135deg, var(--admin-dark) 0%, var(--admin-darker) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: white;
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        
        .sidebar .list-group-item {
            border: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            padding: 15px 20px;
        }
        
        .sidebar .list-group-item:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary-blue);
            transform: translateX(5px);
        }
        
        .sidebar .list-group-item.active {
            background: linear-gradient(90deg, rgba(30, 136, 229, 0.1) 0%, transparent 100%);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
            font-weight: 600;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2"></i>Manage Bookings</h2>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                 Filters 
                <div class="filter-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filter by Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Filter by Payment</label>
                            <select name="payment" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $payment_filter === 'all' ? 'selected' : ''; ?>>All Payments</option>
                                <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="unpaid" <?php echo $payment_filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <a href="bookings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset Filters
                            </a>
                        </div>
                    </form>
                </div>

                 Bookings Table 
                <div class="card table-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Employee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $row['id']; ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['customer_first_name'] . ' ' . $row['customer_last_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['customer_email'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                        <td>
                                            <div><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></div>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($row['booking_time'])); ?></small>
                                        </td>
                                        <td class="fw-bold">$<?php echo number_format($row['base_price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($row['status']) {
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'in_progress' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <i class="fas fa-<?php echo $row['payment_status'] === 'completed' ? 'check-circle' : 'clock'; ?> me-1"></i>
                                                <?php echo ucfirst($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($row['employee_first_name']) && $row['employee_first_name']): ?>
                                                <small><?php echo htmlspecialchars($row['employee_first_name'] . ' ' . ($row['employee_last_name'] ?? '')); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewBooking(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

     Booking Details Modal 
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Customer Information</h6>
                            <p><strong>Name:</strong> <span id="modal_customer_name"></span></p>
                            <p><strong>Email:</strong> <span id="modal_customer_email"></span></p>
                            <p><strong>Phone:</strong> <span id="modal_customer_phone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Booking Information</h6>
                            <p><strong>Service:</strong> <span id="modal_service_name"></span></p>
                            <p><strong>Date:</strong> <span id="modal_booking_date"></span></p>
                            <p><strong>Time:</strong> <span id="modal_booking_time"></span></p>
                            <p><strong>Price:</strong> <span id="modal_price"></span></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Update Status</h6>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" id="status_booking_id">
                                <div class="mb-3">
                                    <select name="status" class="form-select" id="modal_status">
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Update Status</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Assign Employee</h6>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="assign_employee">
                                <input type="hidden" name="booking_id" id="assign_booking_id">
                                <div class="mb-3">
                                    <select name="employee_id" class="form-select" id="modal_employee">
                                        <option value="">Select Employee</option>
                                        <?php 
                                        foreach ($employees as $emp): 
                                        ?>
                                            <option value="<?php echo $emp['id']; ?>">
                                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Assign Employee</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewBooking(booking) {
            document.getElementById('modal_customer_name').textContent = booking.customer_first_name + ' ' + booking.customer_last_name;
            document.getElementById('modal_customer_email').textContent = booking.customer_email || 'N/A';
            document.getElementById('modal_customer_phone').textContent = booking.customer_phone || 'N/A';
            document.getElementById('modal_service_name').textContent = booking.service_name;
            document.getElementById('modal_booking_date').textContent = new Date(booking.booking_date).toLocaleDateString();
            document.getElementById('modal_booking_time').textContent = booking.booking_time;
            document.getElementById('modal_price').textContent = '$' + parseFloat(booking.base_price).toFixed(2);
            document.getElementById('modal_status').value = booking.status;
            document.getElementById('status_booking_id').value = booking.id;
            document.getElementById('assign_booking_id').value = booking.id;
            
            if (booking.employee_id) {
                document.getElementById('modal_employee').value = booking.employee_id;
            }
            
            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }
    </script>
</body>
</html>
