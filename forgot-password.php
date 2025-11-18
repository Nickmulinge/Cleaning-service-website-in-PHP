<?php
require_once 'config/config.php';
require_once 'models/PasswordReset.php';
require_once 'utils/EmailService.php';

$database = new Database();
$db = $database->getConnection();
$passwordReset = new PasswordReset($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if user exists
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $token = $passwordReset->createResetToken($user['id']);
            
            if ($token) {
                // Send reset email
                $emailService = new EmailService();
                $resetLink = SITE_URL . "/reset-password.php?token=" . $token;
                
                $subject = "Password Reset Request - " . SITE_NAME;
                $message = "
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['username']},</p>
                    <p>You requested a password reset. Click the link below to reset your password:</p>
                    <p><a href='{$resetLink}' style='background: #43A047; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";
                
                if ($emailService->sendEmail($email, $subject, $message)) {
                    $_SESSION['success'] = "Password reset instructions have been sent to your email.";
                } else {
                    $_SESSION['error'] = "Failed to send reset email. Please try again.";
                }
            } else {
                $_SESSION['error'] = "Failed to generate reset token. Please try again.";
            }
        } else {
            // Don't reveal if email exists or not for security
            $_SESSION['success'] = "If an account with that email exists, password reset instructions have been sent.";
        }
    } else {
        $_SESSION['error'] = "Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            background-image: url('https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?w=1200&h=800&fit=crop&auto=format');
            background-blend-mode: overlay;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="card reset-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-key fa-3x" style="color: var(--primary-green);"></i>
                            </div>
                            <h2 class="fw-bold mb-2" style="color: var(--primary-blue);">Forgot Password?</h2>
                            <p class="text-muted">Enter your email address and we'll send you instructions to reset your password.</p>
                        </div>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-muted"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-2">
                                Remember your password? 
                                <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--primary-green);">
                                    Sign in here
                                </a>
                            </p>
                            <a href="index.php" class="text-muted text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
