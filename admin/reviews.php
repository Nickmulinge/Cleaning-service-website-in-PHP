<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Rating.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: reviews.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'respond') {
        $query = "UPDATE ratings SET admin_response = :response, responded_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':response', $_POST['response']);
        $stmt->bindParam(':id', $_POST['rating_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Response added successfully!';
        } else {
            $_SESSION['error'] = 'Failed to add response.';
        }
    } elseif ($action === 'delete') {
        $query = "DELETE FROM ratings WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['rating_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Review deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete review.';
        }
    }

    header('Location: reviews.php');
    exit();
}
// Get filter from query string
$rating_filter = $_GET['rating'] ?? null;

// Get all ratings (from service_ratings)
$query = "SELECT r.*, 
          u.first_name, u.last_name, u.email,
          s.name as service_name,
          b.booking_date
          FROM service_ratings r
          JOIN bookings b ON r.booking_id = b.id
          JOIN users u ON r.customer_id = u.id
          JOIN services s ON b.service_id = s.id";

if ($rating_filter) {
    $query .= " WHERE r.rating = :rating";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($query);
if ($rating_filter) {
    $stmt->bindParam(':rating', $rating_filter, PDO::PARAM_INT);
}
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_reviews = count($reviews);
$avg_rating = $total_reviews > 0 ? array_sum(array_column($reviews, 'rating')) / $total_reviews : 0;
$five_star = count(array_filter($reviews, fn($r) => $r['rating'] == 5));
$four_star = count(array_filter($reviews, fn($r) => $r['rating'] == 4));
$three_star = count(array_filter($reviews, fn($r) => $r['rating'] == 3));
$two_star = count(array_filter($reviews, fn($r) => $r['rating'] == 2));
$one_star = count(array_filter($reviews, fn($r) => $r['rating'] == 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - <?php echo SITE_NAME; ?></title>
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
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .review-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .rating-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
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
                    <h2 class="fw-bold mb-0">Manage Reviews</h2>
                    <div class="btn-group" role="group">
                        <a href="reviews.php" class="btn btn-sm <?php echo !$rating_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All
                        </a>
                        <a href="reviews.php?rating=5" class="btn btn-sm <?php echo $rating_filter == '5' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            5 Stars
                        </a>
                        <a href="reviews.php?rating=4" class="btn btn-sm <?php echo $rating_filter == '4' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            4 Stars
                        </a>
                        <a href="reviews.php?rating=3" class="btn btn-sm <?php echo $rating_filter == '3' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            3 Stars
                        </a>
                        <a href="reviews.php?rating=2" class="btn btn-sm <?php echo $rating_filter == '2' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            2 Stars
                        </a>
                        <a href="reviews.php?rating=1" class="btn btn-sm <?php echo $rating_filter == '1' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                            1 Star
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

                 Rating Statistics 
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card table-card">
                            <div class="card-body text-center">
                                <h1 class="display-3 fw-bold mb-0"><?php echo number_format($avg_rating, 1); ?></h1>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i > round($avg_rating) ? ' text-muted' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted mb-0">Based on <?php echo $total_reviews; ?> reviews</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card table-card">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Rating Distribution</h6>
                                <?php 
                                $ratings_data = [
                                    5 => $five_star,
                                    4 => $four_star,
                                    3 => $three_star,
                                    2 => $two_star,
                                    1 => $one_star
                                ];
                                foreach ($ratings_data as $star => $count): 
                                    $percentage = $total_reviews > 0 ? ($count / $total_reviews) * 100 : 0;
                                ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 60px;">
                                        <small><?php echo $star; ?> <i class="fas fa-star text-warning"></i></small>
                                    </div>
                                    <div class="flex-grow-1 mx-3">
                                        <div class="rating-bar">
                                            <div class="rating-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div style="width: 60px;" class="text-end">
                                        <small class="fw-semibold"><?php echo $count; ?> (<?php echo number_format($percentage, 0); ?>%)</small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                  
              <?php foreach ($reviews as $review): ?>
    <div class="card review-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-start">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: bold;">
                        <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($review['email']); ?></small>
                        <div class="rating-stars mt-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i > $review['rating'] ? ' text-muted' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                    <br>
                    <span class="badge bg-info"><?php echo htmlspecialchars($review['service_name']); ?></span>
                </div>
            </div>
            
            <?php if ($review['comment']): ?>
            <div class="mb-3">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteReview(<?php echo $review['id']; ?>)">
                    <i class="fas fa-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
<?php endforeach; ?>


                <?php if (count($reviews) === 0): ?>
                <div class="card table-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No reviews found</h5>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="respondModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Respond to Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="respond">
                    <input type="hidden" name="rating_id" id="respond_rating_id">
                    <div class="modal-body">
                        <p>Responding to review by <strong id="respond_customer_name"></strong></p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Your Response</label>
                            <textarea class="form-control" name="response" rows="4" required 
                                      placeholder="Thank you for your feedback..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Delete Review</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="rating_id" id="delete_rating_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete this review? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function respondToReview(ratingId, customerName) {
            document.getElementById('respond_rating_id').value = ratingId;
            document.getElementById('respond_customer_name').textContent = customerName;
            new bootstrap.Modal(document.getElementById('respondModal')).show();
        }

        function deleteReview(ratingId) {
            document.getElementById('delete_rating_id').value = ratingId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
