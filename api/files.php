<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
$user = get_user_by_api_key($api_key);

if (!$user) {
    json_response(['error' => 'Invalid API key'], 401);
}

if (!check_rate_limit($user['id'])) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

global $pdo;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

// Get files
$stmt = $pdo->prepare("
    SELECT id, file_name, file_url, compressed_url, thumbnail_url, size, mime_type, width, height, created_at 
    FROM uploads 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user['id'], $limit, $offset]);
$files = $stmt->fetchAll();

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM uploads WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total = $stmt->fetch()['total'];

// Log API call
log_usage($user['id'], 'api_call', 0, 'files.php');

json_response([
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