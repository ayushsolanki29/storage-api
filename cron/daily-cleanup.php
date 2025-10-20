<?php
require_once __DIR__ . '/../config/database.php';

// Clean up temporary files older than 24 hours
$temp_dir = sys_get_temp_dir();
$files = glob($temp_dir . '/mini-cloudinary_*');
$now = time();

foreach ($files as $file) {
    if (is_file($file) && ($now - filemtime($file)) > 86400) {
        unlink($file);
    }
}

// Log cleanup
error_log("Daily cleanup completed: " . date('Y-m-d H:i:s'));