<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get total bookings count, completed services, pending payments, and average rating
$statsQuery = "
    SELECT 
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_services,
        SUM(CASE WHEN b.payment_status = 'unpaid' THEN b.total_price ELSE 0 END) as pending_payments,
        AVG(r.rating) as avg_rating
    FROM bookings b
    LEFT JOIN reviews r ON r.booking_id = b.id
    WHERE b.customer_id = :customer_id
";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(':customer_id', $_SESSION['user_id']);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);


// Get next upcoming booking
$upcomingQuery = "
    SELECT 
        b.*, 
        s.name as service_name, 
        s.image_url as service_icon,  -- corrected from s.icon
        u.first_name as staff_first_name, 
        u.last_name as staff_last_name
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN users u ON b.staff_id = u.id
    WHERE b.customer_id = :customer_id 
      AND b.booking_date >= CURDATE()
      AND b.status NOT IN ('completed', 'cancelled')
    ORDER BY b.booking_date ASC, b.booking_time ASC
    LIMIT 1
";

$upcomingStmt = $db->prepare($upcomingQuery);
$upcomingStmt->bindParam(':customer_id', $_SESSION['user_id']);
$upcomingStmt->execute();
$upcomingBooking = $upcomingStmt->fetch(PDO::FETCH_ASSOC);


// Get all bookings
$query = "SELECT b.*, s.name as service_name, s.image_url as service_icon,
          u.first_name as staff_first_name, u.last_name as staff_last_name
          FROM bookings b
          LEFT JOIN services s ON b.service_id = s.id
          LEFT JOIN users u ON b.staff_id = u.id
          WHERE b.customer_id = :customer_id
          ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt;

// Get recent invoices
$invoiceQuery = "
    SELECT 
        i.*, 
        b.booking_date, 
        s.name as service_name
    FROM invoices i
    LEFT JOIN bookings b ON i.booking_id = b.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.customer_id = :customer_id
    ORDER BY i.created_at DESC
    LIMIT 5
";

$invoiceStmt = $db->prepare($invoiceQuery);
$invoiceStmt->bindParam(':customer_id', $_SESSION['user_id']);
$invoiceStmt->execute();
$invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --dark-bg: #2c3e50;
            --sidebar-bg: #34495e;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Enhanced navigation with gradient background */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }
        
        .navbar-custom .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-custom .dropdown-toggle {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        
        .navbar-custom .dropdown-toggle:hover {
            background-color: rgba(255,255,255,0.2);
        }

        /* Fixed sidebar with dark modern background */
        .sidebar {
            position: fixed;
            top: 76px;
            left: 0;
            height: calc(100vh - 76px);
            width: 250px;
            background-color: var(--sidebar-bg);
            padding: 2rem 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 1rem 1.5rem;
            margin: 0.25rem 0;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--primary-green);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(67, 160, 71, 0.2);
            border-left-color: var(--primary-green);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        /* Main content area with left margin for fixed sidebar */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        /* Summary cards styling */
        .stat-card {
            border-radius: 10px;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .card-green { background: linear-gradient(135deg, #43A047 0%, #66BB6A 100%); }
        .card-blue { background: linear-gradient(135deg, #1E88E5 0%, #42A5F5 100%); }
        .card-orange { background: linear-gradient(135deg, #FB8C00 0%, #FFA726 100%); }
        .card-purple { background: linear-gradient(135deg, #8E24AA 0%, #AB47BC 100%); }

        /* Enhanced card styling */
        .content-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 2rem;
        }
        
        .content-card .card-header {
            background-color: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 1.25rem;
            font-weight: 600;
        }

        /* Upcoming booking highlight card */
        .upcoming-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Enhanced table styling */
        .table-custom {
            margin-bottom: 0;
        }
        
        .table-custom thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .table-custom tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table-custom tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .table-custom tbody tr:hover {
            background-color: #e9ecef;
        }

        /* Button styling */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, #66BB6A 100%);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 160, 71, 0.3);
        }

        /* Badge styling */
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                top: 0;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Enhanced navigation bar with gradient and user dropdown -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-sparkles"></i> Cleanfinity
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Fixed sidebar with dark background and icons -->
    <div class="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link" href="book-service.php">
                <i class="fas fa-calendar-plus"></i> Book Service
            </a>
            <a class="nav-link" href="invoices.php">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
        </nav>
    </div>

    <!-- Main content area with welcome message and summary cards -->
    <div class="main-content">
        <!-- Welcome Header -->
        <div class="mb-4">
            <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
            <p class="text-muted">Here's an overview of your cleaning services</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card card-green">
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                    <h3><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card card-blue">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <h3><?php echo $stats['completed_services'] ?? 0; ?></h3>
                    <p>Completed Services</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card card-orange">
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    <h3>$<?php echo number_format($stats['pending_payments'] ?? 0, 2); ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card card-purple">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <h3><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : 'N/A'; ?></h3>
                    <p>Average Rating</p>
                </div>
            </div>
        </div>

        <!-- Next Upcoming Booking Card -->
        <?php if ($upcomingBooking): ?>
        <div class="upcoming-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-2"><i class="fas fa-clock"></i> Next Upcoming Booking</h5>
                    <h4 class="mb-2"><?php echo htmlspecialchars($upcomingBooking['service_name']); ?></h4>
                    <p class="mb-0">
                        <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y', strtotime($upcomingBooking['booking_date'])); ?>
                        at <?php echo date('g:i A', strtotime($upcomingBooking['booking_time'])); ?>
                    </p>
                    <?php if ($upcomingBooking['staff_first_name']): ?>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-user"></i> Staff: <?php echo htmlspecialchars($upcomingBooking['staff_first_name'] . ' ' . $upcomingBooking['staff_last_name']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <div class="icon" style="font-size: 4rem; opacity: 0.3;">
                        <i class="fas fa-broom"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Bookings Table with enhanced styling -->
        <div class="card content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> My Bookings</h5>
                <a href="book-service.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Book New Service
                </a>
            </div>
            <div class="card-body p-0">
                <?php if ($bookings->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Price</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Staff</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $bookings->execute(); // Re-execute to fetch again
                                while ($row = $bookings->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-broom text-primary me-2"></i>
                                        <strong><?php echo htmlspecialchars($row['service_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($row['booking_date'])); ?><br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($row['booking_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($row['total_price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($row['payment_status']) {
                                                'paid' => 'success',
                                                'unpaid' => 'warning',
                                                'partially_paid' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <i class="fas fa-<?php 
                                                echo match($row['payment_status']) {
                                                    'paid' => 'check-circle',
                                                    'unpaid' => 'clock',
                                                    'partially_paid' => 'exclamation-circle',
                                                    default => 'question-circle'
                                                };
                                            ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $row['payment_status'])); ?>
                                        </span>
                                    </td>
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
                                        <?php if ($row['staff_first_name']): ?>
                                            <small><i class="fas fa-user-check text-success"></i> <?php echo htmlspecialchars($row['staff_first_name'] . ' ' . $row['staff_last_name']); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted"><i class="fas fa-user-times"></i> Not assigned</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Conditional action buttons based on status -->
                                        <?php if ($row['status'] === 'completed'): ?>
                                            <a href="rate-service.php?booking_id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-warning" title="Rate Service">
                                                <i class="fas fa-star"></i> Rate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['payment_status'] === 'unpaid'): ?>
                                            <a href="pay-invoice.php?booking_id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Pay Now">
                                                <i class="fas fa-credit-card"></i> Pay
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h5>No bookings yet</h5>
                        <p class="text-muted">Book your first cleaning service to get started!</p>
                        <a href="book-service.php" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Book Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Invoices Section -->
<div class="card content-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-invoice text-primary"></i> Recent Invoices</h5>
    </div>
    <div class="card-body p-0">
        <?php if (count($invoices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo htmlspecialchars($invoice['service_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></td>
                            <td><strong>$<?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($invoice['payment_status']) {
                                        'paid' => 'success',
                                        'unpaid' => 'warning',
                                        'partially_paid' => 'info',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $invoice['payment_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view-invoice.php?id=<?php echo $invoice['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View Invoice">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($invoice['payment_status'] === 'unpaid'): ?>
                                    <a href="pay-invoice.php?id=<?php echo $invoice['id']; ?>" 
                                       class="btn btn-sm btn-success" title="Pay Now">
                                        <i class="fas fa-credit-card"></i> Pay
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="invoices.php" class="btn btn-link">View All Invoices <i class="fas fa-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <p class="text-muted mb-0">No invoices available</p>
            </div>
        <?php endif; ?>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
