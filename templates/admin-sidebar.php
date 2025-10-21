<!-- Sidebar -->
<div class="w-64 bg-white shadow-sm min-h-screen">
    <nav class="mt-8 px-4 space-y-2">
        <?php
        // Get current page name
        $current_page = basename($_SERVER['PHP_SELF']);

        // Define menu items with their permissions and icons
        $menu_items = [
            'dashboard.php' => [
                'name' => 'Dashboard',
                'permission' => null, // Always visible
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'
            ],
            'users.php' => [
                'name' => 'User Management',
                'permission' => 'can_manage_users',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>'
            ],
            'user-details.php' => [
                'name' => 'User Details',
                'permission' => 'can_manage_users',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>',
                'parent' => 'users.php'
            ],
            'plans.php' => [
                'name' => 'Plan Management',
                'permission' => 'can_manage_plans',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>'
            ],
            'analytics.php' => [
                'name' => 'Analytics',
                'permission' => 'can_view_analytics',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>'
            ],
            'logs.php' => [
                'name' => 'System Logs',
                'permission' => 'can_view_logs',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'
            ],
            'settings.php' => [
                'name' => 'Settings',
                'permission' => 'can_manage_settings',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>'
            ]
        ];

        // Function to check if menu item should be active
        function is_active_menu($page, $current_page, $menu_items)
        {
            // Direct match
            if ($page === $current_page) {
                return true;
            }

            // Check if current page is a child of this menu item
            if (isset($menu_items[$current_page]['parent']) && $menu_items[$current_page]['parent'] === $page) {
                return true;
            }

            // Check query parameters for user-details.php
            if ($page === 'users.php' && $current_page === 'user-details.php') {
                return true;
            }

            return false;
        }

        // Generate menu items
        foreach ($menu_items as $page => $item) {
            // Skip if permission required and user doesn't have it
            if ($item['permission'] && !admin_has_permission($item['permission'])) {
                continue;
            }

            // Skip child items (they're handled by parent items)
            if (isset($item['parent'])) {
                continue;
            }

            $is_active = is_active_menu($page, $current_page, $menu_items);
            $active_class = $is_active ? 'text-white bg-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        ?>
            <a href="<?= $page ?>" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition duration-150 <?= $active_class ?>">
                <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $item['icon'] ?>
                </svg>
                <?= $item['name'] ?>
                <?php if ($is_active): ?>
                    <span class="ml-auto w-2 h-2 bg-white rounded-full"></span>
                <?php endif; ?>
            </a>
        <?php
        }
        ?>
    </nav>
</div>