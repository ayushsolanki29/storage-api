<?php
function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/../';
    }
    
    $envFile = $path . '.env';
    if (!file_exists($envFile)) {
        return false;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
    return true;
}

function env($key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false) {
        return $default;
    }
    
    switch (strtolower($value)) {
        case 'true': return true;
        case 'false': return false;
        case 'null': return null;
        case '': return $default;
    }
    
    return $value;
}

function generateApiKey() {
    return bin2hex(random_bytes(32));
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getMimeType($filePath) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    return $mime;
}

function isImage($mimeType) {
    return strpos($mimeType, 'image/') === 0;
}

function logUsage($userId, $action, $bandwidth = 0, $fileId = null, $endpoint = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO usage_logs (user_id, file_id, action, bandwidth_used, ip_address, user_agent, endpoint) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $fileId,
        $action,
        $bandwidth,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $endpoint
    ]);
    
    // Update user's monthly usage
    if ($action === 'upload') {
        $conn->prepare("UPDATE users SET storage_used = storage_used + ? WHERE id = ?")
             ->execute([$bandwidth, $userId]);
    }
    
    $conn->prepare("UPDATE users SET monthly_bandwidth_used = monthly_bandwidth_used + ?, monthly_requests = monthly_requests + 1 WHERE id = ?")
         ->execute([$bandwidth, $userId]);
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>