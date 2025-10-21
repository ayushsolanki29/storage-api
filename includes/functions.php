<?php
require_once __DIR__ . '/../config/database.php';

function generate_api_key()
{
    return bin2hex(random_bytes(32));
}


function format_bytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function get_user_by_api_key($api_key) {
    global $pdo;
    
    if (empty($api_key)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, p.name as plan_name, p.storage_limit, p.monthly_request_limit, p.bandwidth_limit, 
                   p.max_file_size, p.allow_compression, p.allow_thumbnails 
            FROM users u 
            LEFT JOIN plans p ON u.plan_id = p.id 
            WHERE u.api_key = ? AND u.status = 'active'
        ");
        $stmt->execute([$api_key]);
        $user = $stmt->fetch();
        
        // DEBUG
        error_log("User query result: " . ($user ? 'FOUND user ID: ' . $user['id'] : 'NOT FOUND'));
        
        return $user;
    } catch (PDOException $e) {
        error_log("Error in get_user_by_api_key: " . $e->getMessage());
        return false;
    }
}

function log_usage($user_id, $type, $bytes = 0, $endpoint = null, $upload_id = null)
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, upload_id, type, bytes, endpoint, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $upload_id,
        $type,
        $bytes,
        $endpoint,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    // Update user counters for monthly requests and bandwidth
    if ($type === 'api_call') {
        $pdo->prepare("UPDATE users SET monthly_requests = monthly_requests + 1 WHERE id = ?")->execute([$user_id]);
    }
    if ($bytes > 0) {
        $pdo->prepare("UPDATE users SET monthly_bandwidth = monthly_bandwidth + ? WHERE id = ?")->execute([$bytes, $user_id]);
    }
}

function check_rate_limit($user_id)
{
    global $pdo;

    $window_start = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as request_count 
        FROM usage_logs 
        WHERE user_id = ? AND type = 'api_call' AND created_at > ?
    ");
    $stmt->execute([$user_id, $window_start]);
    $result = $stmt->fetch();

    return $result['request_count'] < RATE_LIMIT_REQUESTS;
}

function compress_image($source_path, $destination_path, $quality = COMPRESSION_QUALITY)
{
    $image_info = getimagesize($source_path);
    $mime_type = $image_info['mime'];

    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $success = imagejpeg($image, $destination_path, $quality);
            break;
        case 'image/png':
            $quality = floor(($quality / 100) * 9); // Convert to PNG compression level
            $success = imagepng($image, $destination_path, $quality);
            break;
        case 'image/gif':
            $success = imagegif($image, $destination_path);
            break;
        case 'image/webp':
            $success = imagewebp($image, $destination_path, $quality);
            break;
    }

    imagedestroy($image);
    return $success;
}

function create_thumbnail($source_path, $destination_path, $max_width = THUMBNAIL_WIDTH, $max_height = THUMBNAIL_HEIGHT)
{
    $image_info = getimagesize($source_path);
    $mime_type = $image_info['mime'];

    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    $orig_width = imagesx($image);
    $orig_height = imagesy($image);

    // Calculate thumbnail dimensions
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    $thumb_width = (int)($orig_width * $ratio);
    $thumb_height = (int)($orig_height * $ratio);

    // Create thumbnail
    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumb_width, $thumb_height, $orig_width, $orig_height);

    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $success = imagejpeg($thumb, $destination_path, 85);
            break;
        case 'image/png':
            $success = imagepng($thumb, $destination_path, 8);
            break;
        case 'image/gif':
            $success = imagegif($thumb, $destination_path);
            break;
        case 'image/webp':
            $success = imagewebp($thumb, $destination_path, 85);
            break;
    }

    imagedestroy($image);
    imagedestroy($thumb);

    return $success ? ['width' => $thumb_width, 'height' => $thumb_height] : false;
}

function json_response($data, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
function generate_remember_token()
{
    return bin2hex(random_bytes(32));
}

/**
 * Delete file by ID (for use in dashboard)
 */
function delete_user_file($user_id, $file_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get file details
        $stmt = $pdo->prepare("
            SELECT * FROM uploads 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$file_id, $user_id]);
        $file = $stmt->fetch();
        
        if (!$file) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Delete physical files
        $files_deleted = delete_physical_files($file);
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->execute([$file_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to delete file record'];
        }
        
        // Update user storage usage
        $file_size = $file['size'];
        $update_stmt = $pdo->prepare("UPDATE users SET used_space = GREATEST(0, used_space - ?) WHERE id = ?");
        $update_stmt->execute([$file_size, $user_id]);
        
        // Log the deletion
        log_usage($user_id, 'api_call', 0, 'delete_file', $file_id);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'File deleted successfully',
            'file' => [
                'id' => (int)$file_id,
                'name' => $file['file_name'],
                'size' => (int)$file_size,
                'files_deleted' => $files_deleted
            ]
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete file error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
    }
}

/**
 * Delete physical files from server
 */
function delete_physical_files($file) {
    $files_deleted = [];
    
    // Delete main file
    if (!empty($file['file_path']) && file_exists($file['file_path'])) {
        if (unlink($file['file_path'])) {
            $files_deleted[] = 'original';
        } else {
            error_log("Failed to delete file: " . $file['file_path']);
        }
    }
    
    // Delete compressed file
    if (!empty($file['compressed_path']) && file_exists($file['compressed_path'])) {
        if (unlink($file['compressed_path'])) {
            $files_deleted[] = 'compressed';
        } else {
            error_log("Failed to delete compressed file: " . $file['compressed_path']);
        }
    }
    
    // Delete thumbnail file
    if (!empty($file['thumbnail_path']) && file_exists($file['thumbnail_path'])) {
        if (unlink($file['thumbnail_path'])) {
            $files_deleted[] = 'thumbnail';
        } else {
            error_log("Failed to delete thumbnail: " . $file['thumbnail_path']);
        }
    }
    
    // Clean up empty directories
    clean_empty_directories($file);
    
    return $files_deleted;
}

/**
 * Clean up empty directories after file deletion
 */
function clean_empty_directories($file) {
    if (empty($file['file_path'])) {
        return;
    }
    
    $file_path = $file['file_path'];
    $upload_dir = dirname($file_path);
    
    // Check if directory is empty (only . and ..)
    if (is_dir($upload_dir)) {
        $files_in_dir = scandir($upload_dir);
        if (count($files_in_dir) == 2) {
            @rmdir($upload_dir);
            
            // Also check and remove parent directories if empty
            $parent_dir = dirname($upload_dir);
            if (is_dir($parent_dir)) {
                $files_in_parent = scandir($parent_dir);
                if (count($files_in_parent) == 2) {
                    @rmdir($parent_dir);
                    
                    $grandparent_dir = dirname($parent_dir);
                    if (is_dir($grandparent_dir)) {
                        $files_in_grandparent = scandir($grandparent_dir);
                        if (count($files_in_grandparent) == 2) {
                            @rmdir($grandparent_dir);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Set remember token for user
 */
function set_remember_token($user_id, $token)
{
    global $pdo;

    $hashed_token = hash('sha256', $token);
    $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

    // Delete existing tokens for this user
    $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$user_id]);

    // Insert new token
    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $hashed_token, $expires_at]);
}

/**
 * Validate remember token
 */
function validate_remember_token($token)
{
    global $pdo;

    $hashed_token = hash('sha256', $token);
    $stmt = $pdo->prepare("
        SELECT u.* 
        FROM users u 
        INNER JOIN remember_tokens rt ON u.id = rt.user_id 
        WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute([$hashed_token]);
    return $stmt->fetch();
}

/**
 * Delete remember token (for logout)
 */
function delete_remember_token($user_id, $token = null)
{
    global $pdo;

    if ($token) {
        $hashed_token = hash('sha256', $token);
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$user_id, $hashed_token]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
}
