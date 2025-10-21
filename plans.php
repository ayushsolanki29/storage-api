<?php

require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}


$user = get_logged_user();
$current_plan = get_user_plan($user['id']);
$all_plans = get_all_plans_for_display();

// Handle plan upgrade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_plan'])) {
    $new_plan_id = intval($_POST['plan_id']);
    $result = upgrade_user_plan($user['id'], $new_plan_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
        // Refresh user data
        $user = get_current_user();
        $current_plan = get_user_plan($user['id']);
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    
    header('Location: plans.php');
    exit;
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
    <title>Plans & Upgrade - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <?php include 'templates/header.php'; ?>

    <div class="flex">
 

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Plans & Upgrade</h1>
                        <p class="text-gray-600 mt-2">Choose the perfect plan for your needs</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Current Plan</p>
                        <p class="text-lg font-semibold text-blue-600"><?= htmlspecialchars($current_plan['name']) ?></p>
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

            <!-- Current Plan Overview -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Your Current Plan</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($current_plan['name']) ?></h3>
                            <p class="text-gray-600 mt-1">$<?= $current_plan['price'] ?>/month</p>
                            <div class="mt-4 space-y-2">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?= format_bytes($current_plan['storage_limit']) ?> Storage
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?= number_format($current_plan['monthly_request_limit']) ?> API Requests/Month
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <?= format_bytes($current_plan['bandwidth_limit']) ?> Bandwidth/Month
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                            <p class="text-sm text-gray-500 mt-2">Renews automatically</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Storage Used</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($user['used_space']) ?></p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <?php
                                $storage_percent = $current_plan['storage_limit'] > 0 ? min(100, ($user['used_space'] / $current_plan['storage_limit']) * 100) : 0;
                                $color = $storage_percent > 90 ? 'bg-red-500' : ($storage_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $storage_percent ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($storage_percent, 1) ?>% of <?= format_bytes($current_plan['storage_limit']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">API Requests</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($user['monthly_requests']) ?></p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <?php
                                $request_percent = $current_plan['monthly_request_limit'] > 0 ? min(100, ($user['monthly_requests'] / $current_plan['monthly_request_limit']) * 100) : 0;
                                $color = $request_percent > 90 ? 'bg-red-500' : ($request_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $request_percent ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($request_percent, 1) ?>% of <?= number_format($current_plan['monthly_request_limit']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Bandwidth Used</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($user['monthly_bandwidth']) ?></p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <?php
                                $bandwidth_percent = $current_plan['bandwidth_limit'] > 0 ? min(100, ($user['monthly_bandwidth'] / $current_plan['bandwidth_limit']) * 100) : 0;
                                $color = $bandwidth_percent > 90 ? 'bg-red-500' : ($bandwidth_percent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <div class="h-2 rounded-full <?= $color ?>" style="width: <?= $bandwidth_percent ?>%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1"><?= number_format($bandwidth_percent, 1) ?>% of <?= format_bytes($current_plan['bandwidth_limit']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Plans -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Available Plans</h2>
                    <p class="text-gray-600 mt-1">Upgrade or downgrade your plan anytime</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <?php foreach ($all_plans as $plan): ?>
                            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-300 <?= $plan['id'] == $current_plan['id'] ? 'ring-2 ring-blue-500 bg-blue-50' : '' ?>">
                                <!-- Plan Header -->
                                <div class="text-center mb-6">
                                    <h3 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($plan['name']) ?></h3>
                                    <div class="mt-4">
                                        <span class="text-4xl font-bold text-gray-900">$<?= $plan['price'] ?></span>
                                        <span class="text-gray-600">/month</span>
                                    </div>
                                    <?php if ($plan['id'] == $current_plan['id']): ?>
                                        <span class="inline-block mt-3 px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                            Current Plan
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Plan Features -->
                                <div class="space-y-3 mb-6">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-gray-700"><?= format_bytes($plan['storage_limit']) ?> Storage</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-gray-700"><?= number_format($plan['monthly_request_limit']) ?> API Requests</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-gray-700"><?= format_bytes($plan['bandwidth_limit']) ?> Bandwidth</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-gray-700">Max File Size: <?= format_bytes($plan['max_file_size']) ?></span>
                                    </div>
                                    <?php if ($plan['allow_compression']): ?>
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-gray-700">Image Compression</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($plan['allow_thumbnails']): ?>
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="text-gray-700">Thumbnail Generation</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Upgrade Button -->
                                <form method="POST" onsubmit="return confirm('Are you sure you want to switch to the <?= htmlspecialchars($plan['name']) ?> plan?')">
                                    <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                    <?php if ($plan['id'] == $current_plan['id']): ?>
                                        <button type="button" disabled class="w-full bg-gray-300 text-gray-600 py-2 px-4 rounded-md font-medium cursor-not-allowed">
                                            Current Plan
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="upgrade_plan" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 font-medium transition duration-200">
                                            Switch to <?= htmlspecialchars($plan['name']) ?>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="bg-white rounded-lg shadow mt-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Frequently Asked Questions</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Can I change my plan anytime?</h3>
                            <p class="text-gray-600 mt-2">Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">What happens to my files when I downgrade?</h3>
                            <p class="text-gray-600 mt-2">Your files remain safe. If you exceed the new plan's limits, you won't be able to upload new files until you free up space or upgrade again.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Is there a contract or commitment?</h3>
                            <p class="text-gray-600 mt-2">No contracts. You can cancel or change your plan anytime without any long-term commitment.</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Do you offer refunds?</h3>
                            <p class="text-gray-600 mt-2">We offer prorated refunds for unused time when you downgrade your plan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple confirmation for plan changes
        document.addEventListener('DOMContentLoaded', function() {
            const upgradeButtons = document.querySelectorAll('button[name="upgrade_plan"]');
            upgradeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const planName = this.closest('form').querySelector('input[name="plan_id"]').nextElementSibling?.textContent || 'this plan';
                    if (!confirm(`Are you sure you want to switch to ${planName}?`)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>

</html>