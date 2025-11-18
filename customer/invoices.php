<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Invoice.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

$invoices_query = "SELECT i.*, 
                          b.booking_date, b.booking_time, 
                          s.name as service_name
                   FROM invoices i
                   JOIN bookings b ON i.booking_id = b.id
                   JOIN services s ON b.service_id = s.id
                   WHERE b.customer_id = :customer_id
                   ORDER BY i.created_at DESC";

$stmt = $db->prepare($invoices_query);
$stmt->bindParam(':customer_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Invoices - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --dark-bg: #2C3E50;
            --light-bg: #F8F9FA;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navigation Styling */
        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .navbar-text {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        /* Sidebar Styling */
        .sidebar-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .sidebar-card .card-title {
            color: var(--dark-bg);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--primary-green);
        }
        
        .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
            border-radius: 10px !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .list-group-item:hover {
            background: linear-gradient(135deg, rgba(67,160,71,0.1) 0%, rgba(30,136,229,0.1) 100%);
            transform: translateX(5px);
            color: var(--primary-green);
        }
        
        .list-group-item.active {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(67,160,71,0.3);
        }
        
        .list-group-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styling */
        .main-content {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        
        h2 {
            color: var(--dark-bg);
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 2rem;
            letter-spacing: -0.5px;
        }
        
        h2 i {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Table Styling */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
        }
        
        .table thead th {
            border: none;
            padding: 1.2rem 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(67,160,71,0.05) 0%, rgba(30,136,229,0.05) 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        /* Badge Styling */
        .badge {
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }
        
        .bg-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
        }
        
        .bg-info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%) !important;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%) !important;
        }
        
        .bg-warning {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%) !important;
            color: white;
        }
        
        /* Button Styling */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-green);
            color: var(--primary-green);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67,160,71,0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76,175,80,0.4);
        }
        
        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            color: #ddd;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h5 {
            color: var(--dark-bg);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #999;
            font-size: 1rem;
        }
        
        /* Overdue Warning */
        .text-danger {
            color: #f44336 !important;
            font-weight: 600;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="card-title">Menu</h5>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="book-service.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-plus me-2"></i> Book Service
                            </a>
                            <a href="invoices.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-file-invoice me-2"></i> Invoices
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="main-content">
                    <h2><i class="fas fa-file-invoice"></i> My Invoices</h2>

                    <?php if ($stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-hashtag me-2"></i>Invoice #</th>
                                        <th><i class="fas fa-broom me-2"></i>Service</th>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th><i class="fas fa-dollar-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-calendar-alt me-2"></i>Due Date</th>
                                        <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                        <th><i class="fas fa-cog me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($row['issued_date'])); ?></td>
                                        <td><strong>$<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <?php 
                                            $due_date = strtotime($row['due_date']);
                                            $is_overdue = $due_date < time() && $row['status'] !== 'paid';
                                            ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger' : ''; ?>">
                                                <?php echo date('M j, Y', $due_date); ?>
                                                <?php if ($is_overdue): ?>
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($row['status']) {
                                                    'paid' => 'success',
                                                    'pending' => 'warning',
                                                    'overdue' => 'danger',
                                                    'cancelled' => 'secondary',
                                                    default => 'info'
                                                };
                                            ?>">
                                                <i class="fas fa-<?php 
                                                    echo match($row['status']) {
                                                        'paid' => 'check-circle',
                                                        'pending' => 'clock',
                                                        'overdue' => 'exclamation-triangle',
                                                        'cancelled' => 'times-circle',
                                                        default => 'info-circle'
                                                    };
                                                ?>"></i>
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view-invoice.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-outline-primary" title="View Invoice">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($row['status'] !== 'paid'): ?>
                                                    <a href="pay-invoice.php?booking_id=<?php echo $row['booking_id']; ?>" 
                                                       class="btn btn-success" title="Pay Now">
                                                        <i class="fas fa-credit-card"></i> Pay
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice fa-5x"></i>
                            <h5>No invoices yet</h5>
                            <p class="text-muted">Your invoices will appear here after booking services</p>
                            <a href="book-service.php" class="btn btn-success mt-3">
                                <i class="fas fa-calendar-plus me-2"></i>Book a Service
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
