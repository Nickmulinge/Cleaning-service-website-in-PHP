<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Invoice.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle bulk invoice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: bulk-invoice.php');
        exit();
    }

    $selected_bookings = $_POST['bookings'] ?? [];
    $tax_rate = $_POST['tax_rate'] ?? 0;
    $due_days = $_POST['due_days'] ?? 30;
    $notes = $_POST['notes'] ?? '';

    if (empty($selected_bookings)) {
        $_SESSION['error'] = 'Please select at least one booking';
        header('Location: bulk-invoice.php');
        exit();
    }

    $success_count = 0;
    $invoice = new Invoice($db);

    foreach ($selected_bookings as $booking_id) {
        // Get booking details
        $booking_query = "SELECT b.*, u.id as customer_id 
                          FROM bookings b 
                          JOIN users u ON b.customer_id = u.id 
                          WHERE b.id = :booking_id";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->bindParam(':booking_id', $booking_id);
        $booking_stmt->execute();
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            $amount = $booking['total_price'];
            $tax_amount = ($amount * $tax_rate) / 100;
            $total_amount = $amount + $tax_amount;

            // Create invoice
            $create_query = "INSERT INTO invoices 
                             (booking_id, invoice_number, amount, tax_amount, total_amount, 
                              status, due_date, issued_date, notes, created_at)
                             VALUES (:booking_id, :invoice_number, :amount, :tax_amount, :total_amount,
                                     'pending', :due_date, :issued_date, :notes, NOW())";

            $stmt = $db->prepare($create_query);
            $invoice_number = $invoice->generateInvoiceNumber();
            $due_date = date('Y-m-d', strtotime("+$due_days days"));
            $issued_date = date('Y-m-d');

            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->bindParam(':invoice_number', $invoice_number);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':tax_amount', $tax_amount);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':due_date', $due_date);
            $stmt->bindParam(':issued_date', $issued_date);
            $stmt->bindParam(':notes', $notes);

            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }

    $_SESSION['success'] = "Successfully created $success_count invoice(s)!";
    header('Location: payments.php');
    exit();
}

// ✅ Always load bookings for display
$bookings_query = "SELECT b.*, s.name as service_name, s.base_price,
                   u.first_name, u.last_name, u.email
                   FROM bookings b
                   JOIN services s ON b.service_id = s.id
                   JOIN users u ON b.customer_id = u.id
                   WHERE b.id NOT IN (SELECT booking_id FROM invoices)
                   AND b.status IN ('completed', 'confirmed')
                   ORDER BY b.booking_date DESC";
$bookings_stmt = $db->query($bookings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Invoice Creation - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        
        body { background-color: #f8f9fa; }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            border: none;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
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
                    <h2 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Bulk Invoice Creation</h2>
                    <a href="payments.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Payments
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Invoice Settings</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Tax Rate (%)</label>
                                    <input type="number" class="form-control" name="tax_rate" value="0" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Due in (days)</label>
                                    <select class="form-select" name="due_days">
                                        <option value="7">7 days</option>
                                        <option value="14">14 days</option>
                                        <option value="30" selected>30 days</option>
                                        <option value="60">60 days</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Notes (Optional)</label>
                                    <input type="text" class="form-control" name="notes" placeholder="Payment instructions">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card table-card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Select Bookings</h5>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                        <i class="fas fa-check-square me-1"></i>Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                        <i class="fas fa-square me-1"></i>Deselect All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50"><input type="checkbox" id="selectAllCheckbox" onclick="toggleAll(this)"></th>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = $bookings_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="bookings[]" value="<?php echo $booking['id']; ?>" class="booking-checkbox">
                                            </td>
                                            <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td class="fw-bold">$<?php echo number_format($booking['total_price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['status'] === 'completed' ? 'success' : 'info'; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Selected: <strong id="selectedCount">0</strong> booking(s)</span>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-invoice me-2"></i>Create Invoices
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.booking-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
            updateCount();
        }

        function selectAll() {
            document.querySelectorAll('.booking-checkbox').forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
            updateCount();
        }

        function deselectAll() {
            document.querySelectorAll('.booking-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
            updateCount();
        }

        function updateCount() {
            const count = document.querySelectorAll('.booking-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = count;
        }

        // Update count on checkbox change
        document.querySelectorAll('.booking-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateCount);
        });
    </script>
</body>
</html>
