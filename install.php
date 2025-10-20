<?php
require_once 'includes/helpers.php';

// Load environment variables
if (!function_exists('loadEnv')) {
    /**
     * Simple .env loader fallback if one isn't provided by includes/helpers.php
     * Reads key=value lines, ignores comments starting with #, supports quoted values.
     */
    function loadEnv($path = __DIR__ . '/.env')
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ((($value[0] ?? '') === '"' && substr($value, -1) === '"') || (($value[0] ?? '') === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            if (getenv($name) === false) {
                putenv("$name=$value");
            }
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv();

$db = new Database();
$conn = $db->connect();

if (!$conn) {
    die("Database connection failed. Please check your .env file.");
}

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) UNIQUE,
    plan_id INT DEFAULT 1,
    storage_used BIGINT DEFAULT 0,
    monthly_bandwidth_used BIGINT DEFAULT 0,
    monthly_requests INT DEFAULT 0,
    max_storage BIGINT DEFAULT 536870912, -- 512MB default
    max_file_size INT DEFAULT 5242880, -- 5MB default
    max_monthly_bandwidth BIGINT DEFAULT 1073741824, -- 1GB default
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Plans table
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) DEFAULT 0,
    price_yearly DECIMAL(10,2) DEFAULT 0,
    max_storage BIGINT NOT NULL,
    max_file_size INT NOT NULL,
    max_monthly_bandwidth BIGINT NOT NULL,
    allowed_file_types TEXT,
    compression_quality INT DEFAULT 80,
    can_generate_thumbnails BOOLEAN DEFAULT TRUE,
    max_thumbnail_width INT DEFAULT 200,
    is_active BOOLEAN DEFAULT TRUE,
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Files table
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    compression_quality INT,
    thumbnail_path VARCHAR(500),
    thumbnail_size BIGINT,
    is_compressed BOOLEAN DEFAULT FALSE,
    is_image BOOLEAN DEFAULT FALSE,
    width INT,
    height INT,
    upload_ip VARCHAR(45),
    accessed_count INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Usage logs table
CREATE TABLE IF NOT EXISTS usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_id INT,
    action ENUM('upload', 'download', 'view', 'api_call', 'delete') NOT NULL,
    bandwidth_used BIGINT DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent TEXT,
    endpoint VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at)
);

-- API keys table (for multiple keys per user)
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    last_used TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Insert default plans
INSERT IGNORE INTO plans (id, name, description, price_monthly, price_yearly, max_storage, max_file_size, max_monthly_bandwidth, allowed_file_types, compression_quality, features) VALUES
(1, 'Free', 'Perfect for getting started', 0, 0, 536870912, 5242880, 1073741824, 'image/jpeg,image/png,image/gif,image/webp', 80, '[\"Basic Compression\", \"Thumbnail Generation\", \"API Access\"]'),
(2, 'Pro', 'For power users and developers', 9.99, 99.99, 5368709120, 20971520, 5368709120, 'image/jpeg,image/png,image/gif,image/webp,application/pdf,image/svg+xml', 90, '[\"Advanced Compression\", \"Multiple Thumbnail Sizes\", \"Priority Support\", \"No Watermarks\"]'),
(3, 'Business', 'For teams and businesses', 29.99, 299.99, 21474836480, 52428800, 21474836480, 'image/jpeg,image/png,image/gif,image/webp,application/pdf,image/svg+xml,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document', 95, '[\"All Pro Features\", \"Team Management\", \"Advanced Analytics\", \"Custom Domain\"]');

-- Create admin user (password: admin123)
INSERT IGNORE INTO users (id, email, password, name, api_key, plan_id, max_storage, max_file_size, max_monthly_bandwidth, is_verified) VALUES
(1, 'admin@mini-cloudinary.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin_key_' . SUBSTRING(MD5(RAND()), 1, 50), 3, 107374182400, 104857600, 107374182400, TRUE);

SET FOREIGN_KEY_CHECKS = 1;
";

try {
    $conn->exec($sql);
    echo "Database setup completed successfully!<br>";
    echo "Default plans created.<br>";
    echo "Admin user created: admin@mini-cloudinary.com / admin123<br>";
    echo "You can now delete this install.php file for security.";
} catch (PDOException $e) {
    echo "Error during installation: " . $e->getMessage();
}
