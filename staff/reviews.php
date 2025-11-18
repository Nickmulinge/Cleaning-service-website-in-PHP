<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();

$staff_id = $_SESSION['user_id'];

// Get reviews for this staff member
$query = "SELECT 
    r.id,
    r.rating,
    r.comment,
    r.created_at,
    b.booking_date,
    s.name as service_name,
    u.first_name as customer_first_name,
    u.last_name as customer_last_name
FROM service_ratings r
JOIN bookings b ON r.booking_id = b.id
JOIN services s ON b.service_id = s.id
JOIN users u ON b.customer_id = u.id
WHERE b.employee_id = :staff_id
ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':staff_id', $staff_id);
$stmt->execute();

// Calculate statistics
$stats_query = "SELECT 
    AVG(r.rating) as avg_rating,
    COUNT(*) as total_reviews,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star
FROM service_ratings r
JOIN bookings b ON r.booking_id = b.id
WHERE b.employee_id = :staff_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':staff_id', $staff_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --staff-purple: #7E57C2;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .rating-overview {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .rating-number {
            font-size: 4rem;
            font-weight: bold;
            color: var(--staff-purple);
        }
        
        .rating-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--staff-purple), #9575cd);
            transition: width 0.3s ease;
        }
        
        .review-card {
            background: white;
            border: none;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .star-rating {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="fw-bold mb-4">My Reviews</h2>
                
                <!-- Rating Overview -->
                <div class="rating-overview">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-end">
                            <div class="rating-number">
                                <?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>
                            </div>
                            <div class="star-rating mb-2">
                                <?php 
                                $avg = round($stats['avg_rating'] ?? 0);
                                for($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star<?php echo $i <= $avg ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="text-muted">Based on <?php echo $stats['total_reviews'] ?? 0; ?> reviews</div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2" style="width: 60px;">5 stars</span>
                                    <div class="rating-bar flex-grow-1">
                                        <div class="rating-bar-fill" style="width: <?php echo $stats['total_reviews'] > 0 ? ($stats['five_star'] / $stats['total_reviews'] * 100) : 0; ?>%"></div>
                                    </div>
                                    <span class="ms-2 text-muted" style="width: 40px;"><?php echo $stats['five_star'] ?? 0; ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2" style="width: 60px;">4 stars</span>
                                    <div class="rating-bar flex-grow-1">
                                        <div class="rating-bar-fill" style="width: <?php echo $stats['total_reviews'] > 0 ? ($stats['four_star'] / $stats['total_reviews'] * 100) : 0; ?>%"></div>
                                    </div>
                                    <span class="ms-2 text-muted" style="width: 40px;"><?php echo $stats['four_star'] ?? 0; ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2" style="width: 60px;">3 stars</span>
                                    <div class="rating-bar flex-grow-1">
                                        <div class="rating-bar-fill" style="width: <?php echo $stats['total_reviews'] > 0 ? ($stats['three_star'] / $stats['total_reviews'] * 100) : 0; ?>%"></div>
                                    </div>
                                    <span class="ms-2 text-muted" style="width: 40px;"><?php echo $stats['three_star'] ?? 0; ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2" style="width: 60px;">2 stars</span>
                                    <div class="rating-bar flex-grow-1">
                                        <div class="rating-bar-fill" style="width: <?php echo $stats['total_reviews'] > 0 ? ($stats['two_star'] / $stats['total_reviews'] * 100) : 0; ?>%"></div>
                                    </div>
                                    <span class="ms-2 text-muted" style="width: 40px;"><?php echo $stats['two_star'] ?? 0; ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <span class="me-2" style="width: 60px;">1 star</span>
                                    <div class="rating-bar flex-grow-1">
                                        <div class="rating-bar-fill" style="width: <?php echo $stats['total_reviews'] > 0 ? ($stats['one_star'] / $stats['total_reviews'] * 100) : 0; ?>%"></div>
                                    </div>
                                    <span class="ms-2 text-muted" style="width: 40px;"><?php echo $stats['one_star'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews List -->
                <h5 class="fw-bold mb-3">Customer Feedback</h5>
                
                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while ($review = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($review['customer_first_name'] . ' ' . $review['customer_last_name']); ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($review['service_name']); ?> - 
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="star-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($review['review'])): ?>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0 fst-italic">No written review provided</p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No reviews yet</h5>
                            <p class="text-muted">Complete bookings to start receiving customer reviews</p>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
