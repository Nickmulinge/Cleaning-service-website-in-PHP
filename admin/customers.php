<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';
require_once '../models/User.php';

AuthController::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: customers.php');
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        $query = "UPDATE users SET first_name=:first_name, last_name=:last_name, 
                  email=:email, phone=:phone, status=:status WHERE id=:id";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':first_name', $_POST['first_name']);
        $stmt->bindParam(':last_name', $_POST['last_name']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':phone', $_POST['phone']);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_POST['id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Customer updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update customer.';
        }
    } elseif ($action === 'toggle_status') {
        $new_status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        
        $query = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $_POST['id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Customer status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update customer status.';
        }
    }

    header('Location: customers.php');
    exit();
}

// Get all customers with their booking statistics
$customers_query = "
    SELECT u.*, 
           COUNT(DISTINCT b.id) AS total_bookings,
           COUNT(DISTINCT CASE WHEN b.booking_status = 'completed' THEN b.id END) AS completed_bookings,
           COALESCE(SUM(CASE WHEN b.booking_status = 'completed' THEN s.price ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN bookings b ON u.id = b.customer_id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$customers = $db->query($customers_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - <?php echo SITE_NAME; ?></title>
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
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .customer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
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
                    <h2 class="fw-bold mb-0">Manage Customers</h2>
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

                <div class="card table-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Total Bookings</th>
                                        <th>Completed</th>
                                        <th>Total Spent</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($customer = $customers->fetch(PDO::FETCH_ASSOC)): 
                                        $colors = ['#667eea', '#f093fb', '#4facfe', '#43e97b', '#fa709a'];
                                        $color = $colors[$customer['id'] % count($colors)];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="customer-avatar me-3" style="background: <?php echo $color; ?>">
                                                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                                                    <small class="text-muted">ID: #<?php echo $customer['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($customer['email']); ?></div>
                                            <div><i class="fas fa-phone text-muted me-2"></i><?php echo htmlspecialchars($customer['phone']); ?></div>
                                        </td>
                                        <td class="fw-semibold"><?php echo $customer['total_bookings']; ?></td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $customer['completed_bookings']; ?></span>
                                        </td>
                                        <td class="fw-semibold text-success">$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $customer['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($customer['status']); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></small></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editCustomer(<?php echo htmlspecialchars(json_encode($customer)); ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="viewCustomerDetails(<?php echo $customer['id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-<?php echo $customer['status'] === 'active' ? 'danger' : 'success'; ?>" 
                                                        onclick="toggleStatus(<?php echo $customer['id']; ?>, '<?php echo $customer['status']; ?>', '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>')"
                                                        title="<?php echo $customer['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $customer['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

     Edit Customer Modal 
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     Toggle Status Modal 
    <div class="modal fade" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" id="toggle_header">
                    <h5 class="modal-title fw-bold" id="toggle_title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" id="toggle_id">
                    <input type="hidden" name="current_status" id="toggle_current_status">
                    <div class="modal-body">
                        <p id="toggle_message"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="toggle_btn"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     View Customer Details Modal 
    <div class="modal fade" id="viewCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCustomer(customer) {
            document.getElementById('edit_id').value = customer.id;
            document.getElementById('edit_first_name').value = customer.first_name;
            document.getElementById('edit_last_name').value = customer.last_name;
            document.getElementById('edit_email').value = customer.email;
            document.getElementById('edit_phone').value = customer.phone;
            document.getElementById('edit_status').value = customer.status;
            
            new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
        }

        function toggleStatus(id, currentStatus, name) {
            document.getElementById('toggle_id').value = id;
            document.getElementById('toggle_current_status').value = currentStatus;
            
            const isActive = currentStatus === 'active';
            const header = document.getElementById('toggle_header');
            const title = document.getElementById('toggle_title');
            const message = document.getElementById('toggle_message');
            const btn = document.getElementById('toggle_btn');
            
            if (isActive) {
                header.className = 'modal-header bg-danger text-white';
                title.textContent = 'Deactivate Customer';
                message.innerHTML = `Are you sure you want to deactivate <strong>${name}</strong>? They will not be able to make new bookings.`;
                btn.className = 'btn btn-danger';
                btn.textContent = 'Deactivate';
            } else {
                header.className = 'modal-header bg-success text-white';
                title.textContent = 'Activate Customer';
                message.innerHTML = `Are you sure you want to activate <strong>${name}</strong>? They will be able to make bookings again.`;
                btn.className = 'btn btn-success';
                btn.textContent = 'Activate';
            }
            
            new bootstrap.Modal(document.getElementById('toggleStatusModal')).show();
        }

        function viewCustomerDetails(customerId) {
            const modal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
            modal.show();
            
            fetch('customer-details-ajax.php?id=' + customerId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('customerDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('customerDetailsContent').innerHTML = 
                        '<div class="alert alert-danger">Failed to load customer details.</div>';
                });
        }
    </script>
</body>
</html>
