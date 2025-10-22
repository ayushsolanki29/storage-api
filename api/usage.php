<?php
header("Access-Control-Allow-Origin: *");
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

// Log API call
log_usage($user['id'], 'api_call', 0, 'usage.php');

json_response([
    'success' => true,
    'usage' => [
        'storage_used' => $user['used_space'],
        'storage_limit' => $user['storage_limit'],
        'storage_percentage' => round(($user['used_space'] / $user['storage_limit']) * 100, 2),
        'monthly_requests' => $user['monthly_requests'],
        'monthly_request_limit' => $user['monthly_request_limit'],
        'monthly_bandwidth' => $user['monthly_bandwidth'],
        'bandwidth_limit' => $user['bandwidth_limit']
    ],
    'plan' => [
        'name' => $user['plan_name'],
        'features' => json_decode($user['features'] ?? '[]', true)
    ]
]);
?>