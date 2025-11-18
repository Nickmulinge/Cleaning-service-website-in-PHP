<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();

$staff_id = $_SESSION['user_id'];

// Get current month and year
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Get bookings for the month
$query = "SELECT 
    b.id,
    b.booking_date,
    b.booking_time,
    b.status,
    s.name as service_name,
    s.duration,
    u.first_name as customer_first_name,
    u.last_name as customer_last_name
FROM bookings b
JOIN services s ON b.service_id = s.id
JOIN users u ON b.customer_id = u.id
WHERE b.employee_id = :staff_id
AND MONTH(b.booking_date) = :month
AND YEAR(b.booking_date) = :year
ORDER BY b.booking_date ASC, b.booking_time ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':staff_id', $staff_id);
$stmt->bindParam(':month', $month);
$stmt->bindParam(':year', $year);
$stmt->execute();

// Organize bookings by date
$bookings_by_date = [];
while ($booking = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $booking['booking_date'];
    if (!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}

// Calendar helper functions
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --staff-purple: #7E57C2;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .calendar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 100px;
        }
        
        .calendar-day:hover {
            border-color: var(--staff-purple);
            box-shadow: 0 3px 10px rgba(126, 87, 194, 0.2);
        }
        
        .calendar-day.empty {
            background: #f8f9fa;
            cursor: default;
        }
        
        .calendar-day.today {
            border-color: var(--staff-purple);
            background: rgba(126, 87, 194, 0.05);
        }
        
        .day-number {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .booking-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            margin: 2px;
        }
        
        .booking-dot.pending {
            background: #ffc107;
        }
        
        .booking-dot.in_progress {
            background: #0d6efd;
        }
        
        .booking-dot.completed {
            background: #198754;
        }
        
        .booking-count {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="fw-bold mb-4">My Schedule</h2>
                
                <div class="calendar">
                    <div class="calendar-header">
                        <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>" class="btn btn-outline-purple">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <h4 class="fw-bold mb-0"><?php echo date('F Y', $first_day); ?></h4>
                        <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>" class="btn btn-outline-purple">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                    
                    <div class="calendar-grid">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                        
                        <?php
                        // Empty cells before first day
                        for ($i = 0; $i < $day_of_week; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Days of the month
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $is_today = $current_date == date('Y-m-d');
                            $has_bookings = isset($bookings_by_date[$current_date]);
                            
                            echo '<div class="calendar-day' . ($is_today ? ' today' : '') . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            if ($has_bookings) {
                                $booking_count = count($bookings_by_date[$current_date]);
                                echo '<div>';
                                foreach ($bookings_by_date[$current_date] as $booking) {
                                    echo '<span class="booking-dot ' . $booking['status'] . '"></span>';
                                }
                                echo '</div>';
                                echo '<div class="booking-count">' . $booking_count . ' booking' . ($booking_count > 1 ? 's' : '') . '</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="mt-4 d-flex gap-3 justify-content-center">
                        <div><span class="booking-dot pending"></span> Pending</div>
                        <div><span class="booking-dot in_progress"></span> In Progress</div>
                        <div><span class="booking-dot completed"></span> Completed</div>
                    </div>
                </div>
                
                <!-- Today's Schedule -->
                <?php
                $today = date('Y-m-d');
                if (isset($bookings_by_date[$today]) && count($bookings_by_date[$today]) > 0):
                ?>
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0">
                                <i class="fas fa-calendar-day me-2 text-purple"></i>Today's Schedule
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($bookings_by_date[$today] as $booking): ?>
                                <div class="d-flex justify-content-between align-items-center p-3 mb-2 border rounded">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                        <div class="text-muted small">
                                            <?php echo date('g:i A', strtotime($booking['booking_time'])); ?> - 
                                            <?php echo htmlspecialchars($booking['customer_first_name'] . ' ' . $booking['customer_last_name']); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<style>
    .btn-outline-purple {
        color: #7E57C2;
        border-color: #7E57C2;
    }
    
    .btn-outline-purple:hover {
        background: #7E57C2;
        color: white;
    }
    
    .text-purple {
        color: #7E57C2;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-in_progress {
        background: #cfe2ff;
        color: #084298;
    }
    
    .status-completed {
        background: #d1e7dd;
        color: #0f5132;
    }
</style>
