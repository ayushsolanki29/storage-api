<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_view_analytics');

$admin_user = get_admin_user();

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$timeframe = $_GET['timeframe'] ?? 'month'; // day, week, month, year

// Apply timeframe presets
switch ($timeframe) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        // Use the provided custom dates
        break;
}

// Get analytics data
$analytics_data = get_analytics_data($start_date, $end_date);
$system_stats = $analytics_data['system_stats'];
$user_activity = $analytics_data['user_activity'];
$file_analytics = $analytics_data['file_analytics'];
$api_usage = $analytics_data['api_usage'];
$top_users = $analytics_data['top_users'];

// Check for messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin - Mini Cloudinary</title>
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
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
                        <p class="text-gray-600 mt-2">Monitor system performance and user activity</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">
                            Period: <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mb-6 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success_message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-6 rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error_message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Date Range Filter -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Timeframe Presets -->
                        <div>
                            <label for="timeframe" class="block text-sm font-medium text-gray-700 mb-1">Timeframe</label>
                            <select
                                id="timeframe"
                                name="timeframe"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="today" <?= $timeframe === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= $timeframe === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                                <option value="month" <?= $timeframe === 'month' ? 'selected' : '' ?>>This Month</option>
                                <option value="year" <?= $timeframe === 'year' ? 'selected' : '' ?>>This Year</option>
                                <option value="custom" <?= $timeframe === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="<?= htmlspecialchars($start_date) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="<?= htmlspecialchars($end_date) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end space-x-2">
                            <button
                                type="submit"
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                                Apply
                            </button>
                            <a
                                href="analytics.php"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200 text-center">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($system_stats['total_users']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <span class="<?= $system_stats['users_growth'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $system_stats['users_growth'] >= 0 ? '↑' : '↓' ?>
                                <?= abs($system_stats['users_growth']) ?>%
                            </span>
                            <span class="ml-2">vs previous period</span>
                        </div>
                    </div>
                </div>

                <!-- Total Files -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Files</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($system_stats['total_files']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <span class="<?= $system_stats['files_growth'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $system_stats['files_growth'] >= 0 ? '↑' : '↓' ?>
                                <?= abs($system_stats['files_growth']) ?>%
                            </span>
                            <span class="ml-2">vs previous period</span>
                        </div>
                    </div>
                </div>

                <!-- Total Storage -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Storage Used</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($system_stats['total_storage']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <span class="<?= $system_stats['storage_growth'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $system_stats['storage_growth'] >= 0 ? '↑' : '↓' ?>
                                <?= abs($system_stats['storage_growth']) ?>%
                            </span>
                            <span class="ml-2">vs previous period</span>
                        </div>
                    </div>
                </div>

                <!-- API Requests -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">API Requests</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($system_stats['total_requests']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm text-gray-500">
                            <span class="<?= $system_stats['requests_growth'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $system_stats['requests_growth'] >= 0 ? '↑' : '↓' ?>
                                <?= abs($system_stats['requests_growth']) ?>%
                            </span>
                            <span class="ml-2">vs previous period</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Detailed Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- User Activity Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">User Activity</h3>
                    <div class="h-64">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                </div>

                <!-- File Type Distribution -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">File Type Distribution</h3>
                    <div class="h-64">
                        <canvas id="fileTypeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- API Usage and Top Users -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- API Usage by Endpoint -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">API Usage by Endpoint</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($api_usage as $endpoint): ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($endpoint['endpoint']) ?></span>
                                        <span class="text-gray-500"><?= number_format($endpoint['count']) ?> requests</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="bg-blue-600 h-2 rounded-full" 
                                            style="width: <?= $endpoint['percentage'] ?>%">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Users by Storage -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Users by Storage</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($top_users as $index => $user): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 text-xs font-medium">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900"><?= format_bytes($user['used_space']) ?></p>
                                        <p class="text-xs text-gray-500"><?= number_format($user['file_count']) ?> files</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detailed Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Plan Distribution -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Plan Distribution</h4>
                            <div class="space-y-2">
                                <?php foreach ($system_stats['plan_distribution'] as $plan): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600"><?= htmlspecialchars($plan['name']) ?></span>
                                        <span class="font-medium text-gray-900"><?= number_format($plan['count']) ?> users</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- File Analytics -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">File Analytics</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Average File Size</span>
                                    <span class="font-medium text-gray-900"><?= format_bytes($file_analytics['avg_file_size']) ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Compression Rate</span>
                                    <span class="font-medium text-gray-900"><?= number_format($file_analytics['compression_rate'], 1) ?>%</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Space Saved</span>
                                    <span class="font-medium text-gray-900"><?= format_bytes($file_analytics['space_saved']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- System Health -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 mb-3">System Health</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Active Users</span>
                                    <span class="font-medium text-green-600"><?= number_format($system_stats['active_users']) ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Suspended Users</span>
                                    <span class="font-medium text-red-600"><?= number_format($system_stats['suspended_users']) ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Failed Auth Attempts</span>
                                    <span class="font-medium text-orange-600"><?= number_format($system_stats['failed_auth']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle timeframe changes
        document.getElementById('timeframe').addEventListener('change', function() {
            if (this.value !== 'custom') {
                this.form.submit();
            }
        });

        // Auto-submit form when dates change in custom mode
        document.getElementById('start_date').addEventListener('change', function() {
            if (document.getElementById('timeframe').value === 'custom') {
                this.form.submit();
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            if (document.getElementById('timeframe').value === 'custom') {
                this.form.submit();
            }
        });

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // User Activity Chart
            const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
            new Chart(userActivityCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($user_activity['dates']) ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?= json_encode($user_activity['new_users']) ?>,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Active Users',
                        data: <?= json_encode($user_activity['active_users']) ?>,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // File Type Chart
            const fileTypeCtx = document.getElementById('fileTypeChart').getContext('2d');
            new Chart(fileTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($file_analytics['type_labels']) ?>,
                    datasets: [{
                        data: <?= json_encode($file_analytics['type_counts']) ?>,
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(139, 92, 246)',
                            'rgb(236, 72, 153)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>