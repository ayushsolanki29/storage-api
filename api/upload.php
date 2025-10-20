<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Get API key from query string
$api_key = $_GET['api_key'] ?? '';

if (empty($api_key)) {
    json_response(['error' => 'API key is required'], 400);
}

$user = get_user_by_api_key($api_key);

if (!$user) {
    error_log("Invalid API key provided: " . $api_key);
    json_response(['error' => 'Invalid API key'], 401);
}

// Validate user ID exists
if (!isset($user['id']) || empty($user['id'])) {
    error_log("User ID missing for API key: " . $api_key);
    json_response(['error' => 'Invalid user account'], 401);
}

// Check rate limiting
if (!check_rate_limit($user['id'])) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

// Check if user has reached upload limits
$used_space = $user['used_space'] ?? 0;
$storage_limit = $user['storage_limit'] ?? 1073741824; // 1GB default

if ($used_space >= $storage_limit) {
    json_response(['error' => 'Storage limit exceeded'], 400);
}

$monthly_requests = $user['monthly_requests'] ?? 0;
$monthly_request_limit = $user['monthly_request_limit'] ?? 1000;

if ($monthly_requests >= $monthly_request_limit) {
    json_response(['error' => 'Monthly request limit exceeded'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $upload_error = $_FILES['file']['error'] ?? 'Unknown';
    json_response(['error' => 'No file uploaded or upload error: ' . $upload_error], 400);
}

$file = $_FILES['file'];
$custom_filename = $_POST['filename'] ?? null;

// Validate file size
$max_file_size = $user['max_file_size'] ?? (5 * 1024 * 1024); // 5MB default
if ($file['size'] > $max_file_size) {
    json_response(['error' => 'File too large. Maximum size: ' . format_bytes($max_file_size)], 400);
}

// Validate file type
$allowed_types = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    json_response(['error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types)], 400);
}

// Create user directory structure
$year = date('Y');
$month = date('m');
$user_upload_dir = UPLOAD_PATH . "/{$user['id']}/{$year}/{$month}";

if (!is_dir($user_upload_dir)) {
    if (!mkdir($user_upload_dir, 0755, true)) {
        json_response(['error' => 'Failed to create upload directory'], 500);
    }
}

// Generate filename
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$base_filename = $custom_filename ?: pathinfo($file['name'], PATHINFO_FILENAME);
$final_filename = $base_filename . '_' . uniqid() . '.' . $file_extension;
$file_path = $user_upload_dir . '/' . $final_filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    json_response(['error' => 'Failed to save file'], 500);
}

$file_url = UPLOAD_URL . "/{$user['id']}/{$year}/{$month}/{$final_filename}";
$compressed_url = null;
$thumbnail_url = null;
$compression_ratio = null;
$width = null;
$height = null;
$compressed_path = null;
$thumbnail_path = null;

// Process image files
if (in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
    // Get original dimensions
    $image_info = getimagesize($file_path);
    if ($image_info) {
        $width = $image_info[0];
        $height = $image_info[1];
        
        // Create compressed version if allowed
        $allow_compression = $user['allow_compression'] ?? true;
        if ($allow_compression) {
            $compressed_path = $user_upload_dir . '/compressed_' . $final_filename;
            if (compress_image($file_path, $compressed_path)) {
                $compressed_size = filesize($compressed_path);
                $original_size = filesize($file_path);
                
                // Only keep compressed if it's smaller
                if ($compressed_size < $original_size) {
                    $compressed_url = UPLOAD_URL . "/{$user['id']}/{$year}/{$month}/compressed_{$final_filename}";
                    $compression_ratio = round(($original_size - $compressed_size) / $original_size * 100, 2);
                } else {
                    unlink($compressed_path);
                    $compressed_path = null;
                }
            }
        }
        
        // Create thumbnail if allowed
        $allow_thumbnails = $user['allow_thumbnails'] ?? true;
        if ($allow_thumbnails) {
            $thumbnail_path = $user_upload_dir . '/thumb_' . $final_filename;
            $thumb_dimensions = create_thumbnail($file_path, $thumbnail_path);
            if ($thumb_dimensions) {
                $thumbnail_url = UPLOAD_URL . "/{$user['id']}/{$year}/{$month}/thumb_{$final_filename}";
            } else {
                $thumbnail_path = null;
            }
        }
    }
}

// Update database
global $pdo;
try {
    $pdo->beginTransaction();
    
    // Verify user still exists before inserting
    $check_user_stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
    $check_user_stmt->execute([$user['id']]);
    $valid_user = $check_user_stmt->fetch();
    
    if (!$valid_user) {
        throw new Exception("User account no longer exists or is inactive");
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO uploads (user_id, file_name, file_path, file_url, compressed_path, compressed_url, thumbnail_path, thumbnail_url, size, mime_type, width, height, is_compressed, compression_ratio) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $is_compressed = !empty($compressed_url);
    $success = $stmt->execute([
        $user['id'],
        $file['name'],
        $file_path,
        $file_url,
        $compressed_path,
        $compressed_url,
        $thumbnail_path,
        $thumbnail_url,
        $file['size'],
        $mime_type,
        $width,
        $height,
        $is_compressed ? 1 : 0,
        $compression_ratio
    ]);
    
    if (!$success) {
        throw new Exception("Failed to insert file record into database");
    }
    
    $upload_id = $pdo->lastInsertId();
    
    // Update user storage
    $update_stmt = $pdo->prepare("UPDATE users SET used_space = used_space + ? WHERE id = ?");
    $update_success = $update_stmt->execute([$file['size'], $user['id']]);
    
    if (!$update_success) {
        throw new Exception("Failed to update user storage");
    }
    
    // Log usage
    log_usage($user['id'], 'upload', $file['size'], 'upload.php', $upload_id);
    
    $pdo->commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'file' => [
            'id' => $upload_id,
            'name' => $file['name'],
            'original_url' => $file_url,
            'compressed_url' => $compressed_url,
            'thumbnail_url' => $thumbnail_url,
            'size' => $file['size'],
            'mime_type' => $mime_type,
            'width' => $width,
            'height' => $height,
            'compression_ratio' => $compression_ratio
        ]
    ];
    
    json_response($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    // Clean up uploaded file
    if (file_exists($file_path)) unlink($file_path);
    if ($compressed_path && file_exists($compressed_path)) unlink($compressed_path);
    if ($thumbnail_path && file_exists($thumbnail_path)) unlink($thumbnail_path);
    
    error_log("Upload transaction failed: " . $e->getMessage());
    json_response(['error' => 'Upload failed: ' . $e->getMessage()], 500);
}
?>