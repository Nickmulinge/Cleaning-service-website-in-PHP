<?php
require_once 'config/config.php';
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->logout();
?>
