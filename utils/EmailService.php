<?php
/**
 * EmailService - Handles email functionality for the application
 * Uses PHP's built-in mail() function for simplicity
 */

class EmailService {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/email.php';
    }
    
    /**
     * Send email using PHP's built-in mail() function
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $altMessage Plain text alternative (optional)
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $message, $altMessage = '') {
        try {
            $headers = array();
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>';
            $headers[] = 'Reply-To: ' . $this->config['from_email'];
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($result) {
                error_log("Email sent successfully to: " . $to);
                return true;
            } else {
                error_log("Email failed to send to: " . $to);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     * @param string $email User email
     * @param string $resetToken Reset token
     * @param string $resetUrl Base reset URL
     * @return bool Success status
     */
    public function sendPasswordResetEmail($email, $resetToken, $resetUrl) {
        $subject = "Password Reset - Cleanfinity Cleaning Services";
        
        $resetLink = $resetUrl . "?token=" . $resetToken;
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #43A047; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background-color: #1E88E5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to reset your password for your Cleanfinity Cleaning Services account.</p>
                    <p>Click the button below to reset your password:</p>
                    <p><a href='{$resetLink}' class='button'>Reset Password</a></p>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                    <p>This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Cleanfinity Cleaning Services. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * Send booking confirmation email
     * @param string $email Customer email
     * @param array $bookingData Booking details
     * @return bool Success status
     */
    public function sendBookingConfirmation($email, $bookingData) {
        $subject = "Booking Confirmation - Cleanfinity Cleaning Services";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #43A047; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .booking-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Booking Confirmed!</h1>
                </div>
                <div class='content'>
                    <p>Dear {$bookingData['customer_name']},</p>
                    <p>Thank you for choosing Cleanfinity Cleaning Services! Your booking has been confirmed.</p>
                    
                    <div class='booking-details'>
                        <h3>Booking Details:</h3>
                        <p><strong>Service:</strong> {$bookingData['service_name']}</p>
                        <p><strong>Date:</strong> {$bookingData['booking_date']}</p>
                        <p><strong>Time:</strong> {$bookingData['booking_time']}</p>
                        <p><strong>Address:</strong> {$bookingData['address']}</p>
                        <p><strong>Total Cost:</strong> $" . number_format($bookingData['total_cost'], 2) . "</p>
                    </div>
                    
                    <p>Our team will arrive at the scheduled time. If you need to make any changes, please contact us at least 24 hours in advance.</p>
                    <p>Thank you for your business!</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Cleanfinity Cleaning Services. All rights reserved.</p>
                    <p>Contact us: info@cleanfinity.com | (555) 123-4567</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $message);
    }
}
?>
