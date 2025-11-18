<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';
require_once '../models/User.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$stats = [
    'total_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'pending_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch()['count'],
    'completed_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")->fetch()['count'],
    'total_customers' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch()['count'],
    'total_employees' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch()['count'],
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) as revenue FROM payments WHERE payment_status = 'completed'")->fetch()['revenue'],
    'pending_payments' => $db->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'")->fetch()['count'],
    'total_ratings' => $db->query("SELECT COUNT(*) as count FROM service_ratings")->fetch()['count'],
    'avg_rating' => $db->query("SELECT COALESCE(AVG(rating), 0) as avg FROM service_ratings")->fetch()['avg']
];

// Recent bookings
$recent_bookings_query = "
    SELECT b.*, 
           u.first_name AS customer_first_name, 
           u.last_name AS customer_last_name, 
           s.name AS service_name, 
           s.price,
           COALESCE(p.payment_status, 'unpaid') AS payment_status
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN services s ON b.service_id = s.id
    LEFT JOIN payments p ON b.id = p.booking_id
    ORDER BY b.created_at DESC 
    LIMIT 10
";
$recent_bookings = $db->query($recent_bookings_query);


// Recent ratings
$recent_ratings_query = "
    SELECT r.*, 
           u.first_name, 
           u.last_name, 
           s.name AS service_name
    FROM service_ratings r
    JOIN users u ON r.customer_id = u.id
    JOIN bookings b ON r.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    ORDER BY r.created_at DESC 
    LIMIT 5
";
$recent_ratings = $db->query($recent_ratings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
        }
        
        .sidebar .list-group-item:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary-blue);
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .badge-payment-paid {
            background-color: #28a745;
        }
        
        .badge-payment-unpaid {
            background-color: #dc3545;
        }
        
        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>
<body>
     
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-sparkles me-2"></i>Cleanfinity Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-2 p-0 sidebar">
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="services.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-broom me-2"></i>Services
                    </a>
                    <a href="bookings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i>Bookings
                    </a>
                    <a href="employees.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-tie me-2"></i>Employees
                    </a>
                    <a href="customers.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                    <a href="payments.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-credit-card me-2"></i>Payments & Invoices
                    </a>
                    <a href="reviews.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-star me-2"></i>Reviews
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </div>
            </div>

              
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">Dashboard Overview</h2>
                    <div class="text-muted">
                        <i class="fas fa-calendar me-2"></i><?php echo date('l, F j, Y'); ?>
                    </div>
                </div>

                 
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Total Bookings</p>
                                        <h3 class="fw-bold mb-0"><?php echo $stats['total_bookings']; ?></h3>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i><?php echo $stats['completed_bookings']; ?> completed
                                        </small>
                                    </div>
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Pending Bookings</p>
                                        <h3 class="fw-bold mb-0"><?php echo $stats['pending_bookings']; ?></h3>
                                        <small class="text-warning">
                                            <i class="fas fa-clock me-1"></i>Needs attention
                                        </small>
                                    </div>
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Total Revenue</p>
                                        <h3 class="fw-bold mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i><?php echo $stats['pending_payments']; ?> pending
                                        </small>
                                    </div>
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-muted mb-1 small">Customers</p>
                                        <h3 class="fw-bold mb-0"><?php echo $stats['total_customers']; ?></h3>
                                        <small class="text-info">
                                            <i class="fas fa-user-tie me-1"></i><?php echo $stats['total_employees']; ?> employees
                                        </small>
                                    </div>
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card table-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 fw-bold">Average Rating</h5>
                                    <div class="rating-stars">
                                        <?php 
                                        $avg_rating = round($stats['avg_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++): 
                                            if ($i <= floor($avg_rating)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif;
                                        endfor; ?>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0"><?php echo number_format($avg_rating, 1); ?> / 5.0</h2>
                                <p class="text-muted mb-0">Based on <?php echo $stats['total_ratings']; ?> reviews</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card table-card">
                            <div class="card-body">
                                <h5 class="mb-3 fw-bold">Quick Actions</h5>
                                <div class="d-grid gap-2">
                                    <a href="bookings.php?status=pending" class="btn btn-outline-warning">
                                        <i class="fas fa-clock me-2"></i>View Pending Bookings (<?php echo $stats['pending_bookings']; ?>)
                                    </a>
                                    <a href="payments.php?status=pending" class="btn btn-outline-danger">
                                        <i class="fas fa-file-invoice-dollar me-2"></i>Pending Invoices (<?php echo $stats['pending_payments']; ?>)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            
                <div class="card table-card mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Recent Bookings</h5>
                            <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $recent_bookings->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td class="fw-semibold">#<?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['customer_first_name'] . ' ' . $row['customer_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                        <td>
                                            <small><?php echo date('M j, Y', strtotime($row['booking_date'])); ?></small><br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($row['booking_time'])); ?></small>
                                        </td>
                                        <td class="fw-semibold">$<?php echo number_format($row['total_price'], 2); ?></td>
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
                                            <span class="badge badge-payment-<?php echo $row['payment_status'] === 'completed' ? 'paid' : 'unpaid'; ?>">
                                                <?php echo $row['payment_status'] === 'completed' ? 'Paid' : 'Unpaid'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="booking-details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                 
                <div class="card table-card">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Recent Reviews</h5>
                            <a href="reviews.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php while ($review = $recent_ratings->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <?php echo strtoupper(substr($review['first_name'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($review['service_name']); ?></small>
                                    </div>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i > $review['rating'] ? ' text-muted' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if ($review['comment']): ?>
                                <p class="mb-0 mt-2 small"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
