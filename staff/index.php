<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// If already logged in as staff, redirect to staff dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'staff') {
    header('Location: dashboard.php');
    exit();
}

$auth = new AuthController();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth->staffLogin();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --staff-purple: #7E57C2;
        }
        
        body {
            background: linear-gradient(135deg, #5e35b1 0%, #7e57c2 100%);
            background-image: url('https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1200&h=800&fit=crop&auto=format');
            background-blend-mode: overlay;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .staff-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            border-top: 5px solid var(--staff-purple);
        }
        
        .staff-badge {
            background: linear-gradient(45deg, var(--staff-purple), #9575cd);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .btn-staff {
            background: linear-gradient(45deg, var(--staff-purple), #9575cd);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-staff:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(126, 87, 194, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5 col-xl-4">
                <div class="card staff-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="staff-badge">
                                <i class="fas fa-user-tie me-2"></i>STAFF ACCESS
                            </div>
                            <h2 class="fw-bold fs-1 mb-2" style="color: var(--primary-blue);">
                                <i class="fas fa-sparkles"></i> Cleanfinity
                            </h2>
                            <p class="text-muted">Staff Portal Login</p>
                        </div>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="staff_login" value="1">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="fas fa-user me-2 text-muted"></i>Staff Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-muted"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-staff text-white w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Access Staff Portal
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="../login.php" class="text-muted text-decoration-none">
                                <i class="fas fa-user me-2"></i>Customer Login
                            </a>
                            <br>
                            <a href="../index.php" class="text-muted text-decoration-none mt-2 d-inline-block">
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
