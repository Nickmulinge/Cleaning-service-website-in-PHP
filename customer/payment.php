<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/Booking.php';
require_once '../models/Payment.php';

AuthController::requireRole('customer');

// Get booking ID from session (set during booking creation)
if (!isset($_SESSION['pending_booking_id'])) {
    $_SESSION['error'] = 'No pending booking found';
    header('Location: dashboard.php');
    exit();
}

$booking_id = $_SESSION['pending_booking_id'];

$database = new Database();
$db = $database->getConnection();

// Get booking details
$booking = new Booking($db);
$query = "SELECT b.*, s.name as service_name, s.base_price 
          FROM bookings b 
          INNER JOIN services s ON b.service_id = s.id 
          WHERE b.id = :id AND b.customer_id = :customer_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $booking_id);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$booking_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking_data) {
    $_SESSION['error'] = 'Booking not found';
    header('Location: dashboard.php');
    exit();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: payment.php');
        exit();
    }

    $payment = new Payment($db);
    $payment->booking_id = $booking_id;
    $payment->amount = $booking_data['total_price'];
    $payment->payment_method = 'credit_card';

    // Process credit card payment
    $result = $payment->processCreditCard(
        $_POST['card_number'],
        $_POST['cvv'],
        $_POST['expiry'],
        $booking_data['total_price']
    );

    if ($result['success']) {
        // Save payment record
        if ($payment->create()) {
            // Update booking payment status
            $update_query = "UPDATE bookings SET payment_status='paid' WHERE id=:id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $booking_id);
            $update_stmt->execute();

            // Clear pending booking
            unset($_SESSION['pending_booking_id']);

            $_SESSION['success'] = 'Payment successful! Your booking is confirmed.';
            header('Location: dashboard.php');
            exit();
        }
    } else {
        $_SESSION['error'] = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        .btn-primary { background-color: var(--primary-green); border-color: var(--primary-green); }
        .btn-primary:hover { background-color: #388E3C; border-color: #388E3C; }
        .text-primary { color: var(--primary-green) !important; }
        .bg-primary { background-color: var(--primary-green) !important; }
        .credit-card { 
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
     Navigation 
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
                <h2 class="mb-4 text-center"><i class="fas fa-credit-card text-primary"></i> Complete Payment</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                 Booking Summary 
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Service:</strong> <?php echo htmlspecialchars($booking_data['service_name']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($booking_data['booking_date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking_data['booking_time'])); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <h3 class="text-primary">$<?php echo number_format($booking_data['total_price'], 2); ?></h3>
                                <p class="text-muted">Total Amount</p>
                            </div>
                        </div>
                    </div>
                </div>

                 Payment Form 
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" id="paymentForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-4">
                                <label for="card_number" class="form-label fw-bold">
                                    <i class="fas fa-credit-card"></i> Card Number
                                </label>
                                <input type="text" class="form-control form-control-lg" id="card_number" 
                                       name="card_number" placeholder="1234 5678 9012 3456" 
                                       maxlength="19" required>
                                <small class="text-muted">Test card: 4532015112830366</small>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="expiry" class="form-label fw-bold">
                                        <i class="fas fa-calendar"></i> Expiry Date
                                    </label>
                                    <input type="text" class="form-control" id="expiry" name="expiry" 
                                           placeholder="MM/YY" maxlength="5" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="cvv" class="form-label fw-bold">
                                        <i class="fas fa-lock"></i> CVV
                                    </label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           placeholder="123" maxlength="4" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="card_name" class="form-label fw-bold">
                                    <i class="fas fa-user"></i> Cardholder Name
                                </label>
                                <input type="text" class="form-control" id="card_name" name="card_name" 
                                       placeholder="John Doe" required>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-shield-alt"></i> Your payment information is secure and encrypted
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock"></i> Pay $<?php echo number_format($booking_data['total_price'], 2); ?>
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
        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        // Only allow numbers in CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>
