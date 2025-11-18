<?php
require_once 'config/config.php';
require_once 'models/Service.php';

$database = new Database();
$db = $database->getConnection();
$service = new Service($db);
$services = $service->readAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo SITE_NAME; ?> - Professional Cleaning Services</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-green: #43A047;
            --primary-blue: #1E88E5;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-green) 100%);
            background-image: url('https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?w=1200&h=600&fit=crop&auto=format');
            background-blend-mode: overlay;
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 0;
            position: relative;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 136, 229, 0.8);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .service-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .service-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .btn-book-now {
            background: linear-gradient(45deg, var(--primary-green), var(--primary-blue));
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.4);
            color: white;
        }
        
        .section-padding {
            padding: 80px 0;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--primary-green), var(--primary-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
       
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.php">
                
                <i class="fas fa-sparkles" style="color: var(--primary-green);"></i> 
                <span style="color: var(--primary-blue);">Cleanfinity</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                     <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#hero-section">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="btn btn-book-now" href="#" onclick="handleBookNow()">
                            <i class="fas fa-calendar-plus me-2"></i>Book Now
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['role']; ?>/dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary ms-2" href="register.php">Sign Up</a>
                        </li> -->
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-3 fw-bold mb-4">Professional Cleaning Services You Can Trust</h1>
                    <p class="lead mb-4 fs-5">Experience the difference with Cleanfinity's expert cleaning team. We transform your space into a spotless sanctuary with eco-friendly products and meticulous attention to detail.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="register.php" class="btn btn-light btn-lg px-4 py-3">
                            <i class="fas fa-calendar-check me-2"></i>Book Your Service
                        </a>
                        <a href="#services" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-arrow-down me-2"></i>View Services
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=600&h=500&fit=crop&auto=format" 
                         class="img-fluid rounded-4 shadow-lg" alt="Professional Cleaning Service">
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 fw-bold mb-3" style="color: var(--primary-blue);">Our Professional Services</h2>
                <p class="lead text-muted">Choose from our comprehensive range of cleaning solutions tailored to your needs</p>
            </div>
            <div class="row g-4">
                <?php while ($row = $services->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-lg-6 col-xl-3">
                    <div class="card service-card h-100 shadow-sm">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                             class="service-image" 
                             alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold mb-3" style="color: var(--primary-blue);">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h5>
                            <p class="card-text text-muted mb-4"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 mb-0" style="color: var(--primary-green);">
                                    $<?php echo number_format($row['base_price'], 2); ?>
                                </span>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i><?php echo $row['duration_minutes']; ?> mins
                                </small>
                            </div>
                            <a href="#" onclick="handleServiceBooking(<?php echo $row['id']; ?>)" 
                               class="btn btn-book-now w-100">
                                <i class="fas fa-calendar-plus me-2"></i>Book This Service
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <section id="about" class="section-padding" style="background-color: var(--light-gray);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?w=600&h=500&fit=crop&auto=format" 
                         class="img-fluid rounded-4 shadow-lg" alt="Professional Cleaning Team">
                </div>
                <div class="col-lg-6">
                    <h2 class="display-4 fw-bold mb-4" style="color: var(--primary-blue);">Why Choose Cleanfinity?</h2>
                    <p class="lead mb-5 text-muted">We're committed to delivering exceptional cleaning services that exceed your expectations every time.</p>
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="text-center">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h6 class="fw-bold">Professional Staff</h6>
                                <p class="text-muted small">Trained and experienced cleaning professionals</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-center">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h6 class="fw-bold">Insured & Bonded</h6>
                                <p class="text-muted small">Fully insured for your peace of mind</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-center">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h6 class="fw-bold">Flexible Scheduling</h6>
                                <p class="text-muted small">Book at your convenience</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-center">
                                <div class="feature-icon mx-auto">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <h6 class="fw-bold">Eco-Friendly</h6>
                                <p class="text-muted small">Safe, green cleaning products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Get In Touch</h2>
                <p class="lead">Ready to book your cleaning service?</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                            <h6>Call Us</h6>
                            <p>(555) 123-4567</p>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                            <h6>Email Us</h6>
                            <p>info@cleanfinity.com</p>
                        </div>
                        <div class="col-md-4 text-center mb-4">
                            <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                            <h6>Visit Us</h6>
                            <p>123 Clean Street<br>City, State 12345</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   <footer class="footer">
    <div class="container py-5">
        <div class="row align-items-center">
            <!-- Left side -->
            <div class="col-md-6 mb-3 mb-md-0">
                <h5 class="mb-3 footer-logo">
                    <i class="fas fa-sparkles"></i> 
                    <span>Cleanfinity</span>
                </h5>
                <p class="text-muted">Professional cleaning services you can trust. Making your space sparkle since 2025.</p>
                <div class="social-icons mt-2">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <!-- Right side -->
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">&copy; 2025 Cleanfinity Cleaning Services. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background: linear-gradient(135deg, #1E88E5, #43A047); /* gradient using your primary blue and green */
    color: #fff;
    font-family: 'Roboto', sans-serif;
}

.footer-logo i {
    color: #43A047;
    margin-right: 8px;
}

.footer-logo span {
    color: #fff;
    font-weight: bold;
    font-size: 1.4rem;
}

.footer p {
    color: rgba(255, 255, 255, 0.8);
}

.social-icons a {
    color: #fff;
    font-size: 1.1rem;
    transition: transform 0.2s, color 0.2s;
}

.social-icons a:hover {
    color: #43A047;
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .text-md-end {
        text-align: left !important;
    }
}
</style>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleBookNow() {
            <?php if (isset($_SESSION['user_id'])): ?>
                if (window.location.pathname.includes('dashboard')) {
                    document.getElementById('services').scrollIntoView({ behavior: 'smooth' });
                } else {
                    window.location.href = 'customer/dashboard.php#services';
                }
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
        
        function handleServiceBooking(serviceId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = 'customer/book-service.php?service_id=' + serviceId;
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>
