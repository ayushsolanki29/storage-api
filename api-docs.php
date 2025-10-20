<?php
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include 'templates/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">API Documentation</h1>
            
            <!-- API Key Section -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Your API Key</h2>
                <div class="flex items-center space-x-4">
                    <code class="bg-gray-100 px-4 py-2 rounded-lg flex-1 font-mono text-sm"><?= htmlspecialchars($user['api_key']) ?></code>
                    <button onclick="copyToClipboard('<?= htmlspecialchars($user['api_key']) ?>')" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Copy
                    </button>
                </div>
                <p class="text-sm text-gray-600 mt-2">Keep this key secret! It provides full access to your account.</p>
            </div>

            <!-- Upload Endpoint -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Upload File</h2>
                <p class="text-gray-600 mb-4">Upload a file to your storage. Supports images and documents.</p>
                
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4">
                    <code class="block mb-2">POST <?= SITE_URL ?>/api/upload.php?api_key=YOUR_API_KEY</code>
                    <code class="block">Content-Type: multipart/form-data</code>
                </div>
                
                <h3 class="font-semibold mb-2">Parameters:</h3>
                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                    <li><code>file</code> - The file to upload (required)</li>
                    <li><code>filename</code> - Custom filename (optional)</li>
                </ul>
                
                <h3 class="font-semibold mb-2">cURL Example:</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4">
                    <pre><code>curl -X POST \
  '<?= SITE_URL ?>/api/upload.php?api_key=<?= $user['api_key'] ?>' \
  -F 'file=@/path/to/your/image.jpg' \
  -F 'filename=custom-name'</code></pre>
                </div>

                <h3 class="font-semibold mb-2">Node.js Example:</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg">
                    <pre><code>const formData = new FormData();
formData.append('file', fs.createReadStream('/path/to/image.jpg'));

const response = await fetch('<?= SITE_URL ?>/api/upload.php?api_key=<?= $user['api_key'] ?>', {
    method: 'POST',
    body: formData
});

const result = await response.json();
console.log(result);</code></pre>
                </div>
            </div>

            <!-- List Files Endpoint -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">List Files</h2>
                <p class="text-gray-600 mb-4">Retrieve a paginated list of your uploaded files.</p>
                
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4">
                    <code class="block">GET <?= SITE_URL ?>/api/files.php?api_key=YOUR_API_KEY&page=1&limit=20</code>
                </div>
                
                <h3 class="font-semibold mb-2">Parameters:</h3>
                <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                    <li><code>page</code> - Page number (default: 1)</li>
                    <li><code>limit</code> - Items per page (max: 50, default: 20)</li>
                </ul>
                
                <h3 class="font-semibold mb-2">JavaScript Example:</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg">
                    <pre><code>const response = await fetch('<?= SITE_URL ?>/api/files.php?api_key=<?= $user['api_key'] ?>&page=1&limit=10');
const result = await response.json();

console.log(result.files);
// Returns: [{ id, file_name, file_url, compressed_url, thumbnail_url, size, mime_type, created_at }]</code></pre>
                </div>
            </div>

            <!-- Usage Endpoint -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Get Usage Stats</h2>
                <p class="text-gray-600 mb-4">Retrieve your current usage statistics and plan information.</p>
                
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4">
                    <code class="block">GET <?= SITE_URL ?>/api/usage.php?api_key=YOUR_API_KEY</code>
                </div>
                
                <h3 class="font-semibold mb-2">Response Example:</h3>
                <div class="bg-gray-900 text-gray-100 p-4 rounded-lg">
                    <pre><code>{
  "success": true,
  "usage": {
    "storage_used": 10485760,
    "storage_limit": 1073741824,
    "storage_percentage": 0.98,
    "monthly_requests": 150,
    "monthly_request_limit": 1000,
    "monthly_bandwidth": 52428800,
    "bandwidth_limit": 5368709120
  },
  "plan": {
    "name": "Free",
    "features": ["Basic Compression", "Thumbnail Generation", "API Access"]
  }
}</code></pre>
                </div>
            </div>

            <!-- Rate Limiting -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-2 text-yellow-800">Rate Limiting</h2>
                <p class="text-yellow-700 mb-2">All API endpoints are rate limited to <?= RATE_LIMIT_REQUESTS ?> requests per minute.</p>
                <p class="text-yellow-700">If you exceed this limit, you'll receive a 429 Too Many Requests response.</p>
            </div>
        </div>
    </div>

    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('API key copied to clipboard!');
        });
    }
    </script>
</body>
</html>