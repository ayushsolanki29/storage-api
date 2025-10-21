<?php
session_start();
require_once '../includes/admin-auth.php';

if (is_admin()) {
    header('Location: dashboard.php');
    exit;
} else {
    // If user is logged in but not admin, show error
    if (isset($_SESSION['user_id'])) {
        die('Access denied. You need administrator privileges to access this page. <a href="../dashboard.php">Go to user dashboard</a>');
    } else {
        header('Location: login.php');
        exit;
    }
}
?>