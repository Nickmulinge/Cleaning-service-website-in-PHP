<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('customer');

$booking_id = filter_var($_GET['booking_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$booking_id) {
    header('Location: dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get payment details
$query = "SELECT b.*, s.name as service_name, p.amount, p.transaction_id, p.payment_date
          FROM bookings b
          JOIN services s ON b.service_id = s.id
          LEFT JOIN payments p ON b.id = p.booking_id
          WHERE b.id = :booking_id AND b.customer_id = :customer_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':booking_id', $booking_id);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .success-container {
            max-width: 600px;
            width: 100%;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-green) 0%, #66BB6A 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: scaleIn 0.5s ease;
        }
        
        .success-icon i {
            font-size: 3rem;
            color: white;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        h2 {
            color: var(--primary-green);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .details-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67,160,71,0.4);
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h2>Payment Successful!</h2>
            <p class="text-muted mb-4">Your booking has been confirmed and paid</p>
            
            <div class="details-box">
                <div class="detail-row">
                    <span class="text-muted">Service:</span>
                    <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Date:</span>
                    <strong><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Time:</span>
                    <strong><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Amount Paid:</span>
                    <strong class="text-success">$<?php echo number_format($booking['amount'], 2); ?></strong>
                </div>
                <?php if ($booking['transaction_id']): ?>
                <div class="detail-row">
                    <span class="text-muted">Transaction ID:</span>
                    <small class="text-muted"><?php echo htmlspecialchars($booking['transaction_id']); ?></small>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                A confirmation email has been sent to your registered email address
            </div>
            
            <div class="d-grid gap-2">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
                <a href="book-service.php" class="btn btn-outline-secondary">
                    <i class="fas fa-calendar-plus me-2"></i>Book Another Service
                </a>
            </div>
        </div>
    </div>
</body>
</html>
