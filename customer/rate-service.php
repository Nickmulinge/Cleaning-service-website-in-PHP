<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';
require_once '../models/Rating.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get booking ID
if (!isset($_GET['booking_id'])) {
    $_SESSION['error'] = 'Invalid booking';
    header('Location: dashboard.php');
    exit();
}

$booking_id = $_GET['booking_id'];

// Verify booking belongs to customer and is completed
$query = "SELECT b.*, s.name as service_name 
          FROM bookings b 
          INNER JOIN services s ON b.service_id = s.id 
          WHERE b.id = :id AND b.customer_id = :customer_id AND b.status = 'completed'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $booking_id);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or not eligible for rating';
    header('Location: dashboard.php');
    exit();
}

// Check if already rated
$rating = new Rating($db);
if ($rating->hasRating($booking_id)) {
    $_SESSION['error'] = 'You have already rated this service';
    header('Location: dashboard.php');
    exit();
}

// Process rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: rate-service.php?booking_id=' . $booking_id);
        exit();
    }

    $rating->booking_id = $booking_id;
    $rating->customer_id = $_SESSION['user_id'];
    $rating->rating = $_POST['rating'];
    $rating->comment = $_POST['comment'];

    if ($rating->create()) {
        $_SESSION['success'] = 'Thank you for your feedback!';
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to submit rating';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Service - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --dark-bg: #2C3E50;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navigation */
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
        
        /* Card Styling */
        .rating-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%) !important;
            padding: 1.5rem;
            border: none;
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .card-body {
            padding: 2.5rem;
            background: white;
        }
        
        /* Service Info Alert */
        .alert-info {
            background: linear-gradient(135deg, rgba(30,136,229,0.1) 0%, rgba(67,160,71,0.1) 100%);
            border: 2px solid var(--primary-blue);
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .alert-info h5 {
            color: var(--dark-bg);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .alert-info p {
            color: #555;
            margin: 0;
            font-size: 1rem;
        }
        
        /* Star Rating */
        .star-rating-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }
        
        .star-rating {
            font-size: 3.5rem;
            cursor: pointer;
            display: inline-flex;
            gap: 0.5rem;
        }
        
        .star-rating i {
            color: #ddd;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .star-rating i.active,
        .star-rating i:hover {
            color: #ffc107;
            transform: scale(1.2) rotate(10deg);
            filter: drop-shadow(0 4px 8px rgba(255,193,7,0.5));
        }
        
        #ratingText {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 1rem;
            min-height: 2rem;
        }
        
        /* Form Elements */
        .form-label {
            color: var(--dark-bg);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .form-label i {
            color: var(--primary-green);
            margin-right: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(67,160,71,0.15);
            transform: translateY(-2px);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        /* Buttons */
        .btn {
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            box-shadow: 0 4px 15px rgba(67,160,71,0.3);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67,160,71,0.4);
        }
        
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
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

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card rating-card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-star"></i> Rate Your Service</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Service Details -->
                        <div class="alert alert-info mb-4">
                            <h5><i class="fas fa-broom"></i> <?php echo htmlspecialchars($booking['service_name']); ?></h5>
                            <p class="mb-0">
                                <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at 
                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                            </p>
                        </div>

                        <form method="POST" id="ratingForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="rating" id="ratingValue" value="0">

                            <!-- Star Rating -->
                            <div class="star-rating-container text-center">
                                <label class="form-label d-block mb-3">How would you rate this service?</label>
                                <div class="star-rating" id="starRating">
                                    <i class="fas fa-star" data-rating="1"></i>
                                    <i class="fas fa-star" data-rating="2"></i>
                                    <i class="fas fa-star" data-rating="3"></i>
                                    <i class="fas fa-star" data-rating="4"></i>
                                    <i class="fas fa-star" data-rating="5"></i>
                                </div>
                                <div id="ratingText" class="text-muted"></div>
                            </div>

                            <!-- Comment -->
                            <div class="mb-4">
                                <label for="comment" class="form-label fw-bold">
                                    <i class="fas fa-comment"></i> Your Feedback (Optional)
                                </label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" 
                                          placeholder="Tell us about your experience..."></textarea>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                    <i class="fas fa-paper-plane"></i> Submit Rating
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const stars = document.querySelectorAll('.star-rating i');
        const ratingValue = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');
        const submitBtn = document.getElementById('submitBtn');
        
        const ratingLabels = {
            1: 'Poor',
            2: 'Fair',
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;
                
                // Update star display
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Update text
                ratingText.textContent = ratingLabels[rating];
                ratingText.className = 'mt-2 fw-bold text-warning';
                
                // Enable submit button
                submitBtn.disabled = false;
            });

            // Hover effect
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        // Reset on mouse leave
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = ratingValue.value;
            stars.forEach(s => {
                if (s.getAttribute('data-rating') <= currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    </script>
</body>
</html>
