<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get current admin info
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $admin_id);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: settings.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $query = "UPDATE users SET first_name=:first_name, last_name=:last_name, 
                  email=:email, phone=:phone WHERE id=:id";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':first_name', $_POST['first_name']);
        $stmt->bindParam(':last_name', $_POST['last_name']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':id', $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['first_name'] = $_POST['first_name'];
            $_SESSION['success'] = 'Profile updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update profile.';
        }
    } elseif ($action === 'change_password') {
        // Verify current password
        if (password_verify($_POST['current_password'], $admin['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $query = "UPDATE users SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $new_password);
                $stmt->bindParam(':id', $admin_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Password changed successfully!';
                } else {
                    $_SESSION['error'] = 'Failed to change password.';
                }
            } else {
                $_SESSION['error'] = 'New passwords do not match.';
            }
        } else {
            $_SESSION['error'] = 'Current password is incorrect.';
        }
    }

    header('Location: settings.php');
    exit();
}

// Get system statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'total_bookings' => $db->query("SELECT COUNT(*) as count FROM bookings")->fetch()['count'],
    'total_services' => $db->query("SELECT COUNT(*) as count FROM services")->fetch()['count'],
    'total_revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) as revenue 
                                   FROM payments 
                                   WHERE payment_status = 'completed'")->fetch()['revenue']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
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
        
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <h2 class="fw-bold mb-4">Settings</h2>

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

                <div class="row g-4">
                    
                    <div class="col-md-6">
                        <div class="card settings-card">
                            <div class="card-header bg-white border-0 pt-4">
                                <h5 class="fw-bold mb-0"><i class="fas fa-user-circle me-2 text-primary"></i>Profile Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">First Name</label>
                                            <input type="text" class="form-control" name="first_name" 
                                                   value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" 
                                                   value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Phone</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card settings-card">
                            <div class="card-header bg-white border-0 pt-4">
                                <h5 class="fw-bold mb-0"><i class="fas fa-lock me-2 text-warning"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required minlength="6">
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-12">
                        <div class="card settings-card">
                            <div class="card-header bg-white border-0 pt-4">
                                <h5 class="fw-bold mb-0"><i class="fas fa-info-circle me-2 text-info"></i>System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h3 class="fw-bold text-primary mb-0"><?php echo $stats['total_users']; ?></h3>
                                            <small class="text-muted">Total Users</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h3 class="fw-bold text-success mb-0"><?php echo $stats['total_bookings']; ?></h3>
                                            <small class="text-muted">Total Bookings</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h3 class="fw-bold text-info mb-0"><?php echo $stats['total_services']; ?></h3>
                                            <small class="text-muted">Total Services</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h3 class="fw-bold text-warning mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                            <small class="text-muted">Total Revenue</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3">Application Details</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Application Name:</th>
                                                <td><?php echo SITE_NAME; ?></td>
                                            </tr>
                                            <tr>
                                                <th>Version:</th>
                                                <td>1.0.0</td>
                                            </tr>
                                            <tr>
                                                <th>PHP Version:</th>
                                                <td><?php echo phpversion(); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3">Admin Account</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Account Type:</th>
                                                <td><span class="badge bg-danger">Administrator</span></td>
                                            </tr>
                                            <tr>
                                                <th>Account Created:</th>
                                                <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status:</th>
                                                <td><span class="badge bg-success">Active</span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
