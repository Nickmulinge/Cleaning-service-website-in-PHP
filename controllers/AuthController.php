<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: login.php');
                exit();
            }

            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->user->login($username, $password)) {
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['role'] = $this->user->role;
                $_SESSION['first_name'] = $this->user->first_name;
                $_SESSION['last_name'] = $this->user->last_name;
                $_SESSION['login_time'] = time();

                // Redirect based on role
                switch ($this->user->role) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'staff':
                        header('Location: staff/dashboard.php');
                        break;
                    default:
                        header('Location: customer/dashboard.php');
                        break;
                }
                exit();
            } else {
                $_SESSION['error'] = 'Invalid username or password';
                header('Location: login.php');
                exit();
            }
        }
    }

    public function staffLogin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: staff/index.php');
                exit();
            }

            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->user->login($username, $password)) {
                // Verify user is staff
                if ($this->user->role !== 'staff') {
                    $_SESSION['error'] = 'Access denied. Staff credentials required.';
                    header('Location: staff/index.php');
                    exit();
                }

                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['role'] = $this->user->role;
                $_SESSION['first_name'] = $this->user->first_name;
                $_SESSION['last_name'] = $this->user->last_name;
                $_SESSION['login_time'] = time();

                header('Location: dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = 'Invalid username or password';
                header('Location: index.php');
                exit();
            }
        }
    }

    public function adminLogin() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: admin/index.php');
                exit();
            }

            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->user->login($username, $password)) {
                // Verify user is admin
                if ($this->user->role !== 'admin') {
                    $_SESSION['error'] = 'Access denied. Admin credentials required.';
                    header('Location: admin/index.php');
                    exit();
                }

                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['role'] = $this->user->role;
                $_SESSION['first_name'] = $this->user->first_name;
                $_SESSION['last_name'] = $this->user->last_name;
                $_SESSION['login_time'] = time();

                header('Location: dashboard.php');
                exit();
            } else {
                $_SESSION['error'] = 'Invalid username or password';
                header('Location: index.php');
                exit();
            }
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!validateCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: register.php');
                exit();
            }

            $this->user->username = $_POST['username'];
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];
            $this->user->first_name = $_POST['first_name'];
            $this->user->last_name = $_POST['last_name'];
            $this->user->phone = $_POST['phone'];
            $this->user->address = $_POST['address'];
            $this->user->role = 'customer';

            if ($this->user->create()) {
                $_SESSION['success'] = 'Registration successful! Please login.';
                header('Location: login.php');
                exit();
            } else {
                $_SESSION['error'] = 'Registration failed. Please try again.';
                header('Location: register.php');
                exit();
            }
        }
    }

    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }

    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }

        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            session_destroy();
            header('Location: index.php?timeout=1');
            exit();
        }
    }

    public static function requireRole($required_role) {
        self::requireLogin();
        
        if ($_SESSION['role'] !== $required_role) {
            header('Location: unauthorized.php');
            exit();
        }
    }
}
?>
