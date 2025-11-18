<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/User.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle employee operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: employees.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            // Create new employee account
            // Validate required fields
            if (empty($_POST['username']) || empty($_POST['password'])) {
                $_SESSION['error'] = 'Username and password are required.';
                header("Location: employees.php");
                exit();
            }

            // Check if username already exists
            $check = $db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $check->bindParam(':username', $_POST['username']);
            $check->execute();

            if ($check->rowCount() > 0) {
                $_SESSION['error'] = 'Username already exists. Please choose another.';
                header("Location: employees.php");
                exit();
            }

            $query = "INSERT INTO users (username, first_name, last_name, email, phone, password, role, status) 
                      VALUES (:username, :first_name, :last_name, :email, :phone, :password, 'staff', 'active')";
            $stmt = $db->prepare($query);

            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $stmt->bindParam(':username', $_POST['username']);
            $stmt->bindParam(':first_name', $_POST['first_name']);
            $stmt->bindParam(':last_name', $_POST['last_name']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':phone', $_POST['phone']);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Employee created successfully!';
            } else {
                $_SESSION['error'] = 'Failed to create employee. Please try again.';
            }
            break;

        case 'update':
            // Update employee details
            $query = "UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, 
                      email = :email, phone = :phone, status = :status WHERE id = :id AND role = 'staff'";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':username', $_POST['username']);
            $stmt->bindParam(':first_name', $_POST['first_name']);
            $stmt->bindParam(':last_name', $_POST['last_name']);
            $stmt->bindParam(':email', $_POST['email']);
            $stmt->bindParam(':phone', $_POST['phone']);
            $stmt->bindParam(':status', $_POST['status']);
            $stmt->bindParam(':id', $_POST['employee_id']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Employee updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update employee.';
            }
            break;

        case 'reset_password':
            // Reset employee password
            $query = "UPDATE users SET password = :password WHERE id = :id AND role = 'staff'";
            $stmt = $db->prepare($query);
            
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':id', $_POST['employee_id']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Password reset successfully!';
            } else {
                $_SESSION['error'] = 'Failed to reset password.';
            }
            break;

        case 'toggle_status':
            // Toggle employee active/inactive status
            $query = "UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END 
                      WHERE id = :id AND role = 'staff'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['employee_id']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Employee status updated!';
            } else {
                $_SESSION['error'] = 'Failed to update status.';
            }
            break;

        case 'delete':
            // Delete employee (soft delete by setting status to deleted)
            $query = "UPDATE users SET status = 'deleted' WHERE id = :id AND role = 'staff'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_POST['employee_id']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Employee deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete employee.';
            }
            break;
    }

    header('Location: employees.php');
    exit();
}

// Get all employees with their booking statistics
$query = "SELECT u.*, 
          COUNT(DISTINCT b.id) as total_bookings,
          COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) as completed_bookings
          FROM users u
          LEFT JOIN bookings b ON u.id = b.employee_id
          WHERE u.role = 'staff' AND u.status != 'deleted'
          GROUP BY u.id
          ORDER BY u.created_at DESC";
$employees = $db->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - <?php echo SITE_NAME; ?></title>
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
            padding: 15px 20px;
        }
        
        .sidebar .list-group-item:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary-blue);
            transform: translateX(5px);
        }
        
        .sidebar .list-group-item.active {
            background: linear-gradient(90deg, rgba(30, 136, 229, 0.1) 0%, transparent 100%);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
            font-weight: 600;
        }
        
        .employee-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .employee-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-badge {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0"><i class="fas fa-user-tie me-2"></i>Manage Employees</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-user-plus me-2"></i>Add New Employee
                    </button>
                </div>

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
                    <?php while ($emp = $employees->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card employee-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="employee-avatar me-3">
                                        <?php echo strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h5>
                                        <p class="text-muted mb-1 small">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($emp['email']); ?>
                                        </p>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($emp['phone'] ?? 'N/A'); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-<?php echo $emp['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($emp['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex gap-2 mb-3">
                                    <div class="stat-badge flex-fill text-center">
                                        <div class="fw-bold text-primary"><?php echo $emp['total_bookings']; ?></div>
                                        <small class="text-muted">Total Jobs</small>
                                    </div>
                                    <div class="stat-badge flex-fill text-center">
                                        <div class="fw-bold text-success"><?php echo $emp['completed_bookings']; ?></div>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="resetPassword(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name']); ?>')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Toggle employee status?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        
                        <!-- Added username field -->
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                            <small class="text-muted">Used for login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="employee_id" id="edit_employee_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                        
                        <!-- Added username field for editing -->
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                            <small class="text-muted">Used for login</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="employee_id" id="reset_employee_id">
                        
                        <p>Reset password for <strong id="reset_employee_name"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEmployee(employee) {
            document.getElementById('edit_employee_id').value = employee.id;
            document.getElementById('edit_first_name').value = employee.first_name;
            document.getElementById('edit_last_name').value = employee.last_name;
            document.getElementById('edit_username').value = employee.username;
            document.getElementById('edit_email').value = employee.email;
            document.getElementById('edit_phone').value = employee.phone || '';
            document.getElementById('edit_status').value = employee.status;
            
            new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
        }
        
        function resetPassword(employeeId, employeeName) {
            document.getElementById('reset_employee_id').value = employeeId;
            document.getElementById('reset_employee_name').textContent = employeeName;
            
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        }
    </script>
</body>
</html>
