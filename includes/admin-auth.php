<?php
session_start();
require_once __DIR__ . '/functions.php';

/**
 * Check if current user is an admin
 */
function is_admin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    return $_SESSION['user_role'] === 'admin';
}

/**
 * Admin login function - FIXED VERSION
 */
function admin_login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, p.name as plan_name 
        FROM users u 
        LEFT JOIN plans p ON u.plan_id = p.id 
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Check if user is admin - FIXED: Check role column
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            return ['success' => false, 'message' => 'Administrator privileges required'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role']; // Make sure this is set
        $_SESSION['is_admin'] = true;
        
        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        
        // Log admin login
        log_usage($user['id'], 'api_call', 0, 'admin_login');
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'message' => 'Invalid email or password'];
}


/**
 * Get admin user data with additional admin permissions
 */
function get_admin_user() {
    if (!is_admin()) {
        return null;
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, p.name as plan_name, p.storage_limit, p.monthly_request_limit, p.bandwidth_limit,
               (SELECT COUNT(*) FROM users) as total_users,
               (SELECT COUNT(*) FROM uploads) as total_files,
               (SELECT SUM(used_space) FROM users) as total_storage_used,
               (SELECT SUM(monthly_bandwidth) FROM users) as total_bandwidth_used
        FROM users u 
        LEFT JOIN plans p ON u.plan_id = p.id 
        WHERE u.id = ? AND u.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $user['is_admin'] = true;
        $user['admin_permissions'] = get_admin_permissions($_SESSION['user_id']);
    }
    
    return $user;
}

/**
 * Get admin permissions for a user
 */
function get_admin_permissions($user_id) {
    global $pdo;
    
    // In a real application, you might have a separate admin_permissions table
    // For now, we'll return default admin permissions
    return [
        'can_manage_users' => true,
        'can_manage_plans' => true,
        'can_view_analytics' => true,
        'can_manage_settings' => true,
        'can_view_logs' => true,
        'can_suspend_users' => true,
        'can_reset_api_keys' => true
    ];
}

/**
 * Require admin authentication - redirect if not admin
 */
function require_admin_auth() {
    if (!is_admin()) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Set error message
        $_SESSION['admin_auth_error'] = 'You need administrator privileges to access this page.';
        
        header('Location: ../login.php?errr');
        exit;
    }
}



function get_all_plans() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT * FROM plans 
        ORDER BY price ASC, created_at DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Get user counts per plan
 */
function get_plan_user_counts() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT plan_id, COUNT(*) as user_count 
        FROM users 
        WHERE status = 'active' 
        GROUP BY plan_id
    ");
    
    $counts = [];
    while ($row = $stmt->fetch()) {
        $counts[$row['plan_id']] = $row['user_count'];
    }
    
    return $counts;
}

/**
 * Create a new plan
 */
function create_plan($plan_data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO plans (name, price, storage_limit, monthly_request_limit, bandwidth_limit, 
                             max_file_size, allow_compression, allow_thumbnails, thumbnail_sizes, 
                             features, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $plan_data['name'],
            $plan_data['price'],
            $plan_data['storage_limit'],
            $plan_data['monthly_request_limit'],
            $plan_data['bandwidth_limit'],
            $plan_data['max_file_size'],
            $plan_data['allow_compression'],
            $plan_data['allow_thumbnails'],
            $plan_data['thumbnail_sizes'],
            $plan_data['features'],
            $plan_data['is_active']
        ]);
        
        if ($success) {
            log_usage($_SESSION['user_id'], 'api_call', 0, 'create_plan', $pdo->lastInsertId());
            return ['success' => true, 'message' => 'Plan created successfully'];
        } else {
            throw new Exception("Failed to create plan");
        }
        
    } catch (Exception $e) {
        error_log("Create plan error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create plan: ' . $e->getMessage()];
    }
}

/**
 * Update an existing plan
 */
function update_plan($plan_id, $plan_data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE plans SET 
                name = ?, price = ?, storage_limit = ?, monthly_request_limit = ?, 
                bandwidth_limit = ?, max_file_size = ?, allow_compression = ?, 
                allow_thumbnails = ?, thumbnail_sizes = ?, features = ?, is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $success = $stmt->execute([
            $plan_data['name'],
            $plan_data['price'],
            $plan_data['storage_limit'],
            $plan_data['monthly_request_limit'],
            $plan_data['bandwidth_limit'],
            $plan_data['max_file_size'],
            $plan_data['allow_compression'],
            $plan_data['allow_thumbnails'],
            $plan_data['thumbnail_sizes'],
            $plan_data['features'],
            $plan_data['is_active'],
            $plan_id
        ]);
        
        if ($success) {
            log_usage($_SESSION['user_id'], 'api_call', 0, 'update_plan', $plan_id);
            return ['success' => true, 'message' => 'Plan updated successfully'];
        } else {
            throw new Exception("Failed to update plan");
        }
        
    } catch (Exception $e) {
        error_log("Update plan error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update plan: ' . $e->getMessage()];
    }
}

/**
 * Delete a plan (only if no users are using it)
 */
function delete_plan($plan_id) {
    global $pdo;
    
    try {
        // Check if any users are using this plan
        $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE plan_id = ?");
        $stmt->execute([$plan_id]);
        $user_count = $stmt->fetch()['user_count'];
        
        if ($user_count > 0) {
            return ['success' => false, 'message' => 'Cannot delete plan with active users'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
        $success = $stmt->execute([$plan_id]);
        
        if ($success) {
            log_usage($_SESSION['user_id'], 'api_call', 0, 'delete_plan', $plan_id);
            return ['success' => true, 'message' => 'Plan deleted successfully'];
        } else {
            throw new Exception("Failed to delete plan");
        }
        
    } catch (Exception $e) {
        error_log("Delete plan error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete plan: ' . $e->getMessage()];
    }
}

/**
 * Toggle plan status
 */
function toggle_plan_status($plan_id, $is_active) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE plans SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$is_active, $plan_id]);
        
        if ($success) {
            $action = $is_active ? 'activate' : 'deactivate';
            log_usage($_SESSION['user_id'], 'api_call', 0, $action . '_plan', $plan_id);
            return ['success' => true, 'message' => 'Plan ' . ($is_active ? 'activated' : 'deactivated') . ' successfully'];
        } else {
            throw new Exception("Failed to update plan status");
        }
        
    } catch (Exception $e) {
        error_log("Toggle plan status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update plan status: ' . $e->getMessage()];
    }
}

/**
 * Get admin dashboard statistics
 */
function get_admin_stats() {
    global $pdo;
    
    $stats = [];
    
    // Basic counts
    $stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
            (SELECT COUNT(*) FROM users WHERE status = 'suspended') as suspended_users,
            (SELECT COUNT(*) FROM uploads) as total_files,
            (SELECT SUM(size) FROM uploads) as total_storage_used,
            (SELECT SUM(monthly_bandwidth) FROM users) as total_bandwidth_used,
            (SELECT SUM(monthly_requests) FROM users) as total_requests_used,
            (SELECT COUNT(*) FROM plans) as total_plans
    ");
    $stats = $stmt->fetch();
    
    // Recent registrations (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) as recent_registrations 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['recent_registrations'] = $stmt->fetch()['recent_registrations'];
    
    // Storage usage by plan
    $stmt = $pdo->query("
        SELECT p.name as plan_name, 
               COUNT(u.id) as user_count,
               SUM(us.used_space) as total_used_space
        FROM plans p 
        LEFT JOIN users u ON p.id = u.plan_id 
        LEFT JOIN (SELECT user_id, SUM(size) as used_space FROM uploads GROUP BY user_id) us ON u.id = us.user_id
        GROUP BY p.id, p.name
    ");
    $stats['storage_by_plan'] = $stmt->fetchAll();
    
    // Recent activity
    $stmt = $pdo->query("
        SELECT ul.type, COUNT(*) as count 
        FROM usage_logs ul 
        WHERE ul.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ul.type 
        ORDER BY count DESC
    ");
    $stats['recent_activity'] = $stmt->fetchAll();
    
    return $stats;
}

/**
 * Update user status (active/suspended)
 */
function update_user_status($user_id, $status) {
    global $pdo;
    
    if (!in_array($status, ['active', 'suspended'])) {
        return ['success' => false, 'message' => 'Invalid status'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        
        // Log the action
        log_usage($_SESSION['user_id'], 'api_call', 0, "update_user_status_$status", $user_id);
        
        return ['success' => true, 'message' => "User status updated to $status"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to update user status: ' . $e->getMessage()];
    }
}

/**
 * Reset user API key
 */
function reset_user_api_key($user_id) {
    global $pdo;
    
    try {
        $new_api_key = generate_api_key();
        
        $stmt = $pdo->prepare("UPDATE users SET api_key = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_api_key, $user_id]);
        
        // Log the action
        log_usage($_SESSION['user_id'], 'api_call', 0, 'reset_api_key', $user_id);
        
        return ['success' => true, 'api_key' => $new_api_key];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to reset API key: ' . $e->getMessage()];
    }
}

/**
 * Get system logs for admin
 */
function get_system_logs($type = null, $limit = 100, $page = 1) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $params = [];
    $where_conditions = [];
    
    if ($type) {
        $where_conditions[] = "type = ?";
        $params[] = $type;
    }
    
    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get logs
    $stmt = $pdo->prepare("
        SELECT ul.*, u.name as user_name, u.email as user_email 
        FROM usage_logs ul 
        LEFT JOIN users u ON ul.user_id = u.id 
        $where_sql
        ORDER BY ul.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Get total count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM usage_logs ul 
        $where_sql
    ");
    
    $count_params = array_slice($params, 0, -2);
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetch()['total'];
    
    return [
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ];
}

/**
 * Get file upload statistics
 */
function get_file_statistics($period = '30days') {
    global $pdo;
    
    $date_condition = '';
    switch ($period) {
        case '7days':
            $date_condition = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case '30days':
            $date_condition = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
        case '90days':
            $date_condition = 'WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
            break;
        default:
            $date_condition = '';
    }
    
    // Files by type
    $stmt = $pdo->query("
        SELECT 
            mime_type,
            COUNT(*) as file_count,
            SUM(size) as total_size
        FROM uploads 
        $date_condition
        GROUP BY mime_type 
        ORDER BY file_count DESC
    ");
    $files_by_type = $stmt->fetchAll();
    
    // Daily upload stats
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as upload_date,
            COUNT(*) as file_count,
            SUM(size) as total_size
        FROM uploads 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY upload_date DESC
    ");
    $daily_stats = $stmt->fetchAll();
    
    // Top users by storage
    $stmt = $pdo->query("
        SELECT 
            u.name,
            u.email,
            COUNT(up.id) as file_count,
            SUM(up.size) as total_size
        FROM users u 
        LEFT JOIN uploads up ON u.id = up.user_id 
        GROUP BY u.id, u.name, u.email 
        ORDER BY total_size DESC 
        LIMIT 10
    ");
    $top_users = $stmt->fetchAll();
    
    return [
        'files_by_type' => $files_by_type,
        'daily_stats' => $daily_stats,
        'top_users' => $top_users
    ];
}

/**
 * Check if admin has specific permission
 */
function admin_has_permission($permission) {
    $admin_user = get_admin_user();
    
    if (!$admin_user || !isset($admin_user['admin_permissions'][$permission])) {
        return false;
    }
    
    return $admin_user['admin_permissions'][$permission];
}

/**
 * Require specific admin permission
 */
function require_admin_permission($permission) {
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        die('Access denied. You do not have permission to perform this action.');
    }
}
function get_all_users($page = 1, $limit = 20, $search = '', $status_filter = '') {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $params = [];
    $where_conditions = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
    }
    
    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get users
    $stmt = $pdo->prepare("
        SELECT u.*, p.name as plan_name, 
               (SELECT COUNT(*) FROM uploads WHERE user_id = u.id) as file_count,
               (SELECT SUM(size) FROM uploads WHERE user_id = u.id) as total_upload_size
        FROM users u 
        LEFT JOIN plans p ON u.plan_id = p.id 
        $where_sql
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get total count
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM users u 
        $where_sql
    ");
    
    $count_params = array_slice($params, 0, -2); // Remove limit and offset
    $count_stmt->execute($count_params);
    $total = $count_stmt->fetch()['total'];
    
    return [
        'users' => $users,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ];
}

/**
 * Delete user and all their files
 */
function delete_user($user_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get user's files for cleanup
        $stmt = $pdo->prepare("SELECT * FROM uploads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $files = $stmt->fetchAll();
        
        // Delete physical files
        foreach ($files as $file) {
            delete_physical_files($file);
        }
        
        // Delete user record (this will cascade delete uploads due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("User not found");
        }
        
        // Log the deletion
        log_usage($_SESSION['user_id'], 'api_call', 0, 'delete_user', $user_id);
        
        $pdo->commit();
        
        return ['success' => true, 'message' => 'User deleted successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()];
    }
}

// Auto-check admin auth for admin pages
// if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
//     require_admin_auth();
// }
?>