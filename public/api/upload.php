<?php
require_once '../../includes/helpers.php';
require_once '../../config/Database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/UploadHandler.php';

loadEnv();
session_start();

header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$apiKey = $_GET['api_key'] ?? null;
if (!$apiKey) {
    jsonResponse(['success' => false, 'message' => 'API key required'], 401);
}

$auth = new Auth();
$user = $auth->validateApiKey($apiKey);
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'Invalid API key'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (!isset($_FILES['file'])) {
    jsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
}

$uploadHandler = new UploadHandler();
$result = $uploadHandler->handleUpload($_FILES['file'], $user['id'], $user['compression_quality']);

jsonResponse($result, $result['success'] ? 200 : 400);
