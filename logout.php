<?php
session_start();
require_once 'includes/functions.php';

// Delete remember token if exists
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    if (isset($_SESSION['user_id'])) {
        delete_remember_token($_SESSION['user_id'], $token);
    }
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php?logout=1');
exit;
