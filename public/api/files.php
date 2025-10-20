<?php
require_once '../../includes/helpers.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';

loadEnv();
session_start();

header('Content-Type: application/json');

$apiKey = $_GET['api_key'] ?? null;
if (!$apiKey) {
    jsonResponse(['success' => false, 'message' => 'API key required'], 401);
}

$auth = new Auth();
$user = $auth->validateApiKey($apiKey);
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'Invalid API key'], 401);
}

$db = new Database();
$conn = $db->connect();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, intval($_GET['limit'] ?? 20));
$offset = ($page - 1) * $limit;

// Get files
$stmt = $conn->prepare("
    SELECT id, original_name, stored_name, file_path, file_size, mime_type, 
           thumbnail_path, is_compressed, is_image, width, height, created_at
    FROM files 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['id'], $limit, $offset]);
$files = $stmt->fetchAll();

// Get total count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM files WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total = $stmt->fetch()['total'];

// Format response
$baseUrl = env('APP_URL');
foreach ($files as &$file) {
    $file['url'] = $baseUrl . '/' . $file['file_path'];
    if ($file['thumbnail_path']) {
        $file['thumbnail_url'] = $baseUrl . '/' . $file['thumbnail_path'];
    }
    if (strpos($file['file_path'], '_compressed.') !== false) {
        $file['compressed_url'] = $baseUrl . '/' . $file['file_path'];
        $file['original_url'] = $baseUrl . '/' . str_replace('_compressed.', '.', $file['file_path']);
    } else {
        $file['original_url'] = $baseUrl . '/' . $file['file_path'];
    }
    $file['size_formatted'] = formatBytes($file['file_size']);
}

jsonResponse([
    'success' => true,
    'files' => $files,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => ceil($total / $limit)
    ]
]);
?>