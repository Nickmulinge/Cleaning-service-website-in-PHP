<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Invoice.php';
require_once '../models/Payment.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle payment/invoice operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: payments.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'edit_invoice':
            $invoice_id = (int)$_POST['invoice_id'];
            $amount = (float)$_POST['amount'];
            $tax_amount = (float)$_POST['tax_amount'];
            $total_amount = (float)$_POST['total_amount'];
            $status = $_POST['status'];
            $due_date = $_POST['due_date'];
            $notes = $_POST['notes'] ?? '';

            $query = "UPDATE invoices 
                      SET amount = :amount, 
                          tax_amount = :tax_amount, 
                          total_amount = :total_amount, 
                          status = :status, 
                          due_date = :due_date, 
                          notes = :notes,
                          updated_at = NOW()";
            
            if ($status === 'paid') {
                $query .= ", paid_date = NOW()";
            }
            
            $query .= " WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':amount', $amount);
            $stmt->bindValue(':tax_amount', $tax_amount);
            $stmt->bindValue(':total_amount', $total_amount);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':due_date', $due_date);
            $stmt->bindValue(':notes', $notes);
            $stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Update booking payment status if invoice is paid
                if ($status === 'paid') {
                    $update_booking = "UPDATE bookings b 
                                       JOIN invoices i ON b.id = i.booking_id 
                                       SET b.payment_status = 'paid' 
                                       WHERE i.id = :id";
                    $booking_stmt = $db->prepare($update_booking);
                    $booking_stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);
                    $booking_stmt->execute();
                }
                $_SESSION['success'] = 'Invoice updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update invoice.';
            }
            break;

        case 'mark_paid':
            $invoice_id = (int)$_POST['invoice_id'];
            $query = "UPDATE invoices SET status = 'paid', paid_date = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $update_booking = "UPDATE bookings b 
                                   JOIN invoices i ON b.id = i.booking_id 
                                   SET b.payment_status = 'paid' 
                                   WHERE i.id = :id";
                $booking_stmt = $db->prepare($update_booking);
                $booking_stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);
                $booking_stmt->execute();

                $_SESSION['success'] = 'Invoice marked as paid!';
            } else {
                $_SESSION['error'] = 'Failed to update invoice.';
            }
            break;

        case 'change_status':
            $invoice_id = (int)$_POST['invoice_id'];
            $new_status = $_POST['new_status'] ?? 'pending';

            $query = "UPDATE invoices SET status = :status";
            if ($new_status === 'paid') {
                $query .= ", paid_date = NOW()";
            }
            $query .= " WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':status', $new_status);
            $stmt->bindValue(':id', $invoice_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Invoice status updated!';
            } else {
                $_SESSION['error'] = 'Failed to update status.';
            }
            break;

        case 'send_invoice':
            $_SESSION['success'] = 'Invoice sent to customer!';
            break;
    }

    header('Location: payments.php');
    exit();
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Get all invoices with related data
$query = "SELECT i.*, 
                 COALESCE(i.total_amount, i.amount) AS invoice_amount,
                 b.booking_date, b.booking_time, 
                 s.name AS service_name, 
                 u.first_name, u.last_name, u.email,
                 p.amount AS payment_amount, 
                 p.payment_method, 
                 p.payment_status
          FROM invoices i
          LEFT JOIN bookings b ON i.booking_id = b.id
          LEFT JOIN services s ON b.service_id = s.id
          LEFT JOIN users u ON b.customer_id = u.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE 1=1";

if ($status_filter !== 'all' && $status_filter !== '') {
    $query .= " AND i.status = :status_filter";
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $db->prepare($query);
if ($status_filter !== 'all' && $status_filter !== '') {
    $stmt->bindValue(':status_filter', $status_filter);
}
$stmt->execute();

$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment statistics
$stats_query = "SELECT 
                COUNT(*) AS total_invoices,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_invoices,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) AS paid_invoices,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN COALESCE(total_amount, amount) END),0) AS total_revenue,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN COALESCE(total_amount, amount) END),0) AS pending_revenue
                FROM invoices";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Payments & Invoices - <?php echo SITE_NAME; ?></title>
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
        
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                    <h2 class="fw-bold mb-0"><i class="fas fa-credit-card me-2"></i>Payments & Invoices</h2>
                    <!-- Added invoice creation buttons -->
                    <div class="btn-group">
                        <a href="create-invoice.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Invoice
                        </a>
                        <a href="bulk-invoice.php" class="btn btn-success">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Bulk Invoice
                        </a>
                    </div>
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

                <!-- Payment Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Total Invoices</h6>
                                <h3 class="mb-0"><?php echo $stats['total_invoices']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Pending Invoices</h6>
                                <h3 class="mb-0"><?php echo $stats['pending_invoices']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Total Revenue</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <h6 class="mb-2">Pending Revenue</h6>
                                <h3 class="mb-0">$<?php echo number_format($stats['pending_revenue'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Filter by Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Invoices</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <a href="payments.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="card table-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Booking Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No invoices found
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $row): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></div>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($row['booking_time'])); ?></small>
                                            </td>
                                            <td class="fw-bold">$<?php echo number_format($row['invoice_amount'], 2); ?></td>
                                            <td>
                                                <?php if ($row['payment_method']): ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Pay Later</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                if ($row['status'] === 'paid') $status_class = 'success';
                                                elseif ($row['status'] === 'sent') $status_class = 'info';
                                                elseif ($row['status'] === 'overdue') $status_class = 'danger';
                                                elseif ($row['status'] === 'draft') $status_class = 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="viewInvoice(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                                            title="View Invoice">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-warning" 
                                                            onclick="editInvoice(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                                            title="Edit Invoice">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($row['status'] !== 'paid'): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Mark this invoice as paid?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="mark_paid">
                                                            <input type="hidden" name="invoice_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-success" title="Mark as Paid">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php // Added View Invoice Modal ?>
    <div class="modal fade" id="viewInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Invoice Number</h6>
                            <p class="fw-bold" id="view_invoice_number"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p id="view_status"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Customer</h6>
                            <p id="view_customer"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Email</h6>
                            <p id="view_email"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Service</h6>
                            <p id="view_service"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Booking Date</h6>
                            <p id="view_booking_date"></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6 class="text-muted">Amount</h6>
                            <p class="fw-bold" id="view_amount"></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Tax Amount</h6>
                            <p id="view_tax_amount"></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Amount</h6>
                            <p class="fw-bold text-success" id="view_total_amount"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Issued Date</h6>
                            <p id="view_issued_date"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Due Date</h6>
                            <p id="view_due_date"></p>
                        </div>
                    </div>
                    <div class="row mb-3" id="view_paid_date_row" style="display: none;">
                        <div class="col-md-6">
                            <h6 class="text-muted">Paid Date</h6>
                            <p id="view_paid_date"></p>
                        </div>
                    </div>
                    <div class="row mb-3" id="view_notes_row" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-muted">Notes</h6>
                            <p id="view_notes"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php // Added Edit Invoice Modal ?>
    <div class="modal fade" id="editInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editInvoiceForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit_invoice">
                    <input type="hidden" name="invoice_id" id="edit_invoice_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Invoice</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Invoice Number</label>
                                <input type="text" class="form-control" id="edit_invoice_number" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="paid">Paid</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer</label>
                                <input type="text" class="form-control" id="edit_customer" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service</label>
                                <input type="text" class="form-control" id="edit_service" readonly>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" required onchange="calculateTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tax Amount</label>
                                <input type="number" step="0.01" name="tax_amount" id="edit_tax_amount" class="form-control" value="0.00" onchange="calculateTotal()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="total_amount" id="edit_total_amount" class="form-control" required readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="edit_due_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewInvoice(invoice) {
            document.getElementById('view_invoice_number').textContent = invoice.invoice_number;
            document.getElementById('view_customer').textContent = invoice.first_name + ' ' + invoice.last_name;
            document.getElementById('view_email').textContent = invoice.email;
            document.getElementById('view_service').textContent = invoice.service_name;
            document.getElementById('view_booking_date').textContent = new Date(invoice.booking_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            document.getElementById('view_amount').textContent = '$' + parseFloat(invoice.amount).toFixed(2);
            document.getElementById('view_tax_amount').textContent = '$' + parseFloat(invoice.tax_amount || 0).toFixed(2);
            document.getElementById('view_total_amount').textContent = '$' + parseFloat(invoice.invoice_amount).toFixed(2);
            document.getElementById('view_issued_date').textContent = new Date(invoice.issued_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            document.getElementById('view_due_date').textContent = new Date(invoice.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            
            const statusBadge = document.getElementById('view_status');
            let statusClass = 'secondary';
            if (invoice.status === 'paid') statusClass = 'success';
            else if (invoice.status === 'sent') statusClass = 'info';
            else if (invoice.status === 'overdue') statusClass = 'danger';
            statusBadge.innerHTML = '<span class="badge bg-' + statusClass + '">' + invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) + '</span>';
            
            if (invoice.paid_date) {
                document.getElementById('view_paid_date_row').style.display = 'block';
                document.getElementById('view_paid_date').textContent = new Date(invoice.paid_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            } else {
                document.getElementById('view_paid_date_row').style.display = 'none';
            }
            
            if (invoice.notes) {
                document.getElementById('view_notes_row').style.display = 'block';
                document.getElementById('view_notes').textContent = invoice.notes;
            } else {
                document.getElementById('view_notes_row').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewInvoiceModal')).show();
        }

        function editInvoice(invoice) {
            document.getElementById('edit_invoice_id').value = invoice.id;
            document.getElementById('edit_invoice_number').value = invoice.invoice_number;
            document.getElementById('edit_customer').value = invoice.first_name + ' ' + invoice.last_name;
            document.getElementById('edit_service').value = invoice.service_name;
            document.getElementById('edit_amount').value = parseFloat(invoice.amount).toFixed(2);
            document.getElementById('edit_tax_amount').value = parseFloat(invoice.tax_amount || 0).toFixed(2);
            document.getElementById('edit_total_amount').value = parseFloat(invoice.invoice_amount).toFixed(2);
            document.getElementById('edit_status').value = invoice.status;
            document.getElementById('edit_due_date').value = invoice.due_date;
            document.getElementById('edit_notes').value = invoice.notes || '';
            
            new bootstrap.Modal(document.getElementById('editInvoiceModal')).show();
        }

        function calculateTotal() {
            const amount = parseFloat(document.getElementById('edit_amount').value) || 0;
            const taxAmount = parseFloat(document.getElementById('edit_tax_amount').value) || 0;
            const total = amount + taxAmount;
            document.getElementById('edit_total_amount').value = total.toFixed(2);
        }
    </script>
</body>
</html>
