<div class="col-md-2 p-0 sidebar">
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="services.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>">
            <i class="fas fa-broom me-2"></i>Services
        </a>
        <a href="bookings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check me-2"></i>Bookings
        </a>
        <a href="employees.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie me-2"></i>Employees
        </a>
        <a href="customers.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users me-2"></i>Customers
        </a>
        <a href="payments.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card me-2"></i>Payments & Invoices
        </a>
        <a href="reviews.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-star me-2"></i>Reviews
        </a>
        <a href="settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog me-2"></i>Settings
        </a>
    </div>
</div>
