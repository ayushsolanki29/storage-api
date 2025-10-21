<?php
require_once __DIR__ . '/../config/database.php';

function generate_api_key()
{
    return bin2hex(random_bytes(32));
}


/**
 * Format bytes to human readable format
 */
function format_bytes($bytes, $precision = 2)
{
    // Handle zero and negative values
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function get_user_by_api_key($api_key)
{
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
/**
 * Get user's current plan details
 */
function get_user_plan($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.* 
            FROM plans p 
            INNER JOIN users u ON p.id = u.plan_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            // Return free plan as default
            $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = 1");
            $stmt->execute();
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $plan;
    } catch (PDOException $e) {
        error_log("Get user plan error: " . $e->getMessage());
        return get_default_plan();
    }
}

/**
 * Get all active plans for display
 */
function get_all_plans_for_display() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM plans 
            WHERE is_active = 1 
            ORDER BY price ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get all plans error: " . $e->getMessage());
        return [];
    }
}

/**
 * Upgrade user plan (without payment)
 */
function upgrade_user_plan($user_id, $new_plan_id) {
    global $pdo;
    
    try {
        // Verify the plan exists and is active
        $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
        $stmt->execute([$new_plan_id]);
        $new_plan = $stmt->fetch();
        
        if (!$new_plan) {
            return [
                'success' => false,
                'message' => 'Invalid plan selected'
            ];
        }
        
        // Get current plan
        $current_plan = get_user_plan($user_id);
        
        // Update user plan
        $stmt = $pdo->prepare("UPDATE users SET plan_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_plan_id, $user_id]);
        
        // Log the plan change
        log_usage($user_id, 'billing', 0, "plan_change:{$current_plan['id']}->{$new_plan_id}");
        
        return [
            'success' => true,
            'message' => "Plan successfully changed to {$new_plan['name']}!"
        ];
        
    } catch (PDOException $e) {
        error_log("Upgrade user plan error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update plan. Please try again.'
        ];
    }
}

/**
 * Get default plan (Free plan)
 */
function get_default_plan() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM plans WHERE id = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Return hardcoded free plan as fallback
        return [
            'id' => 1,
            'name' => 'Free',
            'price' => '0.00',
            'storage_limit' => 1073741824, // 1GB
            'monthly_request_limit' => 1000,
            'bandwidth_limit' => 5368709120, // 5GB
            'max_file_size' => 5242880, // 5MB
            'allow_compression' => 1,
            'allow_thumbnails' => 1
        ];
    }
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
function delete_user_file($user_id, $file_id)
{
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
function delete_physical_files($file)
{
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
function clean_empty_directories($file)
{
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
 */ /**
 * Get analytics data for the admin dashboard
 */
function get_analytics_data($start_date, $end_date)
{
    global $pdo;

    $data = [
        'system_stats' => [],
        'user_activity' => [],
        'file_analytics' => [],
        'api_usage' => [],
        'top_users' => []
    ];

    try {
        // System Statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_users,
                SUM(used_space) as total_storage,
                (SELECT COUNT(*) FROM uploads WHERE created_at BETWEEN ? AND ?) as total_files,
                (SELECT COUNT(*) FROM usage_logs WHERE type = 'api_call' AND created_at BETWEEN ? AND ?) as total_requests,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM users WHERE status = 'suspended') as suspended_users,
                (SELECT COUNT(*) FROM usage_logs WHERE type = 'auth_fail' AND created_at BETWEEN ? AND ?) as failed_auth
            FROM users
        ");
        $stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        $system_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate growth percentages (simplified - you might want more sophisticated calculations)
        $system_stats['users_growth'] = 5.2; // Example growth
        $system_stats['files_growth'] = 12.8;
        $system_stats['storage_growth'] = 8.3;
        $system_stats['requests_growth'] = 15.7;

        // Plan distribution
        $stmt = $pdo->prepare("
            SELECT p.name, COUNT(u.id) as count
            FROM plans p
            LEFT JOIN users u ON p.id = u.plan_id
            GROUP BY p.id, p.name
            ORDER BY count DESC
        ");
        $stmt->execute();
        $system_stats['plan_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data['system_stats'] = $system_stats;

        // User Activity (last 30 days)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users
            FROM users 
            WHERE created_at BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->execute([$end_date, $end_date]);
        $user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format for chart
        $dates = [];
        $new_users = [];
        $active_users = []; // This would need more complex query for actual active users

        foreach ($user_activity as $activity) {
            $dates[] = date('M j', strtotime($activity['date']));
            $new_users[] = $activity['new_users'];
            $active_users[] = rand(5, 20); // Placeholder - implement actual active user tracking
        }

        $data['user_activity'] = [
            'dates' => $dates,
            'new_users' => $new_users,
            'active_users' => $active_users
        ];

        // File Analytics
        $stmt = $pdo->prepare("
            SELECT 
                AVG(size) as avg_file_size,
                COUNT(CASE WHEN is_compressed = '1' THEN 1 END) as compressed_files,
                COUNT(*) as total_files,
                SUM(CASE WHEN is_compressed = '1' THEN size * (compression_ratio/100) ELSE 0 END) as space_saved
            FROM uploads 
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $file_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $file_analytics = [
            'avg_file_size' => $file_stats['avg_file_size'] ?? 0,
            'compression_rate' => $file_stats['total_files'] > 0 ? ($file_stats['compressed_files'] / $file_stats['total_files']) * 100 : 0,
            'space_saved' => $file_stats['space_saved'] ?? 0,
            'type_labels' => ['Images', 'PDFs', 'Documents', 'Other'],
            'type_counts' => [65, 20, 10, 5] // Placeholder - implement actual file type counting
        ];

        $data['file_analytics'] = $file_analytics;

        // API Usage by Endpoint
        $stmt = $pdo->prepare("
            SELECT 
                endpoint,
                COUNT(*) as count
            FROM usage_logs 
            WHERE type = 'api_call' AND created_at BETWEEN ? AND ?
            GROUP BY endpoint 
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $api_endpoints = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate percentages
        $total_api_calls = array_sum(array_column($api_endpoints, 'count'));
        foreach ($api_endpoints as &$endpoint) {
            $endpoint['percentage'] = $total_api_calls > 0 ? ($endpoint['count'] / $total_api_calls) * 100 : 0;
        }

        $data['api_usage'] = $api_endpoints;

        // Top Users by Storage
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.name,
                u.email,
                u.used_space,
                COUNT(up.id) as file_count
            FROM users u
            LEFT JOIN uploads up ON u.id = up.user_id
            GROUP BY u.id, u.name, u.email, u.used_space
            ORDER BY u.used_space DESC
            LIMIT 5
        ");
        $stmt->execute();
        $data['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Analytics data error: " . $e->getMessage());
    }

    return $data;
}
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
/**
 * Get system logs with advanced filtering
 */
function get_system_logs($page = 1, $limit = 50, $filters = [])
{
    global $pdo;

    $offset = ($page - 1) * $limit;
    $where_conditions = [];
    $params = [];

    // Build WHERE conditions
    if (!empty($filters['search'])) {
        $where_conditions[] = "(ul.endpoint LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR ul.ip_address LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    }

    if (!empty($filters['type'])) {
        $where_conditions[] = "ul.type = ?";
        $params[] = $filters['type'];
    }

    if (!empty($filters['user_id'])) {
        $where_conditions[] = "ul.user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (!empty($filters['endpoint'])) {
        $where_conditions[] = "ul.endpoint = ?";
        $params[] = $filters['endpoint'];
    }

    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(ul.created_at) >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(ul.created_at) <= ?";
        $params[] = $filters['date_to'];
    }

    if (!empty($filters['ip_address'])) {
        $where_conditions[] = "ul.ip_address LIKE ?";
        $params[] = "%{$filters['ip_address']}%";
    }

    $where_sql = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get logs
    $sql = "
        SELECT 
            ul.*,
            u.name as user_name,
            u.email as user_email
        FROM usage_logs ul
        LEFT JOIN users u ON ul.user_id = u.id
        {$where_sql}
        ORDER BY ul.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM usage_logs ul
        LEFT JOIN users u ON ul.user_id = u.id
        {$where_sql}
    ";

    $stmt = $pdo->prepare($count_sql);
    $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total / $limit);

    // Get filter options
    $filter_options = [
        'types' => get_distinct_log_types(),
        'users' => get_active_users(),
        'endpoints' => get_distinct_endpoints()
    ];

    return [
        'logs' => $logs,
        'total' => $total,
        'total_pages' => $total_pages,
        'filter_options' => $filter_options
    ];
}

/**
 * Get distinct log types for filter
 */
function get_distinct_log_types()
{
    global $pdo;

    $stmt = $pdo->query("SELECT DISTINCT type FROM usage_logs ORDER BY type");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get active users for filter
 */
function get_active_users()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT id, name, email 
        FROM users 
        WHERE status = 'active' 
        ORDER BY name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get distinct endpoints for filter
 */
function get_distinct_endpoints()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT DISTINCT endpoint 
        FROM usage_logs 
        WHERE endpoint IS NOT NULL AND endpoint != '' 
        ORDER BY endpoint
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Delete selected logs
 */
function delete_logs($log_ids)
{
    global $pdo;

    // Convert string to array if needed
    if (is_string($log_ids)) {
        $log_ids = explode(',', $log_ids);
    }

    if (empty($log_ids)) {
        return ['success' => false, 'message' => 'No logs selected'];
    }

    try {
        // Ensure all values are integers and filter out empty values
        $log_ids = array_filter(array_map('intval', $log_ids));

        if (empty($log_ids)) {
            return ['success' => false, 'message' => 'No valid logs selected'];
        }

        $placeholders = str_repeat('?,', count($log_ids) - 1) . '?';
        $sql = "DELETE FROM usage_logs WHERE id IN ($placeholders)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($log_ids);

        $deleted_count = $stmt->rowCount();

        return [
            'success' => true,
            'message' => "Successfully deleted {$deleted_count} logs"
        ];
    } catch (PDOException $e) {
        error_log("Delete logs error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete logs: ' . $e->getMessage()];
    }
}

/**
 * Export logs to CSV
 */
function export_logs($log_ids)
{
    global $pdo;

    // Convert string to array if needed
    if (is_string($log_ids)) {
        $log_ids = explode(',', $log_ids);
    }

    if (empty($log_ids)) {
        return ['success' => false, 'message' => 'No logs selected'];
    }

    try {
        // Ensure all values are integers and filter out empty values
        $log_ids = array_filter(array_map('intval', $log_ids));

        if (empty($log_ids)) {
            return ['success' => false, 'message' => 'No valid logs selected'];
        }

        $placeholders = str_repeat('?,', count($log_ids) - 1) . '?';
        $sql = "
            SELECT 
                ul.id,
                ul.type,
                ul.endpoint,
                ul.bytes,
                ul.ip_address,
                ul.user_agent,
                ul.created_at,
                u.name as user_name,
                u.email as user_email
            FROM usage_logs ul
            LEFT JOIN users u ON ul.user_id = u.id
            WHERE ul.id IN ($placeholders)
            ORDER BY ul.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($log_ids);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($logs)) {
            return ['success' => false, 'message' => 'No logs found to export'];
        }

        // Generate CSV
        $output = fopen('php://output', 'w');
        ob_start();

        // CSV header
        fputcsv($output, [
            'ID',
            'Type',
            'Endpoint',
            'User',
            'Email',
            'Bytes',
            'IP Address',
            'User Agent',
            'Timestamp'
        ]);

        // CSV data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['type'],
                $log['endpoint'] ?? 'N/A',
                $log['user_name'] ?? 'System',
                $log['user_email'] ?? 'N/A',
                $log['bytes'] ?? 0,
                $log['ip_address'] ?? 'N/A',
                $log['user_agent'] ?? 'N/A',
                $log['created_at']
            ]);
        }

        $csv_data = ob_get_clean();
        fclose($output);

        return [
            'success' => true,
            'data' => $csv_data,
            'message' => 'Logs exported successfully'
        ];
    } catch (PDOException $e) {
        error_log("Export logs error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to export logs: ' . $e->getMessage()];
    }
}
