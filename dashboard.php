<?php
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_user(); // Fixed function name
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

global $pdo;

// Get user's files
$stmt = $pdo->prepare("
    SELECT * FROM uploads 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['id'], $limit, $offset]);
$files = $stmt->fetchAll();

// Get total files count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM uploads WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_files = $stmt->fetch()['total'];

// Get recent activity
$stmt = $pdo->prepare("
    SELECT type, bytes, endpoint, created_at 
    FROM usage_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recent_activity = $stmt->fetchAll();

// Define constants if not defined
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
}
if (!defined('ALLOWED_DOC_TYPES')) {
    define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Storage Used</h3>
                        <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($user['used_space'] ?? 0) ?></p>
                        <p class="text-sm text-gray-500">of <?= format_bytes($user['storage_limit'] ?? 1073741824) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Monthly Requests</h3>
                        <p class="text-2xl font-semibold text-gray-900"><?= number_format($user['monthly_requests'] ?? 0) ?></p>
                        <p class="text-sm text-gray-500">of <?= number_format($user['monthly_request_limit'] ?? 1000) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Bandwidth</h3>
                        <p class="text-2xl font-semibold text-gray-900"><?= format_bytes($user['monthly_bandwidth'] ?? 0) ?></p>
                        <p class="text-sm text-gray-500">of <?= format_bytes($user['bandwidth_limit'] ?? 5368709120) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500">Current Plan</h3>
                        <p class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($user['plan_name'] ?? 'Free') ?></p>
                        <p class="text-sm text-gray-500">Active</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div x-data="{ isUploading: false, progress: 0 }" class="mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Upload Files</h2>

                <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
                        @drop.prevent="handleDrop($event)"
                        @dragover.prevent="$event.dataTransfer.dropEffect = 'move'">
                        <input type="file" name="file" id="fileInput" class="hidden" multiple
                            accept="<?= implode(',', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES)) ?>">
                        <label for="fileInput" class="cursor-pointer block">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="mt-2">
                                <span class="text-sm font-medium text-gray-900">Drag and drop files here</span>
                                <span class="text-sm text-gray-500">or click to browse</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, PDF up to <?= format_bytes(MAX_FILE_SIZE) ?></p>
                        </label>
                    </div>

                    <!-- Selected files preview -->
                    <div id="filePreview" class="hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Selected files:</h4>
                        <div id="fileList" class="space-y-2"></div>
                    </div>

                    <div x-show="isUploading" class="mt-4">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="`width: ${progress}%`"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-2" x-text="`Uploading... ${progress}%`"></p>
                    </div>

                    <button type="submit"
                        :disabled="isUploading"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200">
                        <span x-show="!isUploading">Upload Files</span>
                        <span x-show="isUploading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Files List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Your Files</h2>
            </div>

            <?php if (empty($files)): ?>
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No files</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by uploading your first file.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if ($file['thumbnail_url']): ?>
                                                <img src="<?= htmlspecialchars($file['thumbnail_url']) ?>"
                                                    alt="<?= htmlspecialchars($file['file_name']) ?>"
                                                    class="h-10 w-10 rounded-lg object-cover">
                                            <?php else: ?>
                                                <div class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <span class="text-xs text-gray-500"><?= strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION)) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($file['file_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= format_bytes($file['size']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($file['mime_type']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y g:i A', strtotime($file['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button onclick="copyToClipboard('<?= htmlspecialchars($file['file_url']) ?>')"
                                            class="text-blue-600 hover:text-blue-900">Copy URL</button>
                                        <a href="<?= htmlspecialchars($file['file_url']) ?>"
                                            class="text-green-600 hover:text-green-900"
                                            download>Download</a>
                                        <button onclick="deleteFile(<?= $file['id'] ?>)"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_files > $limit): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-700">
                                Showing <?= count($files) ?> of <?= $total_files ?> files
                            </p>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Previous</a>
                                <?php endif; ?>

                                <?php if ($page * $limit < $total_files): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File selection handling
        document.getElementById('fileInput').addEventListener('change', function(e) {
            updateFilePreview(this.files);
        });

        function handleDrop(e) {
            const files = e.dataTransfer.files;
            document.getElementById('fileInput').files = files;
            updateFilePreview(files);
        }

        function updateFilePreview(files) {
            const filePreview = document.getElementById('filePreview');
            const fileList = document.getElementById('fileList');

            if (files.length === 0) {
                filePreview.classList.add('hidden');
                return;
            }

            fileList.innerHTML = '';
            Array.from(files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                fileItem.innerHTML = `
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm text-gray-700">${file.name}</span>
                </div>
                <span class="text-xs text-gray-500">${formatFileSize(file.size)}</span>
            `;
                fileList.appendChild(fileItem);
            });

            filePreview.classList.remove('hidden');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('URL copied to clipboard!', 'success');
            }, function() {
                showToast('Failed to copy URL', 'error');
            });
        }

        function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file?')) return;

            fetch(`api/delete.php?id=${fileId}&api_key=<?= $user['api_key'] ?>`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('File deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Failed to delete file: ' + (data.error || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showToast('Error deleting file: ' + error.message, 'error');
                });
        }

        // Upload form handling - FIXED VERSION
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const files = document.getElementById('fileInput').files;
            const alpineData = document.querySelector('[x-data]').__x.$data;

            if (files.length === 0) {
                showToast('Please select files to upload', 'error');
                return;
            }

            // Show uploading state
            alpineData.isUploading = true;
            alpineData.progress = 0;

            const formData = new FormData();
            Array.from(files).forEach(file => {
                formData.append('file', file); // Changed from 'files[]' to 'file'
            });

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    alpineData.progress = Math.round(percentComplete);
                }
            });

            xhr.addEventListener('load', function() {
                alpineData.isUploading = false;
                alpineData.progress = 0;

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showToast('Files uploaded successfully!', 'success');
                        // Clear file input and preview
                        document.getElementById('fileInput').value = '';
                        document.getElementById('filePreview').classList.add('hidden');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Upload failed: ' + (response.error || 'Unknown error'), 'error');
                    }
                } catch (e) {
                    showToast('Upload failed: Invalid response from server', 'error');
                }
            });

            xhr.addEventListener('error', function() {
                alpineData.isUploading = false;
                alpineData.progress = 0;
                showToast('Upload failed: Network error', 'error');
            });

            xhr.open('POST', 'api/upload.php?api_key=<?= $user['api_key'] ?>');
            xhr.send(formData);
        });

        function showToast(message, type = 'info') {
            // Remove existing toasts
            document.querySelectorAll('.toast-message').forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `toast-message fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

      // Upload form handling - FIXED VERSION
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const files = document.getElementById('fileInput').files;
    
    if (files.length === 0) {
        showToast('Please select files to upload', 'error');
        return;
    }
    
    // Get Alpine component safely
    const alpineElement = document.querySelector('[x-data]');
    let alpineData = null;
    
    // Try different ways to access Alpine data
    if (alpineElement && alpineElement.__x) {
        alpineData = alpineElement.__x.$data;
    } else if (window.Alpine && alpineElement) {
        // Alternative method to get Alpine data
        alpineData = Alpine.$data(alpineElement);
    }
    
    if (alpineData) {
        alpineData.isUploading = true;
        alpineData.progress = 0;
    } else {
        // Fallback: create our own state management
        const progressBar = document.querySelector('.bg-blue-600');
        const progressText = document.querySelector('[x-text*="Uploading"]');
        const submitButton = document.querySelector('button[type="submit"]');
        
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <span class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
            </span>
        `;
    }
    
    const formData = new FormData();
    Array.from(files).forEach(file => {
        formData.append('file', file);
    });
    
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            const progress = Math.round(percentComplete);
            
            if (alpineData) {
                alpineData.progress = progress;
            } else {
                // Update progress bar manually
                const progressBar = document.querySelector('.bg-blue-600');
                const progressText = document.querySelector('[x-text*="Uploading"]');
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                }
                if (progressText) {
                    progressText.textContent = `Uploading... ${progress}%`;
                }
            }
        }
    });
    
    xhr.addEventListener('load', function() {
        if (alpineData) {
            alpineData.isUploading = false;
            alpineData.progress = 0;
        } else {
            // Reset manual state
            const submitButton = document.querySelector('button[type="submit"]');
            const progressBar = document.querySelector('.bg-blue-600');
            const progressText = document.querySelector('[x-text*="Uploading"]');
            
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Upload Files';
            }
            if (progressBar) {
                progressBar.style.width = '0%';
            }
        }
        
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showToast('Files uploaded successfully!', 'success');
                // Clear file input and preview
                document.getElementById('fileInput').value = '';
                document.getElementById('filePreview').classList.add('hidden');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Upload failed: ' + (response.error || 'Unknown error'), 'error');
            }
        } catch (e) {
            showToast('Upload failed: Invalid response from server', 'error');
        }
    });
    
    xhr.addEventListener('error', function() {
        if (alpineData) {
            alpineData.isUploading = false;
            alpineData.progress = 0;
        } else {
            // Reset manual state on error
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Upload Files';
            }
        }
        showToast('Upload failed: Network error', 'error');
    });
    
    xhr.open('POST', 'api/upload.php?api_key=<?= $user['api_key'] ?>');
    xhr.send(formData);
});
    </script>
</body>

</html>