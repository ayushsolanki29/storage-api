<?php
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_logged_user();
$base_url = SITE_URL . '/api';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js"></script>
       <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css">
</head>

<body class="bg-gray-50">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">API Documentation</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Complete integration guide for React, JavaScript, and other platforms
                </p>
            </div>

            <!-- Quick Start -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🚀 Quick Start</h2>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- API Key -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Your API Key</h3>
                        <div class="flex items-center space-x-3 mb-4">
                            <code class="bg-gray-100 px-4 py-3 rounded-lg flex-1 font-mono text-sm break-all">
                                <?= htmlspecialchars($user['api_key']) ?>
                            </code>
                            <button onclick="copyToClipboard('<?= htmlspecialchars($user['api_key']) ?>')"
                                class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                Copy
                            </button>
                        </div>
                        <p class="text-sm text-gray-600">Keep this key secure! It provides full access to your account.</p>
                    </div>

                    <!-- Base URL -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Base URL</h3>
                        <div class="flex items-center space-x-3">
                            <code class="bg-gray-100 px-4 py-3 rounded-lg flex-1 font-mono text-sm">
                                <?= htmlspecialchars($base_url) ?>
                            </code>
                            <button onclick="copyToClipboard('<?= htmlspecialchars($base_url) ?>')"
                                class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- React Integration -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">⚛️ React Integration</h2>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">1. Install Dependencies</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-bash">npm install axios</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">2. Create API Service</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-javascript">// services/api.js
import axios from 'axios';

const API_BASE_URL = '<?= htmlspecialchars($base_url) ?>';
const API_KEY = '<?= htmlspecialchars($user['api_key']) ?>';

const api = axios.create({
  baseURL: API_BASE_URL,
  params: {
    api_key: API_KEY
  }
});

// Request interceptor for error handling
api.interceptors.response.use(
  response => response,
  error => {
    console.error('API Error:', error.response?.data);
    return Promise.reject(error);
  }
);

export const uploadService = {
  uploadFile: (file, customName = null) => {
    const formData = new FormData();
    formData.append('file', file);
    if (customName) {
      formData.append('filename', customName);
    }
    return api.post('/upload.php', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },

  listFiles: (page = 1, limit = 20) => {
    return api.get('/files.php', {
      params: { page, limit }
    });
  },

  getFile: (fileId) => {
    return api.get('/file.php', {
      params: { id: fileId }
    });
  },

  deleteFile: (fileId) => {
    return api.delete('/delete.php', {
      params: { id: fileId }
    });
  },

  getUsage: () => {
    return api.get('/usage.php');
  }
};

export default api;</code></pre>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-3">3. React Hook Example</h3>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-javascript">// hooks/useFileUpload.js
import { useState } from 'react';
import { uploadService } from '../services/api';

export const useFileUpload = () => {
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState(null);

  const uploadFile = async (file, customName = null) => {
    setUploading(true);
    setError(null);
    setProgress(0);

    try {
      const response = await uploadService.uploadFile(file, customName);
      setProgress(100);
      return response.data;
    } catch (err) {
      setError(err.response?.data?.error || 'Upload failed');
      throw err;
    } finally {
      setUploading(false);
    }
  };

  return { uploadFile, uploading, progress, error };
};

// Component usage
const FileUploader = () => {
  const { uploadFile, uploading, progress, error } = useFileUpload();

  const handleFileSelect = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
      const result = await uploadFile(file);
      console.log('Upload successful:', result);
    } catch (err) {
      console.error('Upload failed:', err);
    }
  };

  return (
    &lt;div&gt;
      &lt;input type="file" onChange={handleFileSelect} disabled={uploading} /&gt;
      {uploading && &lt;progress value={progress} max="100" /&gt;}
      {error && &lt;div className="error"&gt;{error}&lt;/div&gt;}
    &lt;/div&gt;
  );
};</code></pre>
                </div>
            </div>

            <!-- API Endpoints -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🔌 API Endpoints</h2>

                <!-- Upload Endpoint -->
                <div class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-4">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium mr-4">
                            POST
                        </span>
                        <h3 class="text-xl font-semibold">Upload File</h3>
                    </div>

                    <code class="block bg-gray-100 px-4 py-2 rounded-lg mb-4 font-mono text-sm">
                        <?= htmlspecialchars($base_url) ?>/upload.php?api_key=YOUR_API_KEY
                    </code>

                    <div class="grid md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <h4 class="font-semibold mb-2">Parameters</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium mr-2">Required</span>
                                    <span><code>file</code> - File to upload</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium mr-2">Optional</span>
                                    <span><code>filename</code> - Custom filename</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">Response</h4>
                            <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code class="language-json">{
  "success": true,
  "file": {
    "id": 123,
    "name": "image.jpg",
    "original_url": "https://...",
    "compressed_url": "https://...",
    "thumbnail_url": "https://...",
    "size": 1024000,
    "mime_type": "image/jpeg",
    "width": 1920,
    "height": 1080,
    "compression_ratio": 45.5
  }
}</code></pre>
                        </div>
                    </div>

                    <h4 class="font-semibold mb-2">React Example</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4 overflow-x-auto"><code class="language-javascript">// Simple upload component
const SimpleUpload = () => {
  const [uploading, setUploading] = useState(false);

  const handleUpload = async (file) => {
    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('file', file);
      
      const response = await fetch(
        `<?= htmlspecialchars($base_url) ?>/upload.php?api_key=<?= htmlspecialchars($user['api_key']) ?>`,
        {
          method: 'POST',
          body: formData
        }
      );
      
      const result = await response.json();
      console.log('Upload result:', result);
    } catch (error) {
      console.error('Upload failed:', error);
    } finally {
      setUploading(false);
    }
  };

  return (
    &lt;input 
      type="file" 
      onChange={(e) => handleUpload(e.target.files[0])}
      disabled={uploading}
    /&gt;
  );
};</code></pre>
                </div>

                <!-- List Files Endpoint -->
                <div class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-4">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mr-4">
                            GET
                        </span>
                        <h3 class="text-xl font-semibold">List Files</h3>
                    </div>

                    <code class="block bg-gray-100 px-4 py-2 rounded-lg mb-4 font-mono text-sm">
                        <?= htmlspecialchars($base_url) ?>/files.php?api_key=YOUR_API_KEY&page=1&limit=20
                    </code>

                    <div class="grid md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <h4 class="font-semibold mb-2">Parameters</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start">
                                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium mr-2">Optional</span>
                                    <span><code>page</code> - Page number (default: 1)</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium mr-2">Optional</span>
                                    <span><code>limit</code> - Items per page (max: 50)</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">Response</h4>
                            <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code class="language-json">{
  "success": true,
  "files": [
    {
      "id": 123,
      "file_name": "image.jpg",
      "file_url": "https://...",
      "compressed_url": "https://...",
      "thumbnail_url": "https://...",
      "size": 1024000,
      "mime_type": "image/jpeg",
      "created_at": "2024-01-01 12:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}</code></pre>
                        </div>
                    </div>

                    <h4 class="font-semibold mb-2">React Example</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4 overflow-x-auto"><code class="language-javascript">// File list component with pagination
const FileList = () => {
  const [files, setFiles] = useState([]);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);

  const loadFiles = async (pageNum = 1) => {
    setLoading(true);
    try {
      const response = await fetch(
        `<?= htmlspecialchars($base_url) ?>/files.php?api_key=<?= htmlspecialchars($user['api_key']) ?>&page=${pageNum}&limit=10`
      );
      const result = await response.json();
      setFiles(result.files);
    } catch (error) {
      console.error('Failed to load files:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadFiles(page);
  }, [page]);

  return (
    &lt;div&gt;
      {files.map(file => (
        &lt;div key={file.id} className="file-item"&gt;
          &lt;img src={file.thumbnail_url} alt={file.file_name} /&gt;
          &lt;span&gt;{file.file_name}&lt;/span&gt;
        &lt;/div&gt;
      ))}
      &lt;button onClick={() => setPage(p => p - 1)} disabled={page === 1}&gt;
        Previous
      &lt;/button&gt;
      &lt;button onClick={() => setPage(p => p + 1)}&gt;
        Next
      &lt;/button&gt;
    &lt;/div&gt;
  );
};</code></pre>
                </div>

                <!-- Delete Endpoint -->
                <div class="mb-8 p-6 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-4">
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium mr-4">
                            DELETE
                        </span>
                        <h3 class="text-xl font-semibold">Delete File</h3>
                    </div>

                    <code class="block bg-gray-100 px-4 py-2 rounded-lg mb-4 font-mono text-sm">
                        <?= htmlspecialchars($base_url) ?>/delete.php?api_key=YOUR_API_KEY&id=FILE_ID
                    </code>

                    <div class="grid md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <h4 class="font-semibold mb-2">Parameters</h4>
                            <ul class="space-y-2 text-sm">
                                <li class="flex items-start">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium mr-2">Required</span>
                                    <span><code>id</code> - File ID to delete</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">Response</h4>
                            <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code class="language-json">{
  "success": true,
  "message": "File deleted successfully",
  "file": {
    "id": 123,
    "name": "image.jpg",
    "size": 1024000,
    "files_deleted": ["original", "compressed", "thumbnail"]
  }
}</code></pre>
                        </div>
                    </div>

                    <h4 class="font-semibold mb-2">React Example</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg mb-4 overflow-x-auto"><code class="language-javascript">// Delete file function
const deleteFile = async (fileId) => {
  if (!window.confirm('Are you sure you want to delete this file?')) {
    return;
  }

  try {
    const response = await fetch(
      `<?= htmlspecialchars($base_url) ?>/delete.php?api_key=<?= htmlspecialchars($user['api_key']) ?>&id=${fileId}`,
      { method: 'DELETE' }
    );
    
    const result = await response.json();
    if (result.success) {
      console.log('File deleted:', result.file.name);
      // Refresh file list or remove from state
    }
  } catch (error) {
    console.error('Delete failed:', error);
  }
};</code></pre>
                </div>

                <!-- Usage Endpoint -->
                <div class="p-6 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-4">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mr-4">
                            GET
                        </span>
                        <h3 class="text-xl font-semibold">Get Usage Stats</h3>
                    </div>

                    <code class="block bg-gray-100 px-4 py-2 rounded-lg mb-4 font-mono text-sm">
                        <?= htmlspecialchars($base_url) ?>/usage.php?api_key=YOUR_API_KEY
                    </code>

                    <div class="mb-4">
                        <h4 class="font-semibold mb-2">Response</h4>
                        <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code class="language-json">{
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
    "name": "Pro",
    "features": ["Advanced Compression", "Multiple Thumbnail Sizes"]
  }
}</code></pre>
                    </div>

                    <h4 class="font-semibold mb-2">React Example</h4>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code class="language-javascript">// Usage stats component
const UsageStats = () => {
  const [usage, setUsage] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadUsage = async () => {
      try {
        const response = await fetch(
          `<?= htmlspecialchars($base_url) ?>/usage.php?api_key=<?= htmlspecialchars($user['api_key']) ?>`
        );
        const result = await response.json();
        setUsage(result.usage);
      } catch (error) {
        console.error('Failed to load usage stats:', error);
      } finally {
        setLoading(false);
      }
    };

    loadUsage();
  }, []);

  if (loading) return &lt;div&gt;Loading...&lt;/div&gt;;
  if (!usage) return &lt;div&gt;Error loading stats&lt;/div&gt;;

  return (
    &lt;div className="usage-stats"&gt;
      &lt;div&gt;
        Storage: {formatBytes(usage.storage_used)} / {formatBytes(usage.storage_limit)}
        &lt;progress value={usage.storage_percentage} max="100" /&gt;
      &lt;/div&gt;
      &lt;div&gt;
        Requests: {usage.monthly_requests} / {usage.monthly_request_limit}
      &lt;/div&gt;
    &lt;/div&gt;
  );
};</code></pre>
                </div>
            </div>

            <!-- Error Handling -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">⚠️ Error Handling</h2>

                <div class="space-y-4">
                    <div class="p-4 border-l-4 border-red-500 bg-red-50">
                        <h4 class="font-semibold text-red-800">401 Unauthorized</h4>
                        <p class="text-red-700 text-sm">Invalid or missing API key</p>
                        <pre class="mt-2 bg-red-100 p-2 rounded text-xs"><code class="language-json">{"error": "Invalid API key"}</code></pre>
                    </div>

                    <div class="p-4 border-l-4 border-yellow-500 bg-yellow-50">
                        <h4 class="font-semibold text-yellow-800">429 Too Many Requests</h4>
                        <p class="text-yellow-700 text-sm">Rate limit exceeded (<?= RATE_LIMIT_REQUESTS ?> requests/minute)</p>
                        <pre class="mt-2 bg-yellow-100 p-2 rounded text-xs"><code class="language-json">{"error": "Rate limit exceeded"}</code></pre>
                    </div>

                    <div class="p-4 border-l-4 border-blue-500 bg-blue-50">
                        <h4 class="font-semibold text-blue-800">400 Bad Request</h4>
                        <p class="text-blue-700 text-sm">Invalid file type, file too large, or missing parameters</p>
                        <pre class="mt-2 bg-blue-100 p-2 rounded text-xs"><code class="language-json">{"error": "File type not allowed"}</code></pre>
                    </div>
                </div>
            </div>

            <!-- File Types & Limits -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">📁 Supported Files & Limits</h2>

                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Supported File Types</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                <span>JPEG, PNG, GIF, WebP images</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                <span>PDF documents</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                <span>Text files</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                <span>Word documents (DOC/DOCX)</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Limits</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex justify-between">
                                <span>Max File Size:</span>
                                <span class="font-mono"><?= format_bytes(MAX_FILE_SIZE ?? (5 * 1024 * 1024)) ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Rate Limit:</span>
                                <span class="font-mono"><?= RATE_LIMIT_REQUESTS ?> req/min</span>
                            </li>
                            <li class="flex justify-between">
                                <span>Storage Limit:</span>
                                <span class="font-mono"><?= format_bytes($user['storage_limit'] ?? 1073741824) ?></span>
                            </li>
                            <li class="flex justify-between">
                                <span>Monthly Requests:</span>
                                <span class="font-mono"><?= number_format($user['monthly_request_limit'] ?? 1000) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                button.classList.add('bg-green-600', 'hover:bg-green-700');

                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600', 'hover:bg-green-700');
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            });
        }

        // Initialize Prism for syntax highlighting
        document.addEventListener('DOMContentLoaded', function() {
            Prism.highlightAll();
        });
    </script>
</body>

</html>