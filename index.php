<?php
// Enable error reporting for local development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Simple autoloader for local development
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once 'includes/helpers.php';
loadEnv();

// Check if we need installation
if (!file_exists('.env') || !is_dir('uploads')) {
    header('Location: install.php');
    exit;
}

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: dashboard/');
} else {
    header('Location: auth/login.php');
}
exit;
?>