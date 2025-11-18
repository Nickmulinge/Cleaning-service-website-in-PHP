<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('customer');

// Set response header
header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $payment_method_id = $input['payment_method_id'] ?? '';
    $booking_id = filter_var($input['booking_id'] ?? 0, FILTER_VALIDATE_INT);
    $amount = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
    $cardholder_name = $input['cardholder_name'] ?? '';
    
    if (!$payment_method_id || !$booking_id || !$amount) {
        throw new Exception('Missing required fields');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify booking belongs to customer and is unpaid
    $verify_query = "SELECT id, total_price, payment_status 
                    FROM bookings 
                    WHERE id = :booking_id AND customer_id = :customer_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(':booking_id', $booking_id);
    $verify_stmt->bindParam(':customer_id', $_SESSION['user_id']);
    $verify_stmt->execute();
    $booking = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    if ($booking['payment_status'] === 'paid') {
        throw new Exception('Booking already paid');
    }
    
    // Verify amount matches
    if (abs($booking['total_price'] - $amount) > 0.01) {
        throw new Exception('Amount mismatch');
    }
    
    // Initialize Stripe
    require_once '../vendor/autoload.php';
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');
    
    // Create payment intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => round($amount * 100), // Convert to cents
        'currency' => 'usd',
        'payment_method' => $payment_method_id,
        'confirm' => true,
        'description' => 'Cleanfinity Booking #' . $booking_id,
        'metadata' => [
            'booking_id' => $booking_id,
            'customer_id' => $_SESSION['user_id']
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never'
        ]
    ]);
    
    // Check payment status
    if ($paymentIntent->status === 'succeeded') {
        // Get last 4 digits of card
        $payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
        $card_last_four = $payment_method->card->last4 ?? null;
        
        // Save payment to database
        $payment_query = "INSERT INTO payments 
                         (booking_id, amount, payment_method, payment_status, transaction_id, card_last_four, payment_date) 
                         VALUES (:booking_id, :amount, 'card', 'completed', :transaction_id, :card_last_four, NOW())";
        $payment_stmt = $db->prepare($payment_query);
        $payment_stmt->bindParam(':booking_id', $booking_id);
        $payment_stmt->bindParam(':amount', $amount);
        $payment_stmt->bindParam(':transaction_id', $paymentIntent->id);
        $payment_stmt->bindParam(':card_last_four', $card_last_four);
        $payment_stmt->execute();
        
        // Update booking payment status
        $update_query = "UPDATE bookings SET payment_status = 'paid' WHERE id = :booking_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':booking_id', $booking_id);
        $update_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful'
        ]);
    } else {
        throw new Exception('Payment not completed');
    }
    
} catch (\Stripe\Exception\CardException $e) {
    // Card was declined
    echo json_encode([
        'success' => false,
        'error' => $e->getError()->message
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Stripe API error
    echo json_encode([
        'success' => false,
        'error' => 'Payment processing error. Please try again.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
