<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';
require_once '../models/Rating.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();

$staff_id = $_SESSION['user_id'];

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_bookings,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
FROM bookings 
WHERE employee_id = :staff_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':staff_id', $staff_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get average rating
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
FROM service_ratings r
JOIN bookings b ON r.booking_id = b.id
WHERE b.employee_id = :staff_id";

$rating_stmt = $db->prepare($rating_query);
$rating_stmt->bindParam(':staff_id', $staff_id);
$rating_stmt->execute();
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);

// Get upcoming bookings
$upcoming_query = "SELECT 
    b.id,
    b.booking_date,
    b.booking_time,
    b.status,
    b.address,
    b.total_price,
    b.payment_status,
    s.name as service_name,
    s.duration,
    u.first_name as customer_first_name,
    u.last_name as customer_last_name,
    u.phone as customer_phone,
    u.email as customer_email
FROM bookings b
JOIN services s ON b.service_id = s.id
JOIN users u ON b.customer_id = u.id
WHERE b.employee_id = :staff_id 
AND b.status IN ('pending', 'in_progress')
AND b.booking_date >= CURDATE()
ORDER BY b.booking_date ASC, b.booking_time ASC
LIMIT 10";

$upcoming_stmt = $db->prepare($upcoming_query);
$upcoming_stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
$upcoming_stmt->execute();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/logo.png">

    <title>Staff Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --staff-purple: #7E57C2;
            --staff-light: #9575cd;
            --staff-dark: #5e35b1;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 25px;
            height: 100%;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .purple-gradient {
            background: linear-gradient(135deg, var(--staff-purple), var(--staff-light));
        }
        
        .blue-gradient {
            background: linear-gradient(135deg, #1E88E5, #42A5F5);
        }
        
        .orange-gradient {
            background: linear-gradient(135deg, #FB8C00, #FFA726);
        }
        
        .green-gradient {
            background: linear-gradient(135deg, #43A047, #66BB6A);
        }
        
        .booking-card {
            border: none;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--staff-purple);
        }
        
        .booking-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateX(5px);
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
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
                        <p class="text-muted mb-0">Here's your work overview for today</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">
                            <i class="fas fa-calendar me-2"></i><?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon purple-gradient text-white">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['total_bookings']; ?></h3>
                            <p class="text-muted mb-0">Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon orange-gradient text-white">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['pending_bookings']; ?></h3>
                            <p class="text-muted mb-0">Pending Jobs</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon blue-gradient text-white">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['in_progress_bookings']; ?></h3>
                            <p class="text-muted mb-0">In Progress</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon green-gradient text-white">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold mb-1"><?php echo $stats['completed_bookings']; ?></h3>
                            <p class="text-muted mb-0">Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Rating Card -->
                <div class="row g-4 mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon purple-gradient text-white me-3">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold mb-1">
                                            <?php 
                                            $avg_rating = $rating_data['avg_rating'] ?? 0;
                                            echo number_format($avg_rating, 1); 
                                            ?>
                                            <span class="text-warning">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= round($avg_rating) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                        </h4>
                                        <p class="text-muted mb-0">
                                            Average Rating from <?php echo $rating_data['total_reviews'] ?? 0; ?> reviews
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Bookings -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-calendar-alt me-2 text-purple"></i>Upcoming Assignments
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($upcoming_stmt->rowCount() > 0): ?>
                            <?php while ($booking = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="booking-card p-3 m-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="fw-bold text-purple"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo $booking['duration']; ?> mins
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Date & Time</div>
                                            <div class="fw-semibold">
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small text-muted">Customer</div>
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($booking['customer_first_name'] . ' ' . $booking['customer_last_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <a href="bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-purple">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No upcoming bookings assigned</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
