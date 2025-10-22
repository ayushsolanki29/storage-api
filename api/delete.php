<?php
header("Access-Control-Allow-Origin: *");
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json_response(['error' => 'Method not allowed. Use DELETE.'], 405);
}

// Get API key from query string
$api_key = $_GET['api_key'] ?? '';

if (empty($api_key)) {
    json_response(['error' => 'API key is required'], 400);
}

// Get file ID from query string or input
$file_id = $_GET['id'] ?? null;
if (!$file_id) {
    // Try to get from input for DELETE requests
    parse_str(file_get_contents("php://input"), $input_data);
    $file_id = $input_data['id'] ?? null;
}

if (!$file_id) {
    json_response(['error' => 'File ID is required'], 400);
}

// Validate file ID
if (!is_numeric($file_id) || $file_id <= 0) {
    json_response(['error' => 'Invalid file ID'], 400);
}

$user = get_user_by_api_key($api_key);

if (!$user) {
    json_response(['error' => 'Invalid API key'], 401);
}

// Check rate limiting
if (!check_rate_limit($user['id'])) {
    json_response(['error' => 'Rate limit exceeded'], 429);
}

global $pdo;

try {
    $pdo->beginTransaction();

    // Get file details and verify ownership
    $stmt = $pdo->prepare("
        SELECT * FROM uploads 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$file_id, $user['id']]);
    $file = $stmt->fetch();

    if (!$file) {
        $pdo->rollBack();
        json_response(['error' => 'File not found or access denied'], 404);
    }

    // Delete physical files
    $files_deleted = delete_physical_files($file);

    // Delete database record
    $stmt = $pdo->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user['id']]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        json_response(['error' => 'Failed to delete file record'], 500);
    }

    // Update user storage usage
    $file_size = $file['size'];
    $update_stmt = $pdo->prepare("UPDATE users SET used_space = used_space - ? WHERE id = ? AND used_space >= ?");
    $update_stmt->execute([$file_size, $user['id'], $file_size]);

    // Log the deletion
    log_usage($user['id'], 'api_call', 0, 'delete.php', $file_id);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'File deleted successfully',
        'file' => [
            'id' => (int)$file_id,
            'name' => $file['file_name'],
            'size' => (int)$file['size'],
            'files_deleted' => $files_deleted
        ]
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Delete error: " . $e->getMessage());
    json_response(['error' => 'Delete failed: ' . $e->getMessage()], 500);
}

/**
 * Delete physical files from server
 */
function delete_physical_files($file)
{
    $files_deleted = [];

    // Delete main file
    if (!empty($file['file_path']) && file_exists($file['file_path'])) {
        if (unlink($file['file_path'])) {
            $files_deleted[] = 'original';
        }
    }

    // Delete compressed file
    if (!empty($file['compressed_path']) && file_exists($file['compressed_path'])) {
        if (unlink($file['compressed_path'])) {
            $files_deleted[] = 'compressed';
        }
    }

    // Delete thumbnail file
    if (!empty($file['thumbnail_path']) && file_exists($file['thumbnail_path'])) {
        if (unlink($file['thumbnail_path'])) {
            $files_deleted[] = 'thumbnail';
        }
    }

    // Clean up empty directories
    clean_empty_directories($file);

    return $files_deleted;
}

/**
 * Clean up empty directories after file deletion
 */
function clean_empty_directories($file)
{
    if (empty($file['file_path'])) {
        return;
    }

    $file_path = $file['file_path'];
    $upload_dir = dirname($file_path);

    // Check if directory is empty
    if (is_dir($upload_dir) && count(scandir($upload_dir)) == 2) { // Only . and .. 
        rmdir($upload_dir);

        // Also check and remove parent directories if empty
        $parent_dir = dirname($upload_dir);
        if (is_dir($parent_dir) && count(scandir($parent_dir)) == 2) {
            rmdir($parent_dir);

            $grandparent_dir = dirname($parent_dir);
            if (is_dir($grandparent_dir) && count(scandir($grandparent_dir)) == 2) {
                rmdir($grandparent_dir);
            }
        }
    }
}
