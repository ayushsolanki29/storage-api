<?php
require_once '../includes/admin-auth.php';
require_admin_auth();
require_admin_permission('can_manage_settings');

$admin_user = get_admin_user();

// Get current settings
$settings = get_admin_settings();
$system_settings = $settings['system'] ?? [];
$file_settings = $settings['file'] ?? [];
$api_settings = $settings['api'] ?? [];
$email_settings = $settings['email'] ?? [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = null;

    switch ($action) {
        case 'save_system_settings':
            $result = update_system_settings($_POST);
            break;
        case 'save_file_settings':
            $result = update_file_settings($_POST);
            break;
        case 'save_api_settings':
            $result = update_api_settings($_POST);
            break;
        case 'save_email_settings':
            $result = update_email_settings($_POST);
            break;
        case 'test_email':
            $result = test_email_settings($_POST);
            break;
        case 'clear_cache':
            $result = clear_system_cache();
            break;
        case 'reset_settings':
            $result = reset_settings_to_default();
            break;
    }

    if ($result) {
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];

            // Refresh settings after update
            $settings = get_admin_settings();
            $system_settings = $settings['system'] ?? [];
            $file_settings = $settings['file'] ?? [];
            $api_settings = $settings['api'] ?? [];
            $email_settings = $settings['email'] ?? [];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }

        header('Location: settings.php');
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
    <title>System Settings - Admin - Mini Cloudinary</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
                        <p class="text-gray-600 mt-2">Configure system behavior and preferences</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Last updated by: <?= htmlspecialchars($admin_user['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= date('M j, Y g:i A') ?></p>
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

            <!-- Settings Container with Alpine.js -->
            <div x-data="{
                activeTab: 'system',
                init() {
                    // Set active tab from URL hash if present
                    const hash = window.location.hash.substring(1);
                    if (hash && ['system', 'file', 'api', 'email', 'maintenance'].includes(hash)) {
                        this.activeTab = hash;
                    }
                    
                    // Update URL hash when tab changes
                    this.$watch('activeTab', (value) => {
                        window.location.hash = value;
                    });
                }
            }">
                <!-- Settings Navigation -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button
                                @click="activeTab = 'system'"
                                :class="activeTab === 'system' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                System Settings
                            </button>
                            <button
                                @click="activeTab = 'file'"
                                :class="activeTab === 'file' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                File Settings
                            </button>
                            <button
                                @click="activeTab = 'api'"
                                :class="activeTab === 'api' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                API Settings
                            </button>
                            <button
                                @click="activeTab = 'email'"
                                :class="activeTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                Email Settings
                            </button>
                            <button
                                @click="activeTab = 'maintenance'"
                                :class="activeTab === 'maintenance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                                Maintenance
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- System Settings Tab -->
                <div x-show="activeTab === 'system'" x-cloak>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_system_settings">

                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">General Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <!-- System Name -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="system_name" class="block text-sm font-medium text-gray-700">System Name</label>
                                        <p class="text-xs text-gray-500 mt-1">Display name for the application</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            id="system_name"
                                            name="system_name"
                                            value="<?= htmlspecialchars($system_settings['system_name'] ?? 'Mini Cloudinary') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- User Registration -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">User Registration</label>
                                        <p class="text-xs text-gray-500 mt-1">Allow new users to register</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="flex items-center space-x-4">
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="user_registration"
                                                    value="enabled"
                                                    <?= ($system_settings['user_registration'] ?? 'enabled') === 'enabled' ? 'checked' : '' ?>
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                <span class="ml-2 text-sm text-gray-700">Enabled</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="user_registration"
                                                    value="disabled"
                                                    <?= ($system_settings['user_registration'] ?? 'enabled') === 'disabled' ? 'checked' : '' ?>
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                <span class="ml-2 text-sm text-gray-700">Disabled</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="user_registration"
                                                    value="invite_only"
                                                    <?= ($system_settings['user_registration'] ?? 'enabled') === 'invite_only' ? 'checked' : '' ?>
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                <span class="ml-2 text-sm text-gray-700">Invite Only</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Default User Plan -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="default_user_plan" class="block text-sm font-medium text-gray-700">Default User Plan</label>
                                        <p class="text-xs text-gray-500 mt-1">Plan assigned to new users</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <select
                                            id="default_user_plan"
                                            name="default_user_plan"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <?php
                                            $plans = get_all_plans();
                                            foreach ($plans as $plan):
                                            ?>
                                                <option value="<?= $plan['id'] ?>"
                                                    <?= ($system_settings['default_user_plan'] ?? 1) == $plan['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($plan['name']) ?> - $<?= $plan['price'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Session Timeout -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="session_timeout" class="block text-sm font-medium text-gray-700">Session Timeout (minutes)</label>
                                        <p class="text-xs text-gray-500 mt-1">User session duration</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="session_timeout"
                                            name="session_timeout"
                                            value="<?= htmlspecialchars($system_settings['session_timeout'] ?? '30') ?>"
                                            min="5"
                                            max="1440"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Security Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <!-- Password Policy -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="min_password_length" class="block text-sm font-medium text-gray-700">Minimum Password Length</label>
                                        <p class="text-xs text-gray-500 mt-1">Required password strength</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="min_password_length"
                                            name="min_password_length"
                                            value="<?= htmlspecialchars($system_settings['min_password_length'] ?? '8') ?>"
                                            min="6"
                                            max="32"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- API Rate Limiting -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="api_rate_limit" class="block text-sm font-medium text-gray-700">API Rate Limit</label>
                                        <p class="text-xs text-gray-500 mt-1">Requests per minute per API key</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="api_rate_limit"
                                            name="api_rate_limit"
                                            value="<?= htmlspecialchars($system_settings['api_rate_limit'] ?? '60') ?>"
                                            min="10"
                                            max="1000"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- Failed Login Attempts -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="max_login_attempts" class="block text-sm font-medium text-gray-700">Max Login Attempts</label>
                                        <p class="text-xs text-gray-500 mt-1">Before temporary lockout</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="max_login_attempts"
                                            name="max_login_attempts"
                                            value="<?= htmlspecialchars($system_settings['max_login_attempts'] ?? '5') ?>"
                                            min="3"
                                            max="10"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button
                                type="reset"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Reset Changes
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Save System Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- File Settings Tab -->
                <div x-show="activeTab === 'file'" x-cloak>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_file_settings">

                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">File Upload Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <!-- Max File Size -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="max_file_size_default" class="block text-sm font-medium text-gray-700">Default Max File Size</label>
                                        <p class="text-xs text-gray-500 mt-1">Maximum upload size in bytes</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="max_file_size_default"
                                            name="max_file_size_default"
                                            value="<?= htmlspecialchars($file_settings['max_file_size_default'] ?? '5242880') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <p class="text-xs text-gray-500 mt-1">
                                            Current: <?= format_bytes($file_settings['max_file_size_default'] ?? 5242880) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Allowed File Types -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Allowed File Types</label>
                                        <p class="text-xs text-gray-500 mt-1">Supported MIME types</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="space-y-2">
                                            <?php
                                            $common_types = [
                                                'image/jpeg' => 'JPEG Images',
                                                'image/png' => 'PNG Images',
                                                'image/gif' => 'GIF Images',
                                                'image/webp' => 'WebP Images',
                                                'application/pdf' => 'PDF Documents',
                                                'text/plain' => 'Text Files',
                                                'application/msword' => 'Word Documents',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Documents (DOCX)'
                                            ];

                                            $allowed_types = $file_settings['allowed_file_types'] ?? [];
                                            // Ensure it's an array
                                            if (!is_array($allowed_types)) {
                                                $allowed_types = [];
                                            }
                                            ?>
                                            <?php foreach ($common_types as $mime => $label): ?>
                                                <label class="inline-flex items-center mr-4">
                                                    <input
                                                        type="checkbox"
                                                        name="allowed_file_types[]"
                                                        value="<?= $mime ?>"
                                                        <?= in_array($mime, $allowed_types) ? 'checked' : '' ?>
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    <span class="ml-2 text-sm text-gray-700"><?= $label ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Processing -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Image Processing</label>
                                        <p class="text-xs text-gray-500 mt-1">Default image handling</p>
                                    </div>
                                    <div class="md:col-span-2 space-y-4">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                name="enable_compression"
                                                value="1"
                                                <?= ($file_settings['enable_compression'] ?? '1') ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Enable automatic image compression</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                name="enable_thumbnails"
                                                value="1"
                                                <?= ($file_settings['enable_thumbnails'] ?? '1') ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Enable thumbnail generation</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                name="keep_original"
                                                value="1"
                                                <?= ($file_settings['keep_original'] ?? '1') ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Keep original files</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button
                                type="reset"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Reset Changes
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Save File Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- API Settings Tab -->
                <div x-show="activeTab === 'api'" x-cloak>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_api_settings">

                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">API Configuration</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <!-- API Base URL -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="api_base_url" class="block text-sm font-medium text-gray-700">API Base URL</label>
                                        <p class="text-xs text-gray-500 mt-1">Base URL for API endpoints</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="url"
                                            id="api_base_url"
                                            name="api_base_url"
                                            value="<?= htmlspecialchars($api_settings['api_base_url'] ?? 'http://localhost/storage_app/api/') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- API Version -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="api_version" class="block text-sm font-medium text-gray-700">API Version</label>
                                        <p class="text-xs text-gray-500 mt-1">Current API version</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            id="api_version"
                                            name="api_version"
                                            value="<?= htmlspecialchars($api_settings['api_version'] ?? 'v1') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- CORS Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">CORS Settings</label>
                                        <p class="text-xs text-gray-500 mt-1">Cross-Origin Resource Sharing</p>
                                    </div>
                                    <div class="md:col-span-2 space-y-4">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                name="cors_enabled"
                                                value="1"
                                                <?= ($api_settings['cors_enabled'] ?? '1') ? 'checked' : '' ?>
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Enable CORS</span>
                                        </label>
                                        <div>
                                            <label for="allowed_origins" class="block text-sm font-medium text-gray-700 mb-1">Allowed Origins</label>
                                            <textarea
                                                id="allowed_origins"
                                                name="allowed_origins"
                                                rows="3"
                                                placeholder="https://example.com&#10;https://app.example.com"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= htmlspecialchars($api_settings['allowed_origins'] ?? '') ?></textarea>
                                            <p class="text-xs text-gray-500 mt-1">One origin per line. Leave empty to allow all origins.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button
                                type="reset"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Reset Changes
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Save API Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Email Settings Tab -->
                <div x-show="activeTab === 'email'" x-cloak>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_email_settings">

                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Email Configuration</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <!-- SMTP Settings -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="smtp_host" class="block text-sm font-medium text-gray-700">SMTP Host</label>
                                        <p class="text-xs text-gray-500 mt-1">Mail server hostname</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            id="smtp_host"
                                            name="smtp_host"
                                            value="<?= htmlspecialchars($email_settings['smtp_host'] ?? 'smtp.gmail.com') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="smtp_port" class="block text-sm font-medium text-gray-700">SMTP Port</label>
                                        <p class="text-xs text-gray-500 mt-1">Mail server port</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="number"
                                            id="smtp_port"
                                            name="smtp_port"
                                            value="<?= htmlspecialchars($email_settings['smtp_port'] ?? '587') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="smtp_username" class="block text-sm font-medium text-gray-700">SMTP Username</label>
                                        <p class="text-xs text-gray-500 mt-1">Email account username</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            id="smtp_username"
                                            name="smtp_username"
                                            value="<?= htmlspecialchars($email_settings['smtp_username'] ?? '') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="smtp_password" class="block text-sm font-medium text-gray-700">SMTP Password</label>
                                        <p class="text-xs text-gray-500 mt-1">Email account password</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="password"
                                            id="smtp_password"
                                            name="smtp_password"
                                            value="<?= htmlspecialchars($email_settings['smtp_password'] ?? '') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                                    </div>
                                </div>

                                <!-- From Address -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="from_email" class="block text-sm font-medium text-gray-700">From Email</label>
                                        <p class="text-xs text-gray-500 mt-1">Sender email address</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="email"
                                            id="from_email"
                                            name="from_email"
                                            value="<?= htmlspecialchars($email_settings['from_email'] ?? 'noreply@example.com') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- From Name -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="from_name" class="block text-sm font-medium text-gray-700">From Name</label>
                                        <p class="text-xs text-gray-500 mt-1">Sender display name</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input
                                            type="text"
                                            id="from_name"
                                            name="from_name"
                                            value="<?= htmlspecialchars($email_settings['from_name'] ?? 'Mini Cloudinary') ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                name="action"
                                value="test_email"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Test Email
                            </button>
                            <button
                                type="reset"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Reset Changes
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Save Email Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Maintenance Tab -->
                <div x-show="activeTab === 'maintenance'" x-cloak>
                    <div class="space-y-6">
                        <!-- Cache Management -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Cache Management</h3>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Clear System Cache</h4>
                                        <p class="text-sm text-gray-500 mt-1">Clear all cached data including thumbnails and processed files</p>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="clear_cache">
                                        <button
                                            type="submit"
                                            onclick="return confirm('Are you sure you want to clear all cache? This may temporarily slow down the system.')"
                                            class="px-4 py-2 bg-orange-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                            Clear Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Reset -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">System Reset</h3>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Reset to Default Settings</h4>
                                        <p class="text-sm text-gray-500 mt-1">Restore all settings to their default values</p>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="reset_settings">
                                        <button
                                            type="submit"
                                            onclick="return confirm('WARNING: This will reset ALL settings to default values. This action cannot be undone. Are you sure?')"
                                            class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                            Reset Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Server Information</h4>
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">PHP Version</dt>
                                                <dd class="text-gray-900"><?= phpversion() ?></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Server Software</dt>
                                                <dd class="text-gray-900"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Database</dt>
                                                <dd class="text-gray-900">MySQL</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Max Upload Size</dt>
                                                <dd class="text-gray-900"><?= ini_get('upload_max_filesize') ?></dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-3">Application Information</h4>
                                        <dl class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Version</dt>
                                                <dd class="text-gray-900">1.0.0</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Environment</dt>
                                                <dd class="text-gray-900">Development</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Last Backup</dt>
                                                <dd class="text-gray-900">Never</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-gray-500">Uptime</dt>
                                                <dd class="text-gray-900"><?= round((time() - strtotime('2025-10-21')) / 3600) ?> hours</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</body>

</html>