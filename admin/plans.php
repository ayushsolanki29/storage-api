<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_manage_plans');

$admin_user = get_admin_user();

global $pdo;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_plan' || $action === 'update_plan') {
        $plan_data = [
            'name' => trim($_POST['name'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'storage_limit' => intval($_POST['storage_limit'] ?? 0) * 1024 * 1024 * 1024, // Convert GB to bytes
            'monthly_request_limit' => intval($_POST['monthly_request_limit'] ?? 0),
            'bandwidth_limit' => intval($_POST['bandwidth_limit'] ?? 0) * 1024 * 1024 * 1024, // Convert GB to bytes
            'max_file_size' => intval($_POST['max_file_size'] ?? 0) * 1024 * 1024, // Convert MB to bytes
            'allow_compression' => isset($_POST['allow_compression']) ? 1 : 0,
            'allow_thumbnails' => isset($_POST['allow_thumbnails']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Handle features array
        $features = [];
        if (isset($_POST['features'])) {
            foreach ($_POST['features'] as $feature) {
                if (!empty(trim($feature))) {
                    $features[] = trim($feature);
                }
            }
        }
        $plan_data['features'] = json_encode($features);
        
        // Handle thumbnail sizes
        $thumbnail_sizes = [];
        if (isset($_POST['thumbnail_sizes'])) {
            foreach ($_POST['thumbnail_sizes'] as $size) {
                if (!empty(trim($size['width'])) && !empty(trim($size['height']))) {
                    $thumbnail_sizes[] = [
                        'width' => intval($size['width']),
                        'height' => intval($size['height']),
                        'label' => trim($size['label'] ?? "{$size['width']}x{$size['height']}")
                    ];
                }
            }
        }
        $plan_data['thumbnail_sizes'] = !empty($thumbnail_sizes) ? json_encode($thumbnail_sizes) : null;
        
        if ($action === 'create_plan') {
            $result = create_plan($plan_data);
        } else {
            $plan_id = intval($_POST['plan_id'] ?? 0);
            $result = update_plan($plan_id, $plan_data);
        }
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: plans.php');
        exit;
        
    } elseif ($action === 'delete_plan') {
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $result = delete_plan($plan_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: plans.php');
        exit;
    } elseif ($action === 'toggle_plan') {
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        $result = toggle_plan_status($plan_id, $is_active);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        header('Location: plans.php');
        exit;
    }
}

// Get all plans
$plans = get_all_plans();
$user_counts = get_plan_user_counts();

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
    <title>Plan Management - Admin - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Plan Management</h1>
                        <p class="text-gray-600 mt-2">Create and manage subscription plans</p>
                    </div>
                    <button 
                        onclick="openModal('createPlanModal')"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create New Plan
                    </button>
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

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach ($plans as $plan): ?>
                <div class="bg-white rounded-lg shadow-lg border-2 <?= $plan['is_active'] ? 'border-blue-500' : 'border-gray-300' ?> transform hover:scale-105 transition duration-200">
                    <!-- Plan Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-t-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-bold"><?= htmlspecialchars($plan['name']) ?></h3>
                                <div class="flex items-baseline mt-2">
                                    <span class="text-3xl font-bold">$<?= number_format($plan['price'], 2) ?></span>
                                    <span class="ml-1 text-blue-100">/month</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end space-y-2">
                                <?php if (!$plan['is_active']): ?>
                                    <span class="px-2 py-1 bg-gray-500 text-white text-xs rounded-full">Inactive</span>
                                <?php endif; ?>
                                <span class="px-2 py-1 bg-blue-400 text-white text-xs rounded-full">
                                    <?= $user_counts[$plan['id']] ?? 0 ?> users
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Features -->
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Storage -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Storage</span>
                                <span class="font-semibold"><?= format_bytes($plan['storage_limit']) ?></span>
                            </div>

                            <!-- Requests -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Monthly Requests</span>
                                <span class="font-semibold"><?= number_format($plan['monthly_request_limit']) ?></span>
                            </div>

                            <!-- Bandwidth -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Bandwidth</span>
                                <span class="font-semibold"><?= format_bytes($plan['bandwidth_limit']) ?></span>
                            </div>

                            <!-- File Size -->
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Max File Size</span>
                                <span class="font-semibold"><?= format_bytes($plan['max_file_size']) ?></span>
                            </div>

                            <!-- Features -->
                            <?php if (!empty($plan['features'])): ?>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Features:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <?php 
                                    $features = json_decode($plan['features'], true) ?: [];
                                    foreach (array_slice($features, 0, 3) as $feature): 
                                    ?>
                                    <li class="flex items-center">
                                        <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if (count($features) > 3): ?>
                                    <li class="text-blue-600 text-xs">+<?= count($features) - 3 ?> more features</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex space-x-2">
                            <button 
                                onclick="openEditModal(<?= htmlspecialchars(json_encode($plan)) ?>)"
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 text-sm"
                            >
                                Edit
                            </button>
                            
                            <?php if ($plan['is_active']): ?>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="action" value="toggle_plan">
                                <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                <input type="hidden" name="is_active" value="0">
                                <button 
                                    type="submit"
                                    onclick="return confirm('Are you sure you want to deactivate this plan? Existing users will keep it, but new users cannot select it.')"
                                    class="w-full bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition duration-200 text-sm"
                                >
                                    Deactivate
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="action" value="toggle_plan">
                                <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                <input type="hidden" name="is_active" value="1">
                                <button 
                                    type="submit"
                                    class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-200 text-sm"
                                >
                                    Activate
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if (($user_counts[$plan['id']] ?? 0) === 0): ?>
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                <button 
                                    type="submit"
                                    onclick="return confirm('Are you sure you want to delete this plan? This action cannot be undone.')"
                                    class="w-full bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-200 text-sm"
                                >
                                    Delete
                                </button>
                            </form>
                            <?php else: ?>
                            <button 
                                disabled
                                class="flex-1 bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed text-sm"
                                title="Cannot delete plan with active users"
                            >
                                Delete
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Plan Statistics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Plan Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php foreach ($plans as $plan): ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600"><?= $user_counts[$plan['id']] ?? 0 ?></div>
                        <div class="text-sm text-gray-600"><?= htmlspecialchars($plan['name']) ?> Users</div>
                        <div class="text-xs text-gray-500 mt-1">
                            <?= $plan['is_active'] ? 'Active' : 'Inactive' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Plan Modal -->
    <div id="createPlanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Create New Plan</h3>
                    <button onclick="closeModal('createPlanModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="create_plan">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Plan Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Price -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Monthly Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Storage Limit -->
                        <div>
                            <label for="storage_limit" class="block text-sm font-medium text-gray-700 mb-1">Storage Limit (GB) *</label>
                            <input type="number" id="storage_limit" name="storage_limit" min="1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Monthly Requests -->
                        <div>
                            <label for="monthly_request_limit" class="block text-sm font-medium text-gray-700 mb-1">Monthly Requests *</label>
                            <input type="number" id="monthly_request_limit" name="monthly_request_limit" min="1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Bandwidth Limit -->
                        <div>
                            <label for="bandwidth_limit" class="block text-sm font-medium text-gray-700 mb-1">Bandwidth Limit (GB) *</label>
                            <input type="number" id="bandwidth_limit" name="bandwidth_limit" min="1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Max File Size -->
                        <div>
                            <label for="max_file_size" class="block text-sm font-medium text-gray-700 mb-1">Max File Size (MB) *</label>
                            <input type="number" id="max_file_size" name="max_file_size" min="1" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <!-- Features -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                        <div id="featuresContainer" class="space-y-2">
                            <div class="flex space-x-2">
                                <input type="text" name="features[]" placeholder="Add a feature..."
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <button type="button" onclick="addFeature()" class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Thumbnail Sizes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail Sizes</label>
                        <div id="thumbnailSizesContainer" class="space-y-2">
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" name="thumbnail_sizes[0][width]" placeholder="Width"
                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <input type="text" name="thumbnail_sizes[0][height]" placeholder="Height"
                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <input type="text" name="thumbnail_sizes[0][label]" placeholder="Label (optional)"
                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <button type="button" onclick="addThumbnailSize()" class="mt-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Add Thumbnail Size
                        </button>
                    </div>

                    <!-- Checkboxes -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="allow_compression" name="allow_compression" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="allow_compression" class="ml-2 block text-sm text-gray-900">
                                Allow Compression
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="allow_thumbnails" name="allow_thumbnails" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="allow_thumbnails" class="ml-2 block text-sm text-gray-900">
                                Allow Thumbnails
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" checked
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Active Plan
                            </label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" onclick="closeModal('createPlanModal')"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Create Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div id="editPlanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <!-- Similar to create modal but for editing -->
        <!-- Content will be populated by JavaScript -->
    </div>

    <script>
    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Feature management
    let featureCount = 1;
    function addFeature() {
        const container = document.getElementById('featuresContainer');
        const div = document.createElement('div');
        div.className = 'flex space-x-2';
        div.innerHTML = `
            <input type="text" name="features[]" placeholder="Add a feature..."
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                -
            </button>
        `;
        container.appendChild(div);
        featureCount++;
    }

    // Thumbnail size management
    let thumbnailSizeCount = 1;
    function addThumbnailSize() {
        const container = document.getElementById('thumbnailSizesContainer');
        const div = document.createElement('div');
        div.className = 'grid grid-cols-3 gap-2';
        div.innerHTML = `
            <input type="text" name="thumbnail_sizes[${thumbnailSizeCount}][width]" placeholder="Width"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <input type="text" name="thumbnail_sizes[${thumbnailSizeCount}][height]" placeholder="Height"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <input type="text" name="thumbnail_sizes[${thumbnailSizeCount}][label]" placeholder="Label (optional)"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            <button type="button" onclick="this.parentElement.remove()" class="col-span-3 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                Remove Size
            </button>
        `;
        container.appendChild(div);
        thumbnailSizeCount++;
    }

    // Edit plan modal
    function openEditModal(plan) {
        // This would populate and open an edit modal similar to create modal
        // For brevity, we'll redirect to a separate edit page or use the same modal with pre-filled data
        alert('Edit functionality would open here with plan data: ' + plan.name);
        // In a full implementation, you'd populate a form with the plan data
    }

    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
    </script>
</body>
</html>