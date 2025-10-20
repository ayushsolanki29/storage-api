<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar for admin pages -->
<aside class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64">
        <div class="flex flex-col h-0 flex-1 border-r border-gray-200 bg-white">
            <!-- Sidebar header -->
            <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                <!-- Logo -->
                <div class="flex items-center flex-shrink-0 px-4">
                    <a href="dashboard.php" class="flex items-center">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-lg font-bold text-gray-900">Mini Cloudinary</span>
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="mt-8 flex-1 px-4 bg-white space-y-2">
                    <!-- User Section -->
                    <div class="px-3">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">User</h3>
                    </div>
                    
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-transparent' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md border-l-4">
                        <svg class="<?= $current_page === 'dashboard.php' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="api-docs.php" class="<?= $current_page === 'api-docs.php' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-transparent' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md border-l-4">
                        <svg class="<?= $current_page === 'api-docs.php' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                        API Documentation
                    </a>

                    <!-- Admin Section (only show if user is admin) -->
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <div class="px-3 mt-8">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</h3>
                    </div>
                    
                    <a href="admin/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-transparent' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md border-l-4">
                        <svg class="<?= $current_page === 'dashboard.php' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Admin Dashboard
                    </a>
                    
                    <a href="admin/users.php" class="<?= $current_page === 'users.php' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-transparent' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md border-l-4">
                        <svg class="<?= $current_page === 'users.php' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        User Management
                    </a>
                    
                    <a href="admin/plans.php" class="<?= $current_page === 'plans.php' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-transparent' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-md border-l-4">
                        <svg class="<?= $current_page === 'plans.php' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' ?> mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Plan Management
                    </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- User profile at bottom -->
            <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                <div class="flex items-center w-full">
                    <div class="h-9 w-9 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-blue-600 text-sm font-medium">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </span>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></p>
                        <p class="text-xs font-medium text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                    </div>
                    <a href="logout.php" class="ml-3 flex-shrink-0 text-gray-400 hover:text-gray-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</aside>