<?php
session_start();
require_once __DIR__ . '/functions.php';

function is_logged_in()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_logged_user()
{
    global $pdo;

    if (!is_logged_in()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT u.*, p.name as plan_name, p.storage_limit, p.monthly_request_limit, p.bandwidth_limit 
        FROM users u 
        LEFT JOIN plans p ON u.plan_id = p.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}


function login($email, $password)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_api_key'] = $user['api_key'];
            $_SESSION['user_role'] = $user['role'] ?? 'user'; // ADD THIS LINE

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function register($name, $email, $password)
{
    global $pdo;

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already exists'];
    }

    $api_key = generate_api_key();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, api_key, plan_id) 
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$name, $email, $hashed_password, $api_key]);

        return ['success' => true, 'user_id' => $pdo->lastInsertId(), 'api_key' => $api_key];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function logout()
{
    session_destroy();
    header('Location: login.php');
    exit;
}
