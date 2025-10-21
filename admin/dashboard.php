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
    <?php include '../templates/admin-header.php'; ?>

    <div class="flex">
        <!-- Sidebar -->
        <?php include '../templates/admin-sidebar.php'; ?>


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
                                        <?= $log['type'] === 'upload' ? 'bg-green-100 text-green-600' : ($log['type'] === 'api_call' ? 'bg-blue-100 text-blue-600' : ($log['type'] === 'auth_fail' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600')) ?>">
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