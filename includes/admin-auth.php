<?php
session_start();
require_once __DIR__ . '/functions.php';

/**
 * Check if current user is an admin
 */
function is_admin()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }

    return $_SESSION['user_role'] === 'admin';
}

/**
 * Admin login function - FIXED VERSION
 */
function admin_login($email, $password)
{
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
function get_admin_user()
{
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
function get_admin_permissions($user_id)
{
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
function require_admin_auth()
{
    if (!is_admin()) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];

        // Set error message
        $_SESSION['admin_auth_error'] = 'You need administrator privileges to access this page.';

        header('Location: ../login.php?errr');
        exit;
    }
}



function get_all_plans()
{
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
function get_plan_user_counts()
{
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
function create_plan($plan_data)
{
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
function update_plan($plan_id, $plan_data)
{
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
function delete_plan($plan_id)
{
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
function toggle_plan_status($plan_id, $is_active)
{
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
function get_admin_stats()
{
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
function update_user_status($user_id, $status)
{
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
function reset_user_api_key($user_id)
{
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
 * Get all admin settings grouped by category
 */

function get_admin_settings()
{
    global $pdo;

    $settings = [
        'system' => [],
        'file' => [],
        'api' => [],
        'email' => []
    ];

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM admin_settings");
        $all_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_settings as $setting) {
            $value = $setting['setting_value'];
            $key = $setting['setting_key'];

            // Convert value based on type
            switch ($setting['setting_type']) {
                case 'integer':
                    $value = intval($value);
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'json':
                    // Only decode if it's a string, otherwise use as-is
                    if (is_string($value)) {
                        $value = json_decode($value, true) ?: [];
                    }
                    break;
                    // string type doesn't need conversion
            }

            // Categorize settings
            if (
                strpos($key, 'smtp_') === 0 ||
                strpos($key, 'from_') === 0
            ) {
                $settings['email'][$key] = $value;
            } elseif (
                strpos($key, 'api_') === 0 ||
                strpos($key, 'cors_') === 0 ||
                strpos($key, 'allowed_origins') === 0
            ) {
                $settings['api'][$key] = $value;
            } elseif (
                strpos($key, 'max_file_') === 0 ||
                strpos($key, 'allowed_file_') === 0 ||
                strpos($key, 'enable_') === 0 ||
                strpos($key, 'keep_') === 0
            ) {
                $settings['file'][$key] = $value;
            } else {
                $settings['system'][$key] = $value;
            }
        }
    } catch (PDOException $e) {
        error_log("Get admin settings error: " . $e->getMessage());
    }

    return $settings;
}
/**
 * Update system settings
 */
function update_system_settings($data)
{
    global $pdo;

    $updates = [
        'system_name' => ['value' => $data['system_name'] ?? 'Mini Cloudinary', 'type' => 'string'],
        'user_registration' => ['value' => $data['user_registration'] ?? 'enabled', 'type' => 'string'],
        'default_user_plan' => ['value' => intval($data['default_user_plan'] ?? 1), 'type' => 'integer'],
        'session_timeout' => ['value' => intval($data['session_timeout'] ?? 30), 'type' => 'integer'],
        'min_password_length' => ['value' => intval($data['min_password_length'] ?? 8), 'type' => 'integer'],
        'api_rate_limit' => ['value' => intval($data['api_rate_limit'] ?? 60), 'type' => 'integer'],
        'max_login_attempts' => ['value' => intval($data['max_login_attempts'] ?? 5), 'type' => 'integer']
    ];

    return save_settings($updates, 'System settings updated successfully');
}

/**
 * Update file settings
 */
function update_file_settings($data) {
    global $pdo;
    
    $allowed_types = $data['allowed_file_types'] ?? [];
    // Ensure it's always an array
    if (!is_array($allowed_types)) {
        $allowed_types = [];
    }
    
    $updates = [
        'max_file_size_default' => ['value' => intval($data['max_file_size_default'] ?? 5242880), 'type' => 'integer'],
        'allowed_file_types' => ['value' => json_encode($allowed_types), 'type' => 'json'],
        'enable_compression' => ['value' => isset($data['enable_compression']) ? 1 : 0, 'type' => 'boolean'],
        'enable_thumbnails' => ['value' => isset($data['enable_thumbnails']) ? 1 : 0, 'type' => 'boolean'],
        'keep_original' => ['value' => isset($data['keep_original']) ? 1 : 0, 'type' => 'boolean']
    ];
    
    return save_settings($updates, 'File settings updated successfully');
}

/**
 * Update API settings
 */
function update_api_settings($data)
{
    global $pdo;

    $updates = [
        'api_base_url' => ['value' => $data['api_base_url'] ?? '', 'type' => 'string'],
        'api_version' => ['value' => $data['api_version'] ?? 'v1', 'type' => 'string'],
        'cors_enabled' => ['value' => isset($data['cors_enabled']) ? 1 : 0, 'type' => 'boolean'],
        'allowed_origins' => ['value' => $data['allowed_origins'] ?? '', 'type' => 'string']
    ];

    return save_settings($updates, 'API settings updated successfully');
}

/**
 * Update email settings
 */
function update_email_settings($data)
{
    global $pdo;

    $updates = [
        'smtp_host' => ['value' => $data['smtp_host'] ?? '', 'type' => 'string'],
        'smtp_port' => ['value' => intval($data['smtp_port'] ?? 587), 'type' => 'integer'],
        'smtp_username' => ['value' => $data['smtp_username'] ?? '', 'type' => 'string'],
        'from_email' => ['value' => $data['from_email'] ?? '', 'type' => 'string'],
        'from_name' => ['value' => $data['from_name'] ?? '', 'type' => 'string']
    ];

    // Only update password if provided
    if (!empty($data['smtp_password'])) {
        $updates['smtp_password'] = ['value' => $data['smtp_password'], 'type' => 'string'];
    }

    return save_settings($updates, 'Email settings updated successfully');
}

/**
 * Save settings to database
 */
function save_settings($updates, $success_message)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        foreach ($updates as $key => $setting) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_settings (setting_key, setting_value, setting_type, updated_by) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_type = VALUES(setting_type),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP
            ");

            $stmt->execute([
                $key,
                $setting['value'],
                $setting['type'],
                $_SESSION['user_id']
            ]);
        }

        $pdo->commit();
        return ['success' => true, 'message' => $success_message];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Save settings error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save settings: ' . $e->getMessage()];
    }
}

/**
 * Test email settings
 */
function test_email_settings($data)
{
    // Implement email testing logic here
    return ['success' => true, 'message' => 'Email test functionality not implemented yet'];
}

/**
 * Clear system cache
 */
function clear_system_cache()
{
    // Implement cache clearing logic here
    return ['success' => true, 'message' => 'Cache cleared successfully'];
}

/**
 * Reset settings to default
 */
function reset_settings_to_default()
{
    global $pdo;

    try {
        $pdo->exec("DELETE FROM admin_settings");

        // Re-insert default settings
        $defaults = [
            ['system_name', 'Mini Cloudinary', 'string'],
            ['max_file_size_default', '5242880', 'integer'],
            ['allowed_file_types', '["image/jpeg","image/png","image/gif","image/webp","application/pdf"]', 'json'],
            ['user_registration', 'enabled', 'string'],
            ['default_user_plan', '1', 'integer']
        ];

        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?)
        ");

        foreach ($defaults as $default) {
            $stmt->execute($default);
        }

        return ['success' => true, 'message' => 'Settings reset to default values'];
    } catch (PDOException $e) {
        error_log("Reset settings error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset settings: ' . $e->getMessage()];
    }
}



/**
 * Get file upload statistics
 */
function get_file_statistics($period = '30days')
{
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
function admin_has_permission($permission)
{
    $admin_user = get_admin_user();

    if (!$admin_user || !isset($admin_user['admin_permissions'][$permission])) {
        return false;
    }

    return $admin_user['admin_permissions'][$permission];
}

/**
 * Require specific admin permission
 */
function require_admin_permission($permission)
{
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        die('Access denied. You do not have permission to perform this action.');
    }
}
function get_all_users($page = 1, $limit = 20, $search = '', $status_filter = '')
{
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
function delete_user($user_id)
{
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
/**
 * Get detailed user information
 */
function get_user_details($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.*,
                p.name as plan_name,
                p.price as plan_price,
                p.storage_limit,
                p.monthly_request_limit,
                p.bandwidth_limit,
                p.features as plan_features
            FROM users u
            LEFT JOIN plans p ON u.plan_id = p.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user details error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user files with filtering and pagination
 */
function get_user_files($user_id, $page = 1, $limit = 20, $filters = []) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $where_conditions = ["u.user_id = ?"];
    $params = [$user_id];
    
    // Build WHERE conditions
    if (!empty($filters['file_type'])) {
        switch ($filters['file_type']) {
            case 'image':
                $where_conditions[] = "u.mime_type LIKE 'image/%'";
                break;
            case 'pdf':
                $where_conditions[] = "u.mime_type = 'application/pdf'";
                break;
            case 'document':
                $where_conditions[] = "u.mime_type LIKE 'application/%' AND u.mime_type != 'application/pdf'";
                break;
        }
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(u.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(u.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    $where_sql = implode(" AND ", $where_conditions);
    
    // Get files
    $sql = "
        SELECT u.*
        FROM uploads u
        WHERE {$where_sql}
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total
        FROM uploads u
        WHERE {$where_sql}
    ";
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total / $limit);
    
    return [
        'files' => $files,
        'total' => $total,
        'total_pages' => $total_pages
    ];
}

/**
 * Get user usage statistics
 */
function get_user_usage_stats($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_files,
                COALESCE(SUM(size), 0) as total_storage,
                COALESCE(AVG(size), 0) as avg_file_size,
                COUNT(CASE WHEN created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 END) as files_this_month,
                (SELECT COUNT(*) FROM usage_logs WHERE user_id = ? AND type = 'api_call' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as api_calls_this_month
            FROM uploads 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user usage stats error: " . $e->getMessage());
        return [
            'total_files' => 0,
            'total_storage' => 0,
            'avg_file_size' => 0,
            'files_this_month' => 0,
            'api_calls_this_month' => 0
        ];
    }
}

/**
 * Update user plan
 */
function update_user_plan($user_id, $new_plan_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET plan_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_plan_id, $user_id]);
        
        // Log the action
        log_admin_action("Updated user #{$user_id} plan to #{$new_plan_id}");
        
        return [
            'success' => true,
            'message' => 'User plan updated successfully'
        ];
    } catch (PDOException $e) {
        error_log("Update user plan error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update user plan'
        ];
    }
}

/**
 * Delete all user files
 */
function delete_all_user_files($user_id) {
    global $pdo;
    
    try {
        // Get user files to delete from disk
        $stmt = $pdo->prepare("SELECT file_path, compressed_path, thumbnail_path FROM uploads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Delete files from disk
        foreach ($files as $file) {
            if ($file['file_path'] && file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            if ($file['compressed_path'] && file_exists($file['compressed_path'])) {
                unlink($file['compressed_path']);
            }
            if ($file['thumbnail_path'] && file_exists($file['thumbnail_path'])) {
                unlink($file['thumbnail_path']);
            }
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM uploads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Update user storage usage
        $stmt = $pdo->prepare("UPDATE users SET used_space = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log the action
        log_admin_action("Deleted all files for user #{$user_id}");
        
        return [
            'success' => true,
            'message' => 'All user files deleted successfully'
        ];
    } catch (PDOException $e) {
        error_log("Delete user files error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to delete user files'
        ];
    }
}

/**
 * Log admin actions
 */
function log_admin_action($action) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO usage_logs (user_id, type, endpoint, ip_address, user_agent, created_at)
            VALUES (?, 'api_call', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Admin'
        ]);
    } catch (PDOException $e) {
        error_log("Log admin action error: " . $e->getMessage());
    }
}/**
 * Get user monthly usage statistics
 */
function get_user_monthly_usage($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as file_count,
                COALESCE(SUM(size), 0) as storage_used,
                (SELECT COUNT(*) FROM usage_logs WHERE user_id = ? AND type = 'api_call' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(u.created_at, '%Y-%m')) as api_calls
            FROM uploads u
            WHERE user_id = ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get user monthly usage error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update user information
 */
function update_user_info($user_id, $data) {
    global $pdo;
    
    try {
        $updates = [];
        $params = [];
        
        if (!empty($data['name'])) {
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
        }
        
        if (!empty($data['email'])) {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $user_id]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Email already exists for another user'
                ];
            }
            
            $updates[] = "email = ?";
            $params[] = trim($data['email']);
        }
        
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            return [
                'success' => false,
                'message' => 'No changes provided'
            ];
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $user_id;
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log the action
        log_admin_action("Updated user #{$user_id} information");
        
        return [
            'success' => true,
            'message' => 'User information updated successfully'
        ];
        
    } catch (PDOException $e) {
        error_log("Update user info error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to update user information'
        ];
    }
}



