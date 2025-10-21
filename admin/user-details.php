<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_manage_users');

$admin_user = get_admin_user();

// Get user ID from URL
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$user_id) {
    $_SESSION['error_message'] = 'User ID is required';
    header('Location: users.php');
    exit;
}

// Get user details
$user = get_user_details($user_id);
if (!$user) {
    $_SESSION['error_message'] = 'User not found';
    header('Location: users.php');
    exit;
}

// Get user files with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$file_type_filter = $_GET['file_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$files_data = get_user_files($user_id, $page, $limit, [
    'file_type' => $file_type_filter,
    'date_from' => $date_from,
    'date_to' => $date_to
]);

$files = $files_data['files'];
$total_files = $files_data['total'];
$total_pages = $files_data['total_pages'];

// Get usage statistics
$usage_stats = get_user_usage_stats($user_id);
$monthly_usage = get_user_monthly_usage($user_id);

// Get all plans for upgrade options
$all_plans = get_all_plans();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;

    switch ($action) {
        case 'update_plan':
            $new_plan_id = intval($_POST['plan_id'] ?? 0);
            $result = update_user_plan($user_id, $new_plan_id);
            break;
        case 'reset_api_key':
            $result = reset_user_api_key($user_id);
            break;
        case 'update_status':
            $new_status = $_POST['status'] ?? 'active';
            $result = update_user_status($user_id, $new_status);
            break;
        case 'update_user_info':
            $result = update_user_info($user_id, $_POST);
            break;
        case 'delete_user_files':
            $result = delete_all_user_files($user_id);
            break;
    }

    if ($result) {
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            
            // Refresh user data
            $user = get_user_details($user_id);
            $usage_stats = get_user_usage_stats($user_id);
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: user-details.php?' . http_build_query($_GET));
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
    <title>User Details - <?= htmlspecialchars($user['name']) ?> - Admin - Mini Cloudinary</title>
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
                        <div class="flex items-center space-x-4">
                            <a href="users.php" class="text-blue-600 hover:text-blue-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            </a>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">User Details</h1>
                                <p class="text-gray-600 mt-2">Manage user account and monitor activity</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">User ID: <span class="font-semibold">#<?= $user_id ?></span></p>
                        <p class="text-xs text-gray-500">Last active: <?= $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?></p>
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

            <!-- User Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- User Info Card -->
                <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 text-xl font-bold">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['name']) ?></h3>
                            <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                       ($user['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Joined</p>
                            <p class="font-medium"><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500">Last Login</p>
                            <p class="font-medium"><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Storage Usage Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Storage Usage</h4>
                    <p class="text-2xl font-bold text-gray-900"><?= format_bytes($user['used_space']) ?></p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <?php
                        $storage_limit = $user['storage_limit'] ?? 1073741824;
                        $usage_percent = $storage_limit > 0 ? min(100, ($user['used_space'] / $storage_limit) * 100) : 0;
                        $color = $usage_percent > 90 ? 'bg-red-500' : ($usage_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $usage_percent ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <?= number_format($usage_percent, 1) ?>% of <?= format_bytes($storage_limit) ?>
                    </p>
                </div>

                <!-- API Usage Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">API Usage</h4>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($user['monthly_requests']) ?></p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?= number_format($user['monthly_requests']) ?> / <?= number_format($user['monthly_request_limit']) ?> requests
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <?php
                        $request_percent = $user['monthly_request_limit'] > 0 ? min(100, ($user['monthly_requests'] / $user['monthly_request_limit']) * 100) : 0;
                        $color = $request_percent > 90 ? 'bg-red-500' : ($request_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $request_percent ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-3">
                        <!-- Plan Upgrade -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                                Change Plan
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-64 bg-white rounded-md shadow-lg border">
                                <form method="POST" class="p-4">
                                    <input type="hidden" name="action" value="update_plan">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select New Plan</label>
                                    <select name="plan_id" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                        <?php foreach ($all_plans as $plan): ?>
                                            <option value="<?= $plan['id'] ?>" <?= $user['plan_id'] == $plan['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($plan['name']) ?> - $<?= $plan['price'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="flex space-x-2 mt-3">
                                        <button type="button" @click="open = false" class="flex-1 bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                                            Cancel
                                        </button>
                                        <button type="submit" class="flex-1 bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                            Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'suspended' : 'active' ?>">
                            <button type="submit" class="bg-<?= $user['status'] === 'active' ? 'yellow' : 'green' ?>-600 text-white px-4 py-2 rounded text-sm hover:bg-<?= $user['status'] === 'active' ? 'yellow' : 'green' ?>-700">
                                <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?> User
                            </button>
                        </form>

                        <!-- Reset API Key -->
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="reset_api_key">
                            <button type="submit" onclick="return confirm('Are you sure you want to reset the API key? This will invalidate the current key.')" class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700">
                                Reset API Key
                            </button>
                        </form>

                        <!-- View API Key -->
                        <div x-data="{ showKey: false }" class="relative">
                            <button @click="showKey = !showKey" class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700">
                                View API Key
                            </button>
                            <div x-show="showKey" @click.away="showKey = false" class="absolute z-10 mt-2 w-96 bg-white rounded-md shadow-lg border p-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">API Key</p>
                                <div class="flex items-center space-x-2">
                                    <code class="flex-1 bg-gray-100 px-3 py-2 rounded text-sm font-mono" x-text="showKey ? '<?= $user['api_key'] ?>' : '••••••••'"></code>
                                    <button type="button" @click="navigator.clipboard.writeText('<?= $user['api_key'] ?>')" class="bg-gray-200 px-3 py-2 rounded text-sm hover:bg-gray-300">
                                        Copy
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Keep this key secure. It provides full access to the user's account.</p>
                            </div>
                        </div>

                        <!-- Delete Files -->
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="delete_user_files">
                            <button type="submit" onclick="return confirm('WARNING: This will delete ALL files for this user. This action cannot be undone. Are you sure?')" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                                Delete All Files
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Files Section -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">User Files (<?= number_format($total_files) ?>)</h3>
                        <div class="flex space-x-2">
                            <!-- File Filters -->
                            <form method="GET" class="flex space-x-2">
                                <input type="hidden" name="id" value="<?= $user_id ?>">
                                <select name="file_type" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">All Types</option>
                                    <option value="image" <?= $file_type_filter === 'image' ? 'selected' : '' ?>>Images</option>
                                    <option value="pdf" <?= $file_type_filter === 'pdf' ? 'selected' : '' ?>>PDFs</option>
                                    <option value="document" <?= $file_type_filter === 'document' ? 'selected' : '' ?>>Documents</option>
                                </select>
                                <input type="date" name="date_from" value="<?= $date_from ?>" placeholder="From Date" class="px-3 py-1 border border-gray-300 rounded text-sm" onchange="this.form.submit()">
                                <input type="date" name="date_to" value="<?= $date_to ?>" placeholder="To Date" class="px-3 py-1 border border-gray-300 rounded text-sm" onchange="this.form.submit()">
                                <select name="limit" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                                    <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <?php if (empty($files)): ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No files found</h3>
                            <p class="mt-1 text-sm text-gray-500">This user hasn't uploaded any files yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type & Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URLs</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($files as $file): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if (strpos($file['mime_type'], 'image/') === 0 && $file['thumbnail_url']): ?>
                                                        <img src="<?= $file['thumbnail_url'] ?>" alt="<?= htmlspecialchars($file['file_name']) ?>" class="h-10 w-10 rounded object-cover">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 bg-gray-100 rounded flex items-center justify-center">
                                                            <span class="text-gray-400 text-sm">
                                                                <?php
                                                                $icons = [
                                                                    'pdf' => '📄',
                                                                    'document' => '📝',
                                                                    'text' => '📃'
                                                                ];
                                                                echo $icons[pathinfo($file['file_name'], PATHINFO_EXTENSION)] ?? '📁';
                                                                ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900 truncate max-w-xs">
                                                            <?= htmlspecialchars($file['file_name']) ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            ID: <?= $file['id'] ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= $file['mime_type'] ?></div>
                                                <div class="text-xs text-gray-500"><?= format_bytes($file['size']) ?></div>
                                                <?php if ($file['is_compressed'] === '1'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        Compressed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="space-y-1">
                                                    <?php if ($file['file_url']): ?>
                                                        <div class="flex items-center space-x-1">
                                                            <span class="text-xs text-gray-500">Original:</span>
                                                            <button onclick="navigator.clipboard.writeText('<?= $file['file_url'] ?>')" class="text-blue-600 hover:text-blue-500 text-xs">
                                                                Copy
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($file['compressed_url']): ?>
                                                        <div class="flex items-center space-x-1">
                                                            <span class="text-xs text-gray-500">Compressed:</span>
                                                            <button onclick="navigator.clipboard.writeText('<?= $file['compressed_url'] ?>')" class="text-green-600 hover:text-green-500 text-xs">
                                                                Copy
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($file['thumbnail_url']): ?>
                                                        <div class="flex items-center space-x-1">
                                                            <span class="text-xs text-gray-500">Thumbnail:</span>
                                                            <button onclick="navigator.clipboard.writeText('<?= $file['thumbnail_url'] ?>')" class="text-purple-600 hover:text-purple-500 text-xs">
                                                                Copy
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= date('M j, Y', strtotime($file['created_at'])) ?>
                                                <div class="text-xs text-gray-400">
                                                    <?= date('g:i A', strtotime($file['created_at'])) ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="<?= $file['file_url'] ?>" target="_blank" class="text-blue-600 hover:text-blue-900 text-xs">
                                                        View
                                                    </a>
                                                    <a href="<?= $file['file_url'] ?>" download class="text-green-600 hover:text-green-900 text-xs">
                                                        Download
                                                    </a>
                                                    <form method="POST" action="../api/delete.php" class="inline" onsubmit="return confirm('Delete this file?')">
                                                        <input type="hidden" name="id" value="<?= $file['id'] ?>">
                                                        <input type="hidden" name="api_key" value="<?= $user['api_key'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs">
                                                            Delete
                                                        </button>
                                                    </form>
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
                                        Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $total_files) ?> of <?= number_format($total_files) ?> files
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($page > 1): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                                Previous
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1 border rounded-md text-sm <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50' ?>">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
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

            <!-- Usage Statistics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Usage Stats -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Usage Statistics</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Files Uploaded</span>
                                <span class="font-medium"><?= number_format($usage_stats['total_files']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Storage Used</span>
                                <span class="font-medium"><?= format_bytes($usage_stats['total_storage']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Average File Size</span>
                                <span class="font-medium"><?= format_bytes($usage_stats['avg_file_size']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Files This Month</span>
                                <span class="font-medium"><?= number_format($usage_stats['files_this_month']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">API Calls This Month</span>
                                <span class="font-medium"><?= number_format($usage_stats['api_calls_this_month']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Plan -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Current Plan</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Plan Name</span>
                                <span class="font-medium text-blue-600"><?= htmlspecialchars($user['plan_name']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Price</span>
                                <span class="font-medium">$<?= $user['plan_price'] ?>/month</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Storage Limit</span>
                                <span class="font-medium"><?= format_bytes($user['storage_limit']) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Request Limit</span>
                                <span class="font-medium"><?= number_format($user['monthly_request_limit']) ?>/month</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Bandwidth Limit</span>
                                <span class="font-medium"><?= format_bytes($user['bandwidth_limit']) ?>/month</span>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-gray-50 rounded">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Plan Features</h4>
                            <div class="text-xs text-gray-600">
                                <?php
                                $features = json_decode($user['plan_features'] ?? '[]', true) ?: [];
                                if (!empty($features)) {
                                    echo '<ul class="list-disc list-inside space-y-1">';
                                    foreach ($features as $feature) {
                                        echo '<li>' . htmlspecialchars($feature) . '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<p class="text-gray-500">No specific features listed</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Alpine.js components
        document.addEventListener('alpine:init', () => {
            // Alpine components are automatically initialized via x-data
        });

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Optional: Show success message
                console.log('Copied to clipboard:', text);
            });
        }
    </script>
</body>

</html>