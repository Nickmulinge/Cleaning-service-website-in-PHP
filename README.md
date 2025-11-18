# Cleanfinity Cleaning Services

A complete PHP web application for managing cleaning service bookings with role-based access control.

## Features

- **User Management**: Customer, Staff, and Admin roles
- **Booking System**: Service booking with availability checking
- **Service Management**: CRUD operations for cleaning services
- **Dashboard**: Role-specific dashboards with analytics
- **Security**: CSRF protection, password hashing, input sanitization
- **Responsive Design**: Bootstrap-based responsive UI

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled

### Local Setup (XAMPP/WAMP)

1. **Download and Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Install and start Apache and MySQL services

2. **Setup the Application**
   \`\`\`bash
   # Copy files to htdocs folder
   cp -r cleanfinity/ C:/xampp/htdocs/cleanfinity/
   
   # Or create symbolic link
   ln -s /path/to/cleanfinity C:/xampp/htdocs/cleanfinity
   \`\`\`

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema: `database/schema.sql`
   - Or run the SQL commands manually

4. **Configuration**
   - Update database credentials in `config/database.php`
   - Update email settings in `config/config.php`

5. **Access the Application**
   - Visit: http://localhost/cleanfinity/
   - Admin login: admin@cleanfinity.com / password

### Production Deployment (Hostinger)

1. **Upload Files**
   - Upload all files to your domain's public_html folder
   - Ensure proper file permissions (644 for files, 755 for folders)

2. **Database Setup**
   - Create MySQL database in Hostinger control panel
   - Import `database/schema.sql`
   - Update database credentials in `config/database.php`

3. **Configuration**
   - Update `BASE_URL` in `config/config.php`
   - Configure email settings for notifications
   - Enable SSL certificate

4. **Security**
   - Ensure `.htaccess` file is uploaded
   - Set proper file permissions
   - Enable HTTPS redirect

## File Structure

\`\`\`
cleanfinity/
├── config/
│   ├── config.php          # Application configuration
│   └── database.php        # Database connection
├── models/
│   ├── User.php           # User model
│   ├── Service.php        # Service model
│   └── Booking.php        # Booking model
├── controllers/
│   ├── AuthController.php # Authentication logic
│   └── BookingController.php # Booking logic
├── customer/
│   ├── dashboard.php      # Customer dashboard
│   └── book-service.php   # Service booking
├── admin/
│   └── dashboard.php      # Admin dashboard
├── database/
│   └── schema.sql         # Database schema
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
├── logout.php             # Logout handler
├── .htaccess             # Apache configuration
└── README.md             # This file
\`\`\`

## Default Login Credentials

- **Admin**: admin@cleanfinity.com / password
- **Customer**: Register new account at /register.php

## API Endpoints

The application includes REST API endpoints:

- `GET /api/services.php` - List all services
- `POST /api/bookings.php` - Create new booking
- `GET /api/bookings.php?customer_id=X` - Get customer bookings
- `PUT /api/bookings.php` - Update booking status

## Security Features

- Password hashing with PHP's `password_hash()`
- CSRF token protection
- SQL injection prevention with PDO prepared statements
- XSS protection with input sanitization
- Session timeout management
- Role-based access control

## Customization

### Adding New Services
1. Login as admin
2. Navigate to Services section
3. Add new service with pricing and duration

### Email Notifications
Configure SMTP settings in `config/config.php`:
\`\`\`php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-app-password');
\`\`\`

### Styling
- CSS framework: Bootstrap 5.1.3
- Icons: Font Awesome 6.0.0
- Custom styles can be added to individual pages

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running

2. **404 Errors**
   - Ensure mod_rewrite is enabled
   - Check `.htaccess` file is uploaded

3. **Permission Denied**
   - Set proper file permissions (644/755)
   - Check folder ownership

4. **Session Issues**
   - Ensure session directory is writable
   - Check PHP session configuration

## Support

For technical support or customization requests, please contact the development team.

## License

This project is proprietary software developed for Cleanfinity Cleaning Services.
"# Cleaning-service-website-in-PHP" 
