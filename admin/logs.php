<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_view_logs');

$admin_user = get_admin_user();

// Pagination and filters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(200, max(1, intval($_GET['limit']))) : 50;
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$endpoint_filter = $_GET['endpoint'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$ip_filter = $_GET['ip_address'] ?? '';

// Get logs data
$logs_data = get_system_logs($page, $limit, [
    'search' => $search,
    'type' => $type_filter,
    'user_id' => $user_filter,
    'endpoint' => $endpoint_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'ip_address' => $ip_filter
]);

$logs = $logs_data['logs'];
$total_logs = $logs_data['total'];
$total_pages = $logs_data['total_pages'];
$filter_options = $logs_data['filter_options'];

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selected_logs = $_POST['selected_logs'] ?? [];

    if (!empty($selected_logs) && in_array($action, ['delete', 'export'])) {
        switch ($action) {
            case 'delete':
                $result = delete_logs($selected_logs);
                break;
            case 'export':
                $result = export_logs($selected_logs);
                if ($result['success']) {
                    header('Content-Type: application/csv');
                    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d') . '.csv"');
                    echo $result['data'];
                    exit;
                }
                break;
        }

        if (isset($result) && $result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'] ?? 'Action failed';
        }

        header('Location: logs.php?' . http_build_query($_GET));
        exit;
    }
}

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
    <title>System Logs - Admin - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
                        <h1 class="text-3xl font-bold text-gray-900">System Logs</h1>
                        <p class="text-gray-600 mt-2">Monitor and analyze system activity</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">
                            Total Logs: <span class="font-semibold"><?= number_format($total_logs) ?></span>
                        </p>
                        <p class="text-xs text-gray-500">
                            Showing <?= number_format(count($logs)) ?> records
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

            <!-- Advanced Filters -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search in logs..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Log Type</label>
                            <select
                                id="type"
                                name="type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">All Types</option>
                                <?php foreach ($filter_options['types'] as $type): ?>
                                    <option value="<?= $type ?>" <?= $type_filter === $type ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- User Filter -->
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select
                                id="user_id"
                                name="user_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">All Users</option>
                                <?php foreach ($filter_options['users'] as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Endpoint Filter -->
                        <div>
                            <label for="endpoint" class="block text-sm font-medium text-gray-700 mb-1">Endpoint</label>
                            <select
                                id="endpoint"
                                name="endpoint"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">All Endpoints</option>
                                <?php foreach ($filter_options['endpoints'] as $endpoint): ?>
                                    <option value="<?= $endpoint ?>" <?= $endpoint_filter === $endpoint ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($endpoint) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                value="<?= htmlspecialchars($date_from) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                value="<?= htmlspecialchars($date_to) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- IP Address -->
                        <div>
                            <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                            <input
                                type="text"
                                id="ip_address"
                                name="ip_address"
                                value="<?= htmlspecialchars($ip_filter) ?>"
                                placeholder="Filter by IP..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Results Per Page -->
                        <div>
                            <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                            <select
                                id="limit"
                                name="limit"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                                <option value="200" <?= $limit == 200 ? 'selected' : '' ?>>200</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-end space-x-2">
                            <button
                                type="submit"
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                                Apply
                            </button>
                            <a
                                href="logs.php"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200 text-center">
                                Clear All
                            </a>
                        </div>
                    </form>

                    <!-- Quick Date Presets -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-sm text-gray-600">Quick filters:</span>
                        <a href="?<?= http_build_query(array_merge($_GET, ['date_from' => date('Y-m-d', strtotime('-1 day')), 'date_to' => date('Y-m-d')])) ?>"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">
                            Last 24 Hours
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d')])) ?>"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">
                            Last 7 Days
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['date_from' => date('Y-m-01'), 'date_to' => date('Y-m-t')])) ?>"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded">
                            This Month
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['type' => 'auth_fail'])) ?>"
                            class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">
                            Failed Auth Only
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white rounded-lg shadow mb-6" x-data="{ 
    selectedLogs: [],
    selectAll: false,
    toggleAll() {
        this.selectAll = !this.selectAll;
        this.selectedLogs = this.selectAll ? [<?= implode(',', array_column($logs, 'id')) ?>] : [];
    },
    updateSelectAll() {
        this.selectAll = this.selectedLogs.length === <?= count($logs) ?>;
    }
}">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <input
                                type="checkbox"
                                x-model="selectAll"
                                @click="toggleAll()"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="text-sm text-gray-700" x-text="`${selectedLogs.length} logs selected`"></span>
                        </div>
                        <div class="flex space-x-2" x-show="selectedLogs.length > 0">
                            <form method="POST" class="inline"
                                @submit="if(selectedLogs.length === 0) { alert('Please select logs first'); return false; }">
                                <input type="hidden" name="selected_logs" :value="selectedLogs.join(',')">
                                <input type="hidden" name="action" value="export">
                                <button type="submit"
                                    class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition duration-200">
                                    Export Selected
                                </button>
                            </form>
                            <form method="POST" class="inline"
                                @submit="if(selectedLogs.length === 0 || !confirm('Are you sure you want to delete ' + selectedLogs.length + ' logs?')) return false;">
                                <input type="hidden" name="selected_logs" :value="selectedLogs.join(',')">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit"
                                    class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition duration-200">
                                    Delete Selected
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">System Activity Logs</h2>
                </div>

                <?php if (empty($logs)): ?>
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No logs found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <?= array_filter($_GET) ? 'Try changing your filters.' : 'No system activity recorded yet.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                                        <input type="checkbox" @click="selectAll = !selectAll; selectedLogs = selectAll ? [<?= implode(',', array_column($logs, 'id')) ?>] : []" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type & User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP & User Agent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-50" x-data="{ showDetails: false }">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                value="<?= $log['id'] ?>"
                                                x-model="selectedLogs"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full flex items-center justify-center 
                                                    <?= $log['type'] === 'upload' ? 'bg-green-100 text-green-600' : ($log['type'] === 'api_call' ? 'bg-blue-100 text-blue-600' : ($log['type'] === 'auth_fail' ? 'bg-red-100 text-red-600' : ($log['type'] === 'download' ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-600'))) ?>">
                                                    <?php
                                                    $icons = [
                                                        'upload' => '📤',
                                                        'api_call' => '🔗',
                                                        'auth_fail' => '🔒',
                                                        'download' => '📥',
                                                        'billing' => '💰'
                                                    ];
                                                    echo $icons[$log['type']] ?? '📝';
                                                    ?>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= ucfirst(str_replace('_', ' ', $log['type'])) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <span class="font-medium">Endpoint:</span>
                                                <?= htmlspecialchars($log['endpoint'] ?? 'N/A') ?>
                                            </div>
                                            <?php if ($log['bytes'] > 0): ?>
                                                <div class="text-sm text-gray-500">
                                                    <span class="font-medium">Size:</span>
                                                    <?= format_bytes($log['bytes']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($log['upload_id']): ?>
                                                <div class="text-sm text-gray-500">
                                                    <span class="font-medium">File ID:</span>
                                                    <?= $log['upload_id'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                            </div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">
                                                <?= htmlspecialchars($log['user_agent'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="font-medium">
                                                <?= date('M j, Y', strtotime($log['created_at'])) ?>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                <?= date('g:i:s A', strtotime($log['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button @click="showDetails = !showDetails"
                                                    class="text-blue-600 hover:text-blue-900 text-xs">
                                                    Details
                                                </button>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="selected_logs" value="<?= $log['id'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit"
                                                        onclick="return confirm('Are you sure you want to delete this log?')"
                                                        class="text-red-600 hover:text-red-900 text-xs">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Details Row -->
                                    <tr x-show="showDetails" x-cloak>
                                        <td colspan="6" class="px-6 py-4 bg-gray-50">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <h4 class="font-medium text-gray-900 mb-2">Log Details</h4>
                                                    <dl class="space-y-1">
                                                        <div class="flex">
                                                            <dt class="w-24 text-gray-500">Log ID:</dt>
                                                            <dd class="text-gray-900"><?= $log['id'] ?></dd>
                                                        </div>
                                                        <div class="flex">
                                                            <dt class="w-24 text-gray-500">User ID:</dt>
                                                            <dd class="text-gray-900"><?= $log['user_id'] ?? 'System' ?></dd>
                                                        </div>
                                                        <div class="flex">
                                                            <dt class="w-24 text-gray-500">Upload ID:</dt>
                                                            <dd class="text-gray-900"><?= $log['upload_id'] ?? 'N/A' ?></dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-900 mb-2">Request Info</h4>
                                                    <dl class="space-y-1">
                                                        <div class="flex">
                                                            <dt class="w-20 text-gray-500">IP:</dt>
                                                            <dd class="text-gray-900"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></dd>
                                                        </div>
                                                        <div class="flex">
                                                            <dt class="w-20 text-gray-500">User Agent:</dt>
                                                            <dd class="text-gray-900 text-xs"><?= htmlspecialchars($log['user_agent'] ?? 'N/A') ?></dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $total_logs) ?> of <?= number_format($total_logs) ?> logs
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                                            class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                            class="px-3 py-1 border rounded-md text-sm <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                                            class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when limit changes
        document.getElementById('limit').addEventListener('change', function() {
            this.form.submit();
        });

        // Initialize Alpine.js data
        document.addEventListener('alpine:init', () => {
            Alpine.data('selectedLogs', () => ({
                selectedLogs: [],
                selectAll: false
            }));
        });
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</body>

</html>