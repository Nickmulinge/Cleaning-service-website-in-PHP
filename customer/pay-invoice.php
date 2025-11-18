<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get booking details
$booking_id = filter_var($_GET['booking_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$booking_id) {
    $_SESSION['error'] = 'Invalid booking ID';
    header('Location: dashboard.php');
    exit();
}

// Fetch booking details
$query = "SELECT b.*, s.name as service_name, s.description as service_description
          FROM bookings b
          JOIN services s ON b.service_id = s.id
          WHERE b.id = :booking_id AND b.customer_id = :customer_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':booking_id', $booking_id);
$stmt->bindParam(':customer_id', $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    header('Location: dashboard.php');
    exit();
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    $_SESSION['error'] = 'This booking has already been paid';
    header('Location: dashboard.php');
    exit();
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
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-body {
            padding: 2.5rem;
        }
        
        .booking-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
            padding-top: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(67,160,71,0.15);
        }
        
        .card-element-container {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .card-element-container:focus-within {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(67,160,71,0.15);
        }
        
        .btn-pay {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67,160,71,0.4);
        }
        
        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .security-badge {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .security-badge i {
            color: var(--primary-green);
            margin-right: 0.5rem;
        }
        
        #card-errors {
            color: #dc3545;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h2 class="mb-2"><i class="fas fa-credit-card me-2"></i>Secure Payment</h2>
                <p class="mb-0">Complete your booking payment</p>
            </div>
            
            <div class="payment-body">
                 Booking Summary 
                <div class="booking-summary">
                    <h5 class="mb-3"><i class="fas fa-file-invoice me-2"></i>Booking Summary</h5>
                    <div class="summary-row">
                        <span>Service:</span>
                        <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Date:</span>
                        <strong><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Time:</span>
                        <strong><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Total Amount:</span>
                        <strong>$<?php echo number_format($booking['total_price'], 2); ?></strong>
                    </div>
                </div>

                 Payment Form 
                <form id="payment-form">
                    <div class="mb-3">
                        <label for="cardholder-name" class="form-label">
                            <i class="fas fa-user me-2"></i>Cardholder Name
                        </label>
                        <input type="text" class="form-control" id="cardholder-name" 
                               placeholder="John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-credit-card me-2"></i>Card Information
                        </label>
                        <div id="card-element" class="card-element-container">
                             Stripe Card Element will be inserted here 
                        </div>
                        <div id="card-errors" role="alert"></div>
                    </div>

                    <input type="hidden" id="booking-id" value="<?php echo $booking_id; ?>">
                    <input type="hidden" id="amount" value="<?php echo $booking['total_price']; ?>">

                    <button type="submit" id="submit-button" class="btn btn-pay">
                        <span id="button-text">
                            <i class="fas fa-lock me-2"></i>Pay $<?php echo number_format($booking['total_price'], 2); ?>
                        </span>
                        <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </span>
                    </button>
                </form>

                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    Your payment information is secure and encrypted
                </div>

                <div class="text-center mt-3">
                    <a href="dashboard.php" class="text-muted">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Stripe
        const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? ''; ?>');
        const elements = stripe.elements();
        
        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#dc3545'
                }
            }
        });
        
        cardElement.mount('#card-element');
        
        // Handle real-time validation errors
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        
        // Handle form submission
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            const cardholderName = document.getElementById('cardholder-name').value;
            
            // Validate cardholder name
            if (!cardholderName.trim()) {
                document.getElementById('card-errors').textContent = 'Please enter the cardholder name';
                return;
            }
            
            // Disable button and show spinner
            submitButton.disabled = true;
            buttonText.classList.add('d-none');
            spinner.classList.remove('d-none');
            
            try {
                // Create payment method
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: cardholderName
                    }
                });
                
                if (error) {
                    // Show error
                    document.getElementById('card-errors').textContent = error.message;
                    submitButton.disabled = false;
                    buttonText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                } else {
                    // Send payment method to server
                    const bookingId = document.getElementById('booking-id').value;
                    const amount = document.getElementById('amount').value;
                    
                    const response = await fetch('process-payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            payment_method_id: paymentMethod.id,
                            booking_id: bookingId,
                            amount: amount,
                            cardholder_name: cardholderName
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Redirect to success page
                        window.location.href = 'payment-success.php?booking_id=' + bookingId;
                    } else {
                        // Show error
                        document.getElementById('card-errors').textContent = result.error || 'Payment failed. Please try again.';
                        submitButton.disabled = false;
                        buttonText.classList.remove('d-none');
                        spinner.classList.add('d-none');
                    }
                }
            } catch (err) {
                document.getElementById('card-errors').textContent = 'An unexpected error occurred. Please try again.';
                submitButton.disabled = false;
                buttonText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        });
    </script>
</body>
</html>
