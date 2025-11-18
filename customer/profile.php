<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('customer');

$database = new Database();
$db = $database->getConnection();

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid request';
        header('Location: profile.php');
        exit();
    }

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $update_query = "UPDATE users 
                     SET first_name=:first_name, last_name=:last_name, 
                         email=:email, phone=:phone, address=:address 
                     WHERE id=:id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':first_name', $first_name);
    $update_stmt->bindParam(':last_name', $last_name);
    $update_stmt->bindParam(':email', $email);
    $update_stmt->bindParam(':phone', $phone);
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':id', $_SESSION['user_id']);

    if ($update_stmt->execute()) {
        $_SESSION['first_name'] = $first_name;
        $_SESSION['success'] = 'Profile updated successfully!';
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to update profile';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --dark-bg: #2C3E50;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
        
        /* Sidebar */
        .sidebar-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .sidebar-card .card-title {
            color: var(--dark-bg);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--primary-green);
        }
        
        .list-group-item {
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 0.5rem;
            border-radius: 10px !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .list-group-item:hover {
            background: linear-gradient(135deg, rgba(67,160,71,0.1) 0%, rgba(30,136,229,0.1) 100%);
            transform: translateX(5px);
            color: var(--primary-green);
        }
        
        .list-group-item.active {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(67,160,71,0.3);
        }
        
        /* Main Content */
        .main-content {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h2 {
            color: var(--dark-bg);
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        
        h2 i {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Profile Avatar */
        .profile-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-green));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(67,160,71,0.3);
            border: 5px solid white;
        }
        
        .profile-section h4 {
            color: var(--dark-bg);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-section p {
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Form Styling */
        .form-label {
            color: var(--dark-bg);
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .form-label i {
            color: var(--primary-green);
            margin-right: 0.5rem;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(67,160,71,0.15);
            transform: translateY(-2px);
        }
        
        .form-control:disabled {
            background: #f5f5f5;
            color: #999;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        small.text-muted {
            font-size: 0.875rem;
            color: #999;
        }
        
        /* Button */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67,160,71,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67,160,71,0.4);
        }
        
        /* Alerts */
        .alert {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }
        
        hr {
            border-top: 2px solid #e0e0e0;
            margin: 2rem 0;
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

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="card-title">Menu</h5>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="book-service.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-plus me-2"></i> Book Service
                            </a>
                            <a href="invoices.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-file-invoice me-2"></i> Invoices
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="main-content">
                    <h2><i class="fas fa-user"></i> My Profile</h2>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Avatar -->
                    <div class="profile-section text-center">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <!-- Profile Form -->
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-bold">
                                    <i class="fas fa-user text-primary"></i> First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-bold">
                                    <i class="fas fa-user text-primary"></i> Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope text-primary"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">
                                <i class="fas fa-phone text-primary"></i> Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="(123) 456-7890">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label fw-bold">
                                <i class="fas fa-map-marker-alt text-primary"></i> Address
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-key text-primary"></i> Username
                            </label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
