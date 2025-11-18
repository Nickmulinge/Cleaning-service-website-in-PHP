<?php
require_once '../config/config.php';
require_once '../controllers/AuthController.php';

AuthController::requireRole('staff');

$database = new Database();
$db = $database->getConnection();
$staff_id = $_SESSION['user_id'];

// Handle marking tutorial as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $module_id = filter_var($_POST['module_id'], FILTER_VALIDATE_INT);
    
    if ($module_id) {
        // Check if progress record exists
        $check_query = "SELECT id, status FROM staff_learning_progress 
                       WHERE staff_id = :staff_id AND module_id = :module_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':staff_id', $staff_id);
        $check_stmt->bindParam(':module_id', $module_id);
        $check_stmt->execute();
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $update_query = "UPDATE staff_learning_progress 
                           SET status = 'completed', completed_at = NOW() 
                           WHERE staff_id = :staff_id AND module_id = :module_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':staff_id', $staff_id);
            $update_stmt->bindParam(':module_id', $module_id);
            $update_stmt->execute();
        } else {
            // Insert new record
            $insert_query = "INSERT INTO staff_learning_progress (staff_id, module_id, status, completed_at) 
                           VALUES (:staff_id, :module_id, 'completed', NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':staff_id', $staff_id);
            $insert_stmt->bindParam(':module_id', $module_id);
            $insert_stmt->execute();
        }
        
        $_SESSION['success'] = 'Tutorial marked as completed!';
        header('Location: learning.php');
        exit();
    }
}

// Get all learning modules with completion status
$query = "SELECT 
    lm.id,
    lm.title,
    lm.content,
    lm.created_at,
    COALESCE(slp.status, 'incomplete') as completion_status,
    slp.completed_at
FROM learning_modules lm
LEFT JOIN staff_learning_progress slp ON lm.id = slp.module_id AND slp.staff_id = :staff_id
ORDER BY lm.id ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':staff_id', $staff_id);
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate completion statistics
$total_modules = count($modules);
$completed_modules = count(array_filter($modules, fn($m) => $m['completion_status'] === 'completed'));
$completion_percentage = $total_modules > 0 ? round(($completed_modules / $total_modules) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Module - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --staff-purple: #7E57C2;
            --staff-light: #9575cd;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .progress-card {
            background: linear-gradient(135deg, var(--staff-purple), var(--staff-light));
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(126, 87, 194, 0.3);
        }
        
        .module-card {
            border: none;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .module-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .module-card.completed {
            border-left: 5px solid #43A047;
        }
        
        .module-card.incomplete {
            border-left: 5px solid #FB8C00;
        }
        
        .module-header {
            cursor: pointer;
            padding: 1.5rem;
            background: white;
            border-radius: 12px 12px 0 0;
        }
        
        .module-content {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            white-space: pre-line;
            line-height: 1.8;
        }
        
        .completion-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .badge-incomplete {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-complete {
            background: linear-gradient(135deg, #43A047, #66BB6A);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 160, 71, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold"><i class="fas fa-graduation-cap me-2"></i>Learning Module</h2>
                        <p class="text-muted mb-0">Enhance your skills with our training tutorials</p>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

               
                <div class="progress-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-3"><i class="fas fa-chart-line me-2"></i>Your Learning Progress</h4>
                            <div class="progress" style="height: 30px; background: rgba(255,255,255,0.2);">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $completion_percentage; ?>%;" 
                                     aria-valuenow="<?php echo $completion_percentage; ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    <strong><?php echo $completion_percentage; ?>%</strong>
                                </div>
                            </div>
                            <p class="mt-3 mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $completed_modules; ?> of <?php echo $total_modules; ?> tutorials completed
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div style="font-size: 4rem;">
                                <i class="fas fa-book-reader"></i>
                            </div>
                        </div>
                    </div>
                </div>

                 Learning Modules List 
                <div class="row">
                    <div class="col-12">
                        <?php foreach ($modules as $index => $module): ?>
                            <div class="card module-card <?php echo $module['completion_status']; ?>">
                                <div class="module-header" data-bs-toggle="collapse" 
                                     data-bs-target="#module<?php echo $module['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">
                                                <i class="fas fa-book me-2 text-purple"></i>
                                                <?php echo htmlspecialchars($module['title']); ?>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Added <?php echo date('M j, Y', strtotime($module['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="completion-badge badge-<?php echo $module['completion_status']; ?>">
                                                <i class="fas fa-<?php echo $module['completion_status'] === 'completed' ? 'check-circle' : 'clock'; ?> me-1"></i>
                                                <?php echo ucfirst($module['completion_status']); ?>
                                            </span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="collapse" id="module<?php echo $module['id']; ?>">
                                    <div class="module-content">
                                        <?php echo htmlspecialchars($module['content']); ?>
                                        
                                        <?php if ($module['completion_status'] === 'incomplete'): ?>
                                            <form method="POST" class="mt-4">
                                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                                <button type="submit" name="mark_complete" class="btn btn-complete">
                                                    <i class="fas fa-check me-2"></i>Mark as Completed
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-success mt-4 mb-0">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Completed on <?php echo date('M j, Y', strtotime($module['completed_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($modules)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                                <h5>No tutorials available yet</h5>
                                <p class="text-muted">Check back later for new learning materials</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
