<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Invoice.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all bookings without invoices
$bookings_query = "SELECT b.*, s.name as service_name, s.base_price,
                   u.first_name, u.last_name, u.email
                   FROM bookings b
                   JOIN services s ON b.service_id = s.id
                   JOIN users u ON b.customer_id = u.id
                   WHERE NOT EXISTS (SELECT 1 FROM invoices i WHERE i.booking_id = b.id)
                   AND b.status IN ('completed', 'confirmed', 'in_progress')
                   ORDER BY b.booking_date DESC";
$bookings_stmt = $db->query($bookings_query);


// Handle invoice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: create-invoice.php');
        exit();
    }

    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $tax_rate = $_POST['tax_rate'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $due_days = $_POST['due_days'] ?? 30;

    // Calculate tax and total
    $tax_amount = ($amount * $tax_rate) / 100;
    $total_amount = $amount + $tax_amount;

    // Get customer_id from booking
    $booking_query = "SELECT customer_id FROM bookings WHERE id = :booking_id";
    $booking_stmt = $db->prepare($booking_query);
    $booking_stmt->bindParam(':booking_id', $booking_id);
    $booking_stmt->execute();
    $booking_data = $booking_stmt->fetch(PDO::FETCH_ASSOC);

    // Create invoice
    $invoice = new Invoice($db);
    $invoice->booking_id = $booking_id;
    $invoice->invoice_number = $invoice->generateInvoiceNumber();
    $invoice->amount = $amount;
    $invoice->tax_amount = $tax_amount;
    $invoice->total_amount = $total_amount;
    $invoice->status = 'pending';
    $invoice->due_date = date('Y-m-d', strtotime("+$due_days days"));
    $invoice->issued_date = date('Y-m-d');
    $invoice->notes = $notes;

// Add invoice (no customer_id column in invoices)
$create_query = "INSERT INTO invoices 
                 (booking_id, invoice_number, amount, tax_amount, total_amount, 
                  status, due_date, issued_date, notes, created_at)
                 VALUES (:booking_id, :invoice_number, :amount, :tax_amount, :total_amount,
                         :status, :due_date, :issued_date, :notes, NOW())";

$stmt = $db->prepare($create_query);
$stmt->bindParam(':booking_id', $invoice->booking_id);
$stmt->bindParam(':invoice_number', $invoice->invoice_number);
$stmt->bindParam(':amount', $invoice->amount);
$stmt->bindParam(':tax_amount', $invoice->tax_amount);
$stmt->bindParam(':total_amount', $invoice->total_amount);
$stmt->bindParam(':status', $invoice->status);
$stmt->bindParam(':due_date', $invoice->due_date);
$stmt->bindParam(':issued_date', $invoice->issued_date);
$stmt->bindParam(':notes', $invoice->notes);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Invoice created successfully!';
    header('Location: payments.php');
    exit();
} else {
    $_SESSION['error'] = 'Failed to create invoice.';
}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --admin-dark: #2c3e50;
        }
        
        body { background-color: #f8f9fa; }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--admin-dark);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            border: none;
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
                    <h2 class="fw-bold mb-0"><i class="fas fa-file-invoice me-2"></i>Create Invoice</h2>
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

                <div class="card form-card">
                    <div class="card-body p-4">
                        <form method="POST" id="invoiceForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-4">
                                <label for="booking_id" class="form-label">
                                    <i class="fas fa-calendar-check text-primary me-2"></i>Select Booking
                                </label>
                                <select class="form-select form-select-lg" id="booking_id" name="booking_id" required>
                                    <option value="">Choose a booking...</option>
                                    <?php while ($booking = $bookings_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $booking['id']; ?>" 
                                                data-price="<?php echo $booking['total_price']; ?>"
                                                data-customer="<?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>"
                                                data-service="<?php echo htmlspecialchars($booking['service_name']); ?>">
                                            #<?php echo $booking['id']; ?> - <?php echo htmlspecialchars($booking['service_name']); ?> - 
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?> - 
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> - 
                                            $<?php echo number_format($booking['total_price'], 2); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="amount" class="form-label">
                                        <i class="fas fa-dollar-sign text-success me-2"></i>Amount
                                    </label>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tax_rate" class="form-label">
                                        <i class="fas fa-percent text-info me-2"></i>Tax Rate (%)
                                    </label>
                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                           value="0" step="0.01" min="0" max="100">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="due_days" class="form-label">
                                    <i class="fas fa-calendar-alt text-warning me-2"></i>Due in (days)
                                </label>
                                <select class="form-select" id="due_days" name="due_days">
                                    <option value="7">7 days</option>
                                    <option value="14">14 days</option>
                                    <option value="30" selected>30 days</option>
                                    <option value="60">60 days</option>
                                    <option value="90">90 days</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-sticky-note text-secondary me-2"></i>Notes (Optional)
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Add any additional notes or payment instructions"></textarea>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="fw-bold mb-2">Invoice Preview</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer:</strong> <span id="preview_customer">-</span></p>
                                        <p class="mb-1"><strong>Service:</strong> <span id="preview_service">-</span></p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <p class="mb-1"><strong>Subtotal:</strong> $<span id="preview_amount">0.00</span></p>
                                        <p class="mb-1"><strong>Tax:</strong> $<span id="preview_tax">0.00</span></p>
                                        <p class="mb-0"><strong class="fs-5">Total:</strong> <span class="fs-5 text-success">$<span id="preview_total">0.00</span></span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Create Invoice
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill amount when booking is selected
        document.getElementById('booking_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const customer = selectedOption.getAttribute('data-customer');
            const service = selectedOption.getAttribute('data-service');
            
            if (price) {
                document.getElementById('amount').value = parseFloat(price).toFixed(2);
                document.getElementById('preview_customer').textContent = customer;
                document.getElementById('preview_service').textContent = service;
                calculateTotal();
            }
        });

        // Calculate total when amount or tax changes
        document.getElementById('amount').addEventListener('input', calculateTotal);
        document.getElementById('tax_rate').addEventListener('input', calculateTotal);

        function calculateTotal() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const tax = (amount * taxRate) / 100;
            const total = amount + tax;

            document.getElementById('preview_amount').textContent = amount.toFixed(2);
            document.getElementById('preview_tax').textContent = tax.toFixed(2);
            document.getElementById('preview_total').textContent = total.toFixed(2);
        }
    </script>
</body>
</html>
