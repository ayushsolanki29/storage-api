<?php
require_once '../includes/admin-auth.php';
if (!is_admin()) {
    header('Location: redirect.php');
    exit;
}


$admin_user = get_admin_user();
$stats = get_admin_stats();

// Get recent users
global $pdo;
$stmt = $pdo->prepare("
    SELECT u.*, p.name as plan_name 
    FROM users u 
    LEFT JOIN plans p ON u.plan_id = p.id 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Get recent uploads
$stmt = $pdo->prepare("
    SELECT up.*, u.name as user_name, u.email as user_email 
    FROM uploads up 
    LEFT JOIN users u ON up.user_id = u.id 
    ORDER BY up.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_uploads = $stmt->fetchAll();

// Get system logs
$stmt = $pdo->prepare("
    SELECT ul.*, u.name as user_name 
    FROM usage_logs ul 
    LEFT JOIN users u ON ul.user_id = u.id 
    ORDER BY ul.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$system_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-xl font-bold text-gray-900">Mini Cloudinary</span>
                        <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">Admin</span>
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="text-gray-600 hover:text-blue-600 transition duration-150">
                        User Dashboard
                    </a>
                    <div class="relative">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 text-sm font-medium">A</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($admin_user['name']) ?></span>
                        </div>
                    </div>
                    <a href="../logout.php" class="text-gray-600 hover:text-red-600 transition duration-150">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-sm min-h-screen">
            <nav class="mt-8 px-4 space-y-2">
                <a href="dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <?php if (admin_has_permission('can_manage_users')): ?>
                <a href="users.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg transition duration-150">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    User Management
                </a>
                <?php endif; ?>
                <?php if (admin_has_permission('can_manage_plans')): ?>
                <a href="plans.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg transition duration-150">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Plan Management
                </a>
                <?php endif; ?>
                <?php if (admin_has_permission('can_view_analytics')): ?>
                <a href="analytics.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg transition duration-150">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Analytics
                </a>
                <?php endif; ?>
                <?php if (admin_has_permission('can_view_logs')): ?>
                <a href="logs.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg transition duration-150">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    System Logs
                </a>
                <?php endif; ?>
                <?php if (admin_has_permission('can_manage_settings')): ?>
                <a href="settings.php" class="flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-lg transition duration-150">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Welcome Section -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-gray-600 mt-2">Welcome back, <?= htmlspecialchars($admin_user['name']) ?>! Here's what's happening with your system.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_users'] ?? 0) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= number_format($stats['active_users'] ?? 0) ?> active, 
                                <?= number_format($stats['suspended_users'] ?? 0) ?> suspended
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Total Files -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Files</h3>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_files'] ?? 0) ?></p>
                            <p class="text-sm text-gray-500"><?= format_bytes($stats['total_storage_used'] ?? 0) ?> used</p>
                        </div>
                    </div>
                </div>

                <!-- Storage Usage -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Storage Used</h3>
                            <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($stats['total_storage_used'] ?? 0) ?></p>
                            <p class="text-sm text-gray-500">Across all users</p>
                        </div>
                    </div>
                </div>

                <!-- Bandwidth -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Bandwidth</h3>
                            <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($stats['total_bandwidth_used'] ?? 0) ?></p>
                            <p class="text-sm text-gray-500">This month</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Users -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Users</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_users)): ?>
                            <p class="text-gray-500 text-center py-4">No users found</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_users as $user): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 text-sm font-medium">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></p>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['plan_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 text-center">
                            <a href="users.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                View all users →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Uploads</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_uploads)): ?>
                            <p class="text-gray-500 text-center py-4">No recent uploads</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($recent_uploads as $upload): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <?php if ($upload['thumbnail_url']): ?>
                                        <img src="<?= htmlspecialchars($upload['thumbnail_url']) ?>" 
                                             alt="<?= htmlspecialchars($upload['file_name']) ?>" 
                                             class="h-8 w-8 rounded object-cover">
                                        <?php else: ?>
                                        <div class="h-8 w-8 bg-gray-200 rounded flex items-center justify-center">
                                            <span class="text-xs text-gray-500">
                                                <?= strtoupper(pathinfo($upload['file_name'], PATHINFO_EXTENSION)) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 truncate max-w-xs">
                                                <?= htmlspecialchars($upload['file_name']) ?>
                                            </p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($upload['user_name']) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-900"><?= format_bytes($upload['size']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($upload['created_at'])) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Logs -->
            <div class="mt-8 bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent System Activity</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($system_logs)): ?>
                        <p class="text-gray-500 text-center py-4">No system activity</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($system_logs as $log): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center 
                                        <?= $log['type'] === 'upload' ? 'bg-green-100 text-green-600' : 
                                           ($log['type'] === 'api_call' ? 'bg-blue-100 text-blue-600' : 
                                           ($log['type'] === 'auth_fail' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600')) ?>">
                                        <?php
                                        $icons = [
                                            'upload' => '📤',
                                            'api_call' => '🔗', 
                                            'auth_fail' => '🔒',
                                            'download' => '📥'
                                        ];
                                        echo $icons[$log['type']] ?? '📝';
                                        ?>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= ucfirst(str_replace('_', ' ', $log['type'])) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= htmlspecialchars($log['user_name'] ?? 'System') ?> • 
                                            <?= htmlspecialchars($log['endpoint'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-900">
                                        <?php if ($log['bytes'] > 0): ?>
                                            <?= format_bytes($log['bytes']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($log['created_at'])) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4 text-center">
                        <a href="logs.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                            View all logs →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Simple chart for storage usage (optional)
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('storageChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Available'],
                    datasets: [{
                        data: [<?= $stats['total_storage_used'] ?? 0 ?>, 1000000000], // 1GB total for demo
                        backgroundColor: ['#3B82F6', '#E5E7EB'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>