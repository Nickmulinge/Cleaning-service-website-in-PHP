<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();

$staff_id = $_SESSION['user_id'];

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_availability'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: settings.php');
        exit();
    }
    
    $availability = json_encode([
        'monday' => isset($_POST['monday']),
        'tuesday' => isset($_POST['tuesday']),
        'wednesday' => isset($_POST['wednesday']),
        'thursday' => isset($_POST['thursday']),
        'friday' => isset($_POST['friday']),
        'saturday' => isset($_POST['saturday']),
        'sunday' => isset($_POST['sunday']),
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time']
    ]);
    
    $query = "UPDATE users SET availability = :availability WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':availability', $availability);
    $stmt->bindParam(':id', $staff_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Availability updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update availability';
    }
    
    header('Location: settings.php');
    exit();
}

// Get current availability
$query = "SELECT availability FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $staff_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$availability = json_decode($user_data['availability'] ?? '{}', true);
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
        body {
            background: #f8f9fa;
        }
        
        .day-checkbox {
            display: none;
        }
        
        .day-label {
            display: inline-block;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .day-checkbox:checked + .day-label {
            background: linear-gradient(135deg, #7E57C2, #9575cd);
            color: white;
            border-color: #7E57C2;
        }
        
        .day-label:hover {
            border-color: #7E57C2;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="fw-bold mb-4">Settings</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Availability Settings -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-calendar-check me-2 text-purple"></i>Work Availability
                        </h5>
                        <p class="text-muted small mb-0 mt-2">Set your working days and hours</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="update_availability" value="1">
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">Working Days</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php
                                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    foreach ($days as $day):
                                        $checked = isset($availability[$day]) && $availability[$day] ? 'checked' : '';
                                    ?>
                                        <div>
                                            <input type="checkbox" name="<?php echo $day; ?>" id="<?php echo $day; ?>" class="day-checkbox" <?php echo $checked; ?>>
                                            <label for="<?php echo $day; ?>" class="day-label">
                                                <?php echo ucfirst($day); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="<?php echo $availability['start_time'] ?? '09:00'; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="<?php echo $availability['end_time'] ?? '17:00'; ?>" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-purple">
                                <i class="fas fa-save me-2"></i>Save Availability
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="fw-bold mb-0">
                            <i class="fas fa-bell me-2 text-purple"></i>Notification Preferences
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                            <label class="form-check-label" for="emailNotifications">
                                <strong>Email Notifications</strong>
                                <p class="text-muted small mb-0">Receive email alerts for new bookings and updates</p>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="bookingReminders" checked>
                            <label class="form-check-label" for="bookingReminders">
                                <strong>Booking Reminders</strong>
                                <p class="text-muted small mb-0">Get reminders 24 hours before scheduled bookings</p>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="reviewNotifications" checked>
                            <label class="form-check-label" for="reviewNotifications">
                                <strong>Review Notifications</strong>
                                <p class="text-muted small mb-0">Be notified when customers leave reviews</p>
                            </label>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    .btn-purple {
        background: linear-gradient(135deg, #7E57C2, #9575cd);
        color: white;
        border: none;
        padding: 12px 30px;
        font-weight: 600;
    }
    
    .btn-purple:hover {
        background: linear-gradient(135deg, #5e35b1, #7E57C2);
        color: white;
    }
    
    .text-purple {
        color: #7E57C2;
    }
    
    .form-check-input:checked {
        background-color: #7E57C2;
        border-color: #7E57C2;
    }
</style>
