<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get invoice ID from URL
$invoice_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$invoice_id) {
    $_SESSION['error'] = 'Invalid invoice ID';
    header('Location: invoices.php');
    exit();
}

// Fetch invoice details with related booking and service information
$query = "SELECT i.*, 
                 b.booking_date, b.booking_time, b.address, 
                 s.name as service_name, s.description as service_description,
                 u.first_name as customer_first_name, u.last_name as customer_last_name,
                 u.email as customer_email, u.phone as customer_phone,
                 staff.first_name as staff_first_name, staff.last_name as staff_last_name
          FROM invoices i
          JOIN bookings b ON i.booking_id = b.id
          JOIN services s ON b.service_id = s.id
          JOIN users u ON b.customer_id = u.id
          LEFT JOIN users staff ON b.staff_id = staff.id
          WHERE i.id = :invoice_id AND b.customer_id = :customer_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':invoice_id', $invoice_id, PDO::PARAM_INT);
$stmt->bindParam(':customer_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found';
    header('Location: invoices.php');
    exit();
}

$isPrintView = isset($_GET['download']) && $_GET['download'] === 'pdf';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo SITE_NAME; ?></title>
    <?php if (!$isPrintView): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?php endif; ?>
    <style>
        <?php if ($isPrintView): ?>
        /* Print-friendly styles for PDF generation */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: white;
            color: #000;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #43A047;
        }
        
        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #43A047;
            margin-bottom: 10px;
        }
        
        .invoice-title {
            font-size: 24px;
            color: #333;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section {
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #43A047;
        }
        
        .info-section h3 {
            color: #43A047;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: bold;
            color: #666;
        }
        
        .value {
            color: #000;
        }
        
        .service-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        
        .service-table th {
            background: #43A047;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        .service-table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .amount-section {
            margin-top: 30px;
            text-align: right;
        }
        
        .amount-row {
            padding: 10px 0;
            font-size: 16px;
        }
        
        .total-row {
            font-size: 24px;
            font-weight: bold;
            color: #43A047;
            padding-top: 15px;
            border-top: 3px solid #43A047;
            margin-top: 15px;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #43A047;
        }
        
        .notes-section h3 {
            color: #43A047;
            margin-bottom: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 14px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .status-paid { background: #4CAF50; color: white; }
        .status-sent { background: #2196F3; color: white; }
        .status-overdue { background: #f44336; color: white; }
        .status-draft { background: #9E9E9E; color: white; }
        .status-cancelled { background: #424242; color: white; }
        
        @media print {
            body { padding: 20px; }
            @page { margin: 1cm; }
        }
        <?php else: ?>
        /* Regular view styles */
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .invoice-container {
            max-width: 900px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
        }
        
        .invoice-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .invoice-body {
            padding: 2.5rem;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary-green);
        }
        
        .info-card h5 {
            color: var(--primary-green);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .service-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .amount-breakdown {
            background: linear-gradient(135deg, rgba(67,160,71,0.1) 0%, rgba(30,136,229,0.1) 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 1.1rem;
        }
        
        .total-row {
            border-top: 3px solid var(--primary-green);
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-custom {
            flex: 1;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .btn-download {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
        }
        
        .btn-pay {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php if ($isPrintView): ?>
     Print-friendly PDF view 
    <div class="invoice-header">
        <div class="company-name">CLEANFINITY</div>
        <div class="invoice-title">INVOICE</div>
    </div>
    
    <div class="invoice-info">
        <div class="info-section">
            <h3>Invoice Details</h3>
            <div class="info-row">
                <span class="label">Invoice Number:</span>
                <span class="value"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Issue Date:</span>
                <span class="value"><?php echo date('F j, Y', strtotime($invoice['issued_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Due Date:</span>
                <span class="value"><?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></span>
            </div>
            <?php if ($invoice['paid_date']): ?>
            <div class="info-row">
                <span class="label">Paid Date:</span>
                <span class="value"><?php echo date('F j, Y', strtotime($invoice['paid_date'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="status-badge status-<?php echo $invoice['status']; ?>">
                    <?php echo strtoupper($invoice['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="info-section">
            <h3>Bill To</h3>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value"><?php echo htmlspecialchars($invoice['customer_first_name'] . ' ' . $invoice['customer_last_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value"><?php echo htmlspecialchars($invoice['customer_email']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value"><?php echo htmlspecialchars($invoice['customer_phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Address:</span>
                <span class="value">
                    <?php echo htmlspecialchars($invoice['address']); ?><br>
                    <?php echo htmlspecialchars($invoice['city'] . ', ' . $invoice['state'] . ' ' . $invoice['zip_code']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <table class="service-table">
        <thead>
            <tr>
                <th>Service Description</th>
                <th>Service Date</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($invoice['service_name']); ?></strong><br>
                    <?php if ($invoice['staff_first_name']): ?>
                    <small>Staff: <?php echo htmlspecialchars($invoice['staff_first_name'] . ' ' . $invoice['staff_last_name']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo date('F j, Y', strtotime($invoice['booking_date'])); ?><br>
                    <small><?php echo date('g:i A', strtotime($invoice['booking_time'])); ?></small>
                </td>
                <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="amount-section">
        <div class="amount-row">
            <strong>Subtotal:</strong> $<?php echo number_format($invoice['amount'], 2); ?>
        </div>
        <div class="amount-row">
            <strong>Tax:</strong> $<?php echo number_format($invoice['tax_amount'], 2); ?>
        </div>
        <div class="amount-row total-row">
            <strong>TOTAL:</strong> $<?php echo number_format($invoice['total_amount'], 2); ?>
        </div>
    </div>
    
    <?php if ($invoice['notes']): ?>
    <div class="notes-section">
        <h3>Notes</h3>
        <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>Cleanfinity Cleaning Services | contact@cleanfinity.com | (555) 123-4567</p>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    
    <?php else: ?>
   
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-sparkles"></i> Cleanfinity
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?php echo $_SESSION['first_name']; ?>!
                </span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="invoice-container">
        <div class="invoice-header">
            <h1><i class="fas fa-file-invoice"></i> INVOICE</h1>
            <p class="mb-0">Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
        </div>

        <div class="invoice-body">
            <div class="invoice-info">
                <div class="info-card">
                    <h5><i class="fas fa-info-circle"></i> Invoice Details</h5>
                    <div class="info-row">
                        <span class="label">Invoice Number:</span>
                        <span class="value"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Issue Date:</span>
                        <span class="value"><?php echo date('F j, Y', strtotime($invoice['issued_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Due Date:</span>
                        <span class="value"><?php echo date('F j, Y', strtotime($invoice['due_date'])); ?></span>
                    </div>
                    <?php if ($invoice['paid_date']): ?>
                    <div class="info-row">
                        <span class="label">Paid Date:</span>
                        <span class="value"><?php echo date('F j, Y', strtotime($invoice['paid_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="status-badge bg-<?php 
                            echo match($invoice['status']) {
                                'paid' => 'success',
                                'sent' => 'info',
                                'overdue' => 'danger',
                                'draft' => 'secondary',
                                'cancelled' => 'dark',
                                default => 'warning'
                            };
                        ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="info-card">
                    <h5><i class="fas fa-user"></i> Bill To</h5>
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value"><?php echo htmlspecialchars($invoice['customer_first_name'] . ' ' . $invoice['customer_last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($invoice['customer_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value"><?php echo htmlspecialchars($invoice['customer_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Address:</span>
                        <span class="value">
                            <?php echo htmlspecialchars($invoice['address']); ?><br>
                           
                        </span>
                    </div>
                </div>
            </div>

            <div class="service-details">
                <h5 class="mb-3"><i class="fas fa-broom text-primary"></i>Service Details</h5>
                <div class="info-row">
                    <span class="label">Service:</span>
                    <span class="value"><?php echo htmlspecialchars($invoice['service_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Service Date:</span>
                    <span class="value">
                        <?php echo date('F j, Y', strtotime($invoice['booking_date'])); ?> 
                        at <?php echo date('g:i A', strtotime($invoice['booking_time'])); ?>
                    </span>
                </div>
                <?php if ($invoice['staff_first_name']): ?>
                <div class="info-row">
                    <span class="label">Staff Assigned:</span>
                    <span class="value"><?php echo htmlspecialchars($invoice['staff_first_name'] . ' ' . $invoice['staff_last_name']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="label">Location:</span>
                    <span class="value"><?php echo htmlspecialchars($invoice['address']); ?></span>
                </div>
            </div>

            <div class="amount-breakdown">
                <h5 class="mb-3"><i class="fas fa-calculator"></i> Amount Breakdown</h5>
                <div class="amount-row">
                    <span>Service Amount:</span>
                    <span>$<?php echo number_format($invoice['amount'], 2); ?></span>
                </div>
                <div class="amount-row">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($invoice['tax_amount'], 2); ?></span>
                </div>
                <div class="amount-row total-row">
                    <span>TOTAL AMOUNT:</span>
                    <span>$<?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
            </div>

            <?php if ($invoice['notes']): ?>
            <div class="service-details mt-3">
                <h5 class="mb-2"><i class="fas fa-sticky-note"></i> Notes</h5>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="?id=<?php echo $invoice_id; ?>&download=pdf" target="_blank" class="btn btn-custom btn-download">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <?php if ($invoice['status'] !== 'paid'): ?>
                <a href="pay-invoice.php?booking_id=<?php echo $invoice['booking_id']; ?>" class="btn btn-custom btn-pay">
                    <i class="fas fa-credit-card"></i> Pay Now
                </a>
                <?php endif; ?>
                <a href="invoices.php" class="btn btn-custom btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>
</body>
</html>
