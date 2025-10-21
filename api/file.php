<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Get API key from query string
$api_key = $_GET['api_key'] ?? '';

if (empty($api_key)) {
    json_response(['error' => 'API key is required'], 400);
}

$user = get_user_by_api_key($api_key);

if (!$user) {
    json_response(['error' => 'Invalid API key'], 401);
}

// Check rate limiting
if (!check_rate_limit($user['id'])) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

// Get file ID from query string
$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    json_response(['error' => 'File ID is required'], 400);
}

// Validate file ID
if (!is_numeric($file_id) || $file_id <= 0) {
    json_response(['error' => 'Invalid file ID'], 400);
}

global $pdo;

try {
    // Get file details and verify ownership
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            p.name as plan_name,
            (SELECT COUNT(*) FROM uploads WHERE user_id = u.id) as total_files,
            (SELECT SUM(size) FROM uploads WHERE user_id = u.id) as total_storage_used
        FROM uploads u 
        LEFT JOIN users usr ON u.user_id = usr.id
        LEFT JOIN plans p ON usr.plan_id = p.id
        WHERE u.id = ? AND u.user_id = ?
    ");
    $stmt->execute([$file_id, $user['id']]);
    $file = $stmt->fetch();

    if (!$file) {
        json_response(['error' => 'File not found or access denied'], 404);
    }

    // Format file data for response
    $file_data = format_file_data($file);

    // Log the API call
    log_usage($user['id'], 'api_call', 0, 'file.php', $file_id);

    json_response([
        'success' => true,
        'file' => $file_data
    ]);
} catch (Exception $e) {
    error_log("File API error: " . $e->getMessage());
    json_response(['error' => 'Failed to retrieve file information: ' . $e->getMessage()], 500);
}

/**
 * Format file data for API response
 */
function format_file_data($file)
{
    return [
        'id' => (int)$file['id'],
        'user_id' => (int)$file['user_id'],
        'file_name' => $file['file_name'],
        'file_path' => $file['file_path'],
        'file_url' => $file['file_url'],
        'compressed_path' => $file['compressed_path'],
        'compressed_url' => $file['compressed_url'],
        'thumbnail_path' => $file['thumbnail_path'],
        'thumbnail_url' => $file['thumbnail_url'],
        'size' => (int)$file['size'],
        'size_formatted' => format_bytes($file['size']),
        'mime_type' => $file['mime_type'],
        'file_type' => get_file_type($file['mime_type']),
        'width' => $file['width'] ? (int)$file['width'] : null,
        'height' => $file['height'] ? (int)$file['height'] : null,
        'is_compressed' => (bool)$file['is_compressed'],
        'compression_ratio' => $file['compression_ratio'] ? (float)$file['compression_ratio'] : null,
        'created_at' => $file['created_at'],
        'created_timestamp' => strtotime($file['created_at']),
        'urls' => [
            'original' => $file['file_url'],
            'compressed' => $file['compressed_url'],
            'thumbnail' => $file['thumbnail_url']
        ],
        'dimensions' => $file['width'] && $file['height'] ? [
            'width' => (int)$file['width'],
            'height' => (int)$file['height'],
            'aspect_ratio' => $file['height'] > 0 ? round($file['width'] / $file['height'], 2) : null
        ] : null,
        'metadata' => [
            'is_image' => strpos($file['mime_type'], 'image/') === 0,
            'is_document' => strpos($file['mime_type'], 'application/') === 0,
            'extension' => pathinfo($file['file_name'], PATHINFO_EXTENSION),
            'file_size_saved' => $file['compression_ratio'] ?
                round($file['size'] * ($file['compression_ratio'] / 100)) : 0
        ]
    ];
}

/**
 * Get file type category
 */
function get_file_type($mime_type)
{
    if (strpos($mime_type, 'image/') === 0) {
        return 'image';
    } elseif (strpos($mime_type, 'video/') === 0) {
        return 'video';
    } elseif (strpos($mime_type, 'audio/') === 0) {
        return 'audio';
    } elseif (strpos($mime_type, 'application/pdf') === 0) {
        return 'pdf';
    } elseif (
        strpos($mime_type, 'application/msword') === 0 ||
        strpos($mime_type, 'application/vnd.openxmlformats-officedocument') === 0
    ) {
        return 'document';
    } else {
        return 'other';
    }
}
