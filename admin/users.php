<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_manage_users');

$admin_user = get_admin_user();

// Pagination and search
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get users data
$users_data = get_all_users($page, $limit, $search, $status_filter);
$users = $users_data['users'];
$total_users = $users_data['total'];
$total_pages = $users_data['total_pages'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($user_id && in_array($action, ['suspend', 'activate', 'reset_api_key', 'delete'])) {
        switch ($action) {
            case 'suspend':
                $result = update_user_status($user_id, 'suspended');
                break;
            case 'activate':
                $result = update_user_status($user_id, 'active');
                break;
            case 'reset_api_key':
                $result = reset_user_api_key($user_id);
                break;
            case 'delete':
                $result = delete_user($user_id);
                break;
        }

        if (isset($result) && $result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'] ?? 'Action failed';
        }

        header('Location: users.php?' . http_build_query($_GET));
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
    <title>User Management - Admin - Mini Cloudinary</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                        <p class="text-gray-600 mt-2">Manage all users and their accounts</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total Users: <span class="font-semibold"><?= number_format($total_users) ?></span></p>
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

            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search by name or email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                id="status"
                                name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">All Statuses</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>

                        <!-- Results Per Page -->
                        <div>
                            <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                            <select
                                id="limit"
                                name="limit"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                                <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
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
                                href="users.php"
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200 text-center">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Users</h2>
                </div>

                <?php if (empty($users)): ?>
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <?= $search || $status_filter ? 'Try changing your filters.' : 'No users have registered yet.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan & Usage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <span class="text-blue-600 text-sm font-medium">
                                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                                    <?php if ($user['role'] === 'admin'): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                            Admin
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($user['plan_name'] ?? 'Free') ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?= format_bytes($user['used_space'] ?? 0) ?> used
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <?php
                                                $storage_limit = $user['storage_limit'] ?? 1073741824; // 1GB default
                                                $usage_percent = $storage_limit > 0 ? min(100, ($user['used_space'] / $storage_limit) * 100) : 0;
                                                $color = $usage_percent > 90 ? 'bg-red-500' : ($usage_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                                ?>
                                                <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $usage_percent ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= number_format($user['file_count'] ?? 0) ?> files</div>
                                            <div class="text-sm text-gray-500"><?= format_bytes($user['total_upload_size'] ?? 0) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : ($user['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?= number_format($user['monthly_requests'] ?? 0) ?> / <?= number_format($user['monthly_request_limit'] ?? 1000) ?> requests
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                            <div class="text-xs text-gray-400">
                                                <?= date('g:i A', strtotime($user['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2" x-data="{ open: false }">
                                                <!-- Quick Actions -->
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="suspend">
                                                        <button type="submit"
                                                            onclick="return confirm('Are you sure you want to suspend this user?')"
                                                            class="text-yellow-600 hover:text-yellow-900 text-xs">
                                                            Suspend
                                                        </button>
                                                    </form>
                                                <?php elseif ($user['status'] === 'suspended'): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit"
                                                            class="text-green-600 hover:text-green-900 text-xs">
                                                            Activate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- More Actions Dropdown -->
                                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                                    <button @click="open = !open" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                                                        </svg>
                                                    </button>

                                                    <div x-show="open"
                                                        @click.away="open = false"
                                                        class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                                        <div class="py-1" role="menu">
                                                            <!-- Reset API Key -->
                                                            <form method="POST">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <input type="hidden" name="action" value="reset_api_key">
                                                                <button type="submit"
                                                                    onclick="return confirm('Are you sure you want to reset this user\\s API key?')"
                                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                    role="menuitem">
                                                                    Reset API Key
                                                                </button>
                                                            </form>

                                                            <!-- View Files -->
                                                            <a href="user-details.php?user_id=<?= $user['id'] ?>"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                                role="menuitem">
                                                                View Details
                                                            </a>

                                                            <!-- Delete User -->
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <form method="POST">
                                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <button type="submit"
                                                                        onclick='return confirm("WARNING: This will permanently delete the user and all their files. This action cannot be undone. Are you sure?")'
                                                                        class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-100"
                                                                        role="menuitem">
                                                                        Delete User
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
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
                                    Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $total_users) ?> of <?= number_format($total_users) ?> users
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

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[x-data]')) {
                document.querySelectorAll('[x-data]').forEach(dropdown => {
                    dropdown.__x.$data.open = false;
                });
            }
        });
    </script>
</body>

</html>