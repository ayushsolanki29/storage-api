<?php
session_start();
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_user();
$new_api_key = $_SESSION['new_api_key'] ?? $user['api_key'];
unset($_SESSION['new_api_key']); // Clear after display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="flex justify-center">
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Welcome to Mini Cloudinary!</h2>
            <p class="mt-2 text-sm text-gray-600">Your account has been created successfully.</p>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Your API Key</h3>
            <p class="text-sm text-gray-600 mb-4">
                This is your personal API key. Keep it secure and don't share it with anyone.
            </p>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <code class="text-sm font-mono break-all"><?= htmlspecialchars($new_api_key) ?></code>
            </div>
            
            <button onclick="copyToClipboard('<?= htmlspecialchars($new_api_key) ?>')" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Copy API Key
            </button>
            
            <div class="mt-4 text-center">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                    Go to Dashboard →
                </a>
            </div>
        </div>

        <div class="text-center text-sm text-gray-600">
            <p>Need help? Check out our <a href="api-docs.php" class="text-blue-600 hover:text-blue-500">API Documentation</a></p>
        </div>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('API key copied to clipboard!');
        }, function() {
            alert('Failed to copy API key');
        });
    }
    </script>
</body>
</html>