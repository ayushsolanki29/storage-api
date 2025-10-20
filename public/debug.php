<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP Version: " . phpversion() . "</h2>";
echo "<h2>Checking requirements...</h2>";

// Check GD
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✓ GD extension is loaded</p>";
} else {
    echo "<p style='color: red;'>✗ GD extension is NOT loaded</p>";
}

// Check PDO MySQL
if (extension_loaded('pdo_mysql')) {
    echo "<p style='color: green;'>✓ PDO MySQL extension is loaded</p>";
} else {
    echo "<p style='color: red;'>✗ PDO MySQL extension is NOT loaded</p>";
}

// Check file permissions
$uploads_dir = '../uploads';
if (is_writable($uploads_dir)) {
    echo "<p style='color: green;'>✓ Uploads directory is writable</p>";
} else {
    echo "<p style='color: red;'>✗ Uploads directory is NOT writable</p>";
}

// Check .env file
if (file_exists('../.env')) {
    echo "<p style='color: green;'>✓ .env file exists</p>";
} else {
    echo "<p style='color: red;'>✗ .env file does NOT exist</p>";
}

echo "<h2>Testing database connection...</h2>";
try {
    require_once '../config/Database.php';
    $db = new Database();
    $conn = $db->connect();
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>