<?php
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function register($email, $password, $name) {
        $conn = $this->db->connect();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Create user
        $apiKey = generateApiKey();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, name, api_key, plan_id) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        if ($stmt->execute([$email, $hashedPassword, $name, $apiKey])) {
            $userId = $conn->lastInsertId();
            
            // Start session
            $this->startSession($userId, $email, $name, $apiKey);
            
            return ['success' => true, 'user_id' => $userId, 'api_key' => $apiKey];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($email, $password) {
        $conn = $this->db->connect();
        
        $stmt = $conn->prepare("SELECT id, email, password, name, api_key, plan_id, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account is suspended'];
        }
        
        $this->startSession($user['id'], $user['email'], $user['name'], $user['api_key']);
        
        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    public function validateApiKey($apiKey) {
        $conn = $this->db->connect();
        
        $stmt = $conn->prepare("
            SELECT u.*, p.* 
            FROM users u 
            LEFT JOIN plans p ON u.plan_id = p.id 
            WHERE u.api_key = ? AND u.is_active = TRUE
        ");
        $stmt->execute([$apiKey]);
        $user = $stmt->fetch();
        
        return $user ?: false;
    }
    
    private function startSession($userId, $email, $name, $apiKey) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['api_key'] = $apiKey;
        $_SESSION['logged_in'] = true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        
        $conn = $this->db->connect();
        $stmt = $conn->prepare("
            SELECT u.*, p.name as plan_name, p.max_storage, p.max_file_size, p.max_monthly_bandwidth 
            FROM users u 
            LEFT JOIN plans p ON u.plan_id = p.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
}
?>