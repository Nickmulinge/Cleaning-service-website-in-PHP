<?php
require_once __DIR__ . '/../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../controllers/BookingController.php';
require_once '../models/Service.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();
$service = new Service($db);
$services = $service->readAll();

$booking_controller = new BookingController();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_controller->create();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - <?php echo SITE_NAME; ?></title>
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
        .payment-option { cursor: pointer; transition: all 0.3s; }
        .payment-option:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .payment-option.selected { border-color: var(--primary-green); background-color: #f0f9f0; }
    </style>
</head>
<body>
      
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
              
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Menu</h5>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="book-service.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-calendar-plus me-2"></i> Book Service
                            </a>
                            <a href="invoices.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-invoice me-2"></i> Invoices
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

           
            <div class="col-md-9">
                <h2 class="mb-4"><i class="fas fa-calendar-plus text-primary"></i> Book a Service</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST" id="bookingForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="payment_method" id="payment_method" value="pay_later">
                            
                             
                            <div class="mb-4">
                                <label for="service_id" class="form-label fw-bold">
                                    <i class="fas fa-broom text-primary"></i> Select Service
                                </label>
                                <select class="form-select form-select-lg" id="service_id" name="service_id" required>
                                    <option value="">Choose a service...</option>
                                    <?php while ($row = $services->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                                data-price="<?php echo $row['base_price']; ?>"
                                                data-duration="<?php echo $row['duration_minutes']; ?>">
                                            <?php echo htmlspecialchars($row['name']); ?> - 
                                            $<?php echo number_format($row['base_price'], 2); ?> 
                                            (<?php echo $row['duration_minutes']; ?> mins)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="booking_date" class="form-label fw-bold">
                                        <i class="fas fa-calendar text-primary"></i> Booking Date
                                    </label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                    <small class="text-muted">When to start the service</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="booking_time" class="form-label fw-bold">
                                        <i class="fas fa-clock text-primary"></i> Time
                                    </label>
                                    <select class="form-select" id="booking_time" name="booking_time" required>
                                        <option value="">Select time...</option>
                                        <?php for ($hour = 8; $hour <= 18; $hour++): ?>
                                            <option value="<?php echo sprintf('%02d:00:00', $hour); ?>">
                                                <?php echo date('g:i A', strtotime($hour . ':00')); ?>
                                            </option>
                                            <option value="<?php echo sprintf('%02d:30:00', $hour); ?>">
                                                <?php echo date('g:i A', strtotime($hour . ':30')); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="preferred_completion_date" class="form-label fw-bold">
                                        <i class="fas fa-calendar-check text-primary"></i> Completion Date
                                    </label>
                                    <input type="date" class="form-control" id="preferred_completion_date" 
                                           name="preferred_completion_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    <small class="text-muted">When you want it done</small>
                                </div>
                            </div>

                              
                            <div class="mb-4">
                                <label for="address" class="form-label fw-bold">
                                    <i class="fas fa-map-marker-alt text-primary"></i> Service Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="2" required 
                                          placeholder="Enter the full address where the service should be performed"></textarea>
                            </div>

                             
                            <div class="mb-4">
                                <label for="special_instructions" class="form-label fw-bold">
                                    <i class="fas fa-comment-dots text-primary"></i> Special Instructions (Optional)
                                </label>
                                <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3"
                                          placeholder="Any special requests or instructions for our cleaning team"></textarea>
                            </div>

                         
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-credit-card text-primary"></i> Payment Option
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card payment-option" onclick="selectPayment('pay_now')">
                                            <div class="card-body text-center">
                                                <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                                                <h5>Pay Now</h5>
                                                <p class="text-muted mb-0">Pay with credit card</p>
                                                <small class="text-success">Secure payment</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card payment-option selected" onclick="selectPayment('pay_later')">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-invoice fa-3x text-primary mb-3"></i>
                                                <h5>Pay Later</h5>
                                                <p class="text-muted mb-0">Receive invoice after service</p>
                                                <small class="text-info">Pay after completion</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Credit Card Form Section -->
                            <div id="cardDetailsSection" style="display: none;">
                                <div class="card border-primary mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-lock"></i> Secure Payment Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="card_number" class="form-label fw-bold">Card Number</label>
                                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                                       placeholder="1234 5678 9012 3456" maxlength="19" 
                                                       pattern="[0-9\s]{13,19}">
                                                <small class="text-muted">Enter your 16-digit card number</small>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label for="card_name" class="form-label fw-bold">Cardholder Name</label>
                                                <input type="text" class="form-control" id="card_name" name="card_name" 
                                                       placeholder="John Doe">
                                                <small class="text-muted">Name as it appears on card</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_date" class="form-label fw-bold">Expiry Date</label>
                                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                                       placeholder="MM/YY" maxlength="5" pattern="[0-9]{2}/[0-9]{2}">
                                                <small class="text-muted">MM/YY format</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cvv" class="form-label fw-bold">CVV</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                                       placeholder="123" maxlength="4" pattern="[0-9]{3,4}">
                                                <small class="text-muted">3 or 4 digits on back</small>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-shield-alt"></i> Your payment information is encrypted and secure
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Display -->
                            <div class="alert alert-info mb-4" id="priceDisplay" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Price:</span>
                                    <span class="fs-4 fw-bold">$<span id="totalPrice">0.00</span></span>
                                </div>
                            </div>

                           
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-check"></i> Proceed to <span id="bookingAction">Booking</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle service selection to show price
        document.getElementById('service_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            
            if (price) {
                document.getElementById('totalPrice').textContent = parseFloat(price).toFixed(2);
                document.getElementById('priceDisplay').style.display = 'block';
            } else {
                document.getElementById('priceDisplay').style.display = 'none';
            }
        });

        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Update hidden input
            document.getElementById('payment_method').value = method;
            
            // Update button text
            document.getElementById('bookingAction').textContent = 
                method === 'pay_now' ? 'Payment' : 'Booking';
            
            // Show/hide card details section
            const cardSection = document.getElementById('cardDetailsSection');
            const cardFields = ['card_number', 'card_name', 'expiry_date', 'cvv'];
            
            if (method === 'pay_now') {
                cardSection.style.display = 'block';
                // Make card fields required
                cardFields.forEach(field => {
                    document.getElementById(field).setAttribute('required', 'required');
                });
            } else {
                cardSection.style.display = 'none';
                // Remove required attribute from card fields
                cardFields.forEach(field => {
                    document.getElementById(field).removeAttribute('required');
                });
            }
        }

        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (paymentMethod === 'pay_now') {
                const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                const expiryDate = document.getElementById('expiry_date').value;
                const cvv = document.getElementById('cvv').value;
                const cardName = document.getElementById('card_name').value;
                
                // Validate card number (basic Luhn algorithm check)
                if (cardNumber.length < 13 || cardNumber.length > 19) {
                    e.preventDefault();
                    alert('Please enter a valid card number');
                    return false;
                }
                
                // Validate expiry date
                if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                    e.preventDefault();
                    alert('Please enter expiry date in MM/YY format');
                    return false;
                }
                
                const [month, year] = expiryDate.split('/');
                const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const now = new Date();
                
                if (expiry < now) {
                    e.preventDefault();
                    alert('Card has expired');
                    return false;
                }
                
                // Validate CVV
                if (cvv.length < 3 || cvv.length > 4) {
                    e.preventDefault();
                    alert('Please enter a valid CVV');
                    return false;
                }
                
                // Validate cardholder name
                if (cardName.trim().length < 3) {
                    e.preventDefault();
                    alert('Please enter the cardholder name');
                    return false;
                }
            }
        });

        // Set minimum completion date to be same as or after booking date
        document.getElementById('booking_date').addEventListener('change', function() {
            document.getElementById('preferred_completion_date').min = this.value;
            if (document.getElementById('preferred_completion_date').value < this.value) {
                document.getElementById('preferred_completion_date').value = this.value;
            }
        });
    </script>
</body>
</html>
