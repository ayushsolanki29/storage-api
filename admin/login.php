<?php

require_once '../includes/functions.php';
require_once '../includes/admin-auth.php';

// Redirect if already logged in as admin
if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

// Check for "remember me" cookie for admin
if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $token = $_COOKIE['remember_token'];
    $user = validate_remember_token($token);
    if ($user && $user['role'] === 'admin') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
}

$errors = [];
$success_message = '';

// Check for messages
if (isset($_GET['logout'])) {
    $success_message = 'You have been successfully logged out.';
}

if (isset($_GET['timeout'])) {
    $errors[] = 'Your session has expired. Please log in again.';
}

// Check for admin auth error from redirect
if (isset($_SESSION['admin_auth_error'])) {
    $errors[] = $_SESSION['admin_auth_error'];
    unset($_SESSION['admin_auth_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        // Attempt admin login
        $result = admin_login($email, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            
            // Handle "remember me" functionality
            if ($remember_me) {
                $token = generate_remember_token();
                set_remember_token($_SESSION['user_id'], $token);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 days
            }
            
            // Log successful admin login
            log_usage($_SESSION['user_id'], 'api_call', 0, 'admin_login');
            
            // Redirect to intended page or admin dashboard
            $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $errors[] = $result['message'];
            
            // Log failed admin login attempt
            global $pdo;
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                log_usage($user['id'], 'auth_fail', 0, 'admin_login');
            }
        }
    }
    
    // Preserve email for form
    $form_email = htmlspecialchars($email);
} else {
    $form_email = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .admin-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="flex justify-center">
                <a href="../index.php" class="flex items-center">
                    <div class="h-16 w-16 bg-white rounded-lg flex items-center justify-center shadow-lg">
                        <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </div>
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Admin Portal
            </h2>
            <div class="mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Restricted Access
                </span>
            </div>
            <p class="mt-2 text-center text-sm text-gray-600">
                Administrator credentials required
                <br>
                <a href="../login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    ← Back to user login
                </a>
            </p>
        </div>

        <!-- Success Messages -->
        <?php if ($success_message): ?>
        <div class="rounded-md bg-green-50 p-4 animate-fade-in">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success_message) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
        <div class="rounded-md bg-red-50 p-4 shake">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Access Denied</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="admin-gradient px-6 py-4">
                <h3 class="text-lg font-semibold text-white text-center">Administrator Sign In</h3>
            </div>
            
            <form class="p-6 space-y-6" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Admin Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required 
                            value="<?= $form_email ?>"
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="admin@example.com"
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Admin Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required 
                            class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input 
                        id="remember_me" 
                        name="remember_me" 
                        type="checkbox" 
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                        Remember this device
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white admin-gradient hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200 transform hover:scale-105"
                    >
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Access Admin Dashboard
                    </button>
                </div>
            </form>
        </div>

        <!-- Demo Admin Account Info -->
        <?php if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-yellow-800">Development Admin Account</h4>
                    <div class="mt-1 text-sm text-yellow-700">
                        <p><strong>Email:</strong> admin@mini-cloudinary.com</p>
                        <p><strong>Password:</strong> password</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Security Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800">Security Notice</h4>
                    <p class="mt-1 text-sm text-blue-700">
                        This area contains sensitive system controls. Access is restricted to authorized administrators only.
                        All login attempts are logged and monitored.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="text-center">
            <div class="flex justify-center space-x-6 text-sm">
                <a href="../index.php" class="text-gray-600 hover:text-blue-500 transition duration-150">
                    ← Back to Home
                </a>
                <span class="text-gray-300">•</span>
                <a href="../login.php" class="text-gray-600 hover:text-blue-500 transition duration-150">
                    User Login
                </a>
                <span class="text-gray-300">•</span>
                <a href="mailto:security@mini-cloudinary.com" class="text-gray-600 hover:text-blue-500 transition duration-150">
                    Report Issue
                </a>
            </div>
        </div>
    </div>

    <script>
    // Enhanced form handling
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Real-time validation
        function validateForm() {
            const isEmailValid = emailInput.value.trim() !== '' && emailInput.checkValidity();
            const isPasswordValid = passwordInput.value.trim() !== '';
            
            return isEmailValid && isPasswordValid;
        }
        
        function updateButtonState() {
            if (validateForm()) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        
        // Event listeners for real-time validation
        emailInput.addEventListener('input', updateButtonState);
        passwordInput.addEventListener('input', updateButtonState);
        
        // Initial validation
        updateButtonState();
        
        // Form submission handling
        form.addEventListener('submit', function(e) {
            if (validateForm()) {
                // Show loading state
                const originalHTML = submitButton.innerHTML;
                submitButton.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Authenticating...
                `;
                submitButton.disabled = true;
                
                // Revert after 5 seconds if still on page (fallback)
                setTimeout(() => {
                    submitButton.innerHTML = originalHTML;
                    submitButton.disabled = false;
                }, 5000);
            }
        });
        
        // Auto-focus email field
        if (!emailInput.value) {
            emailInput.focus();
        }
    });
    </script>
</body>
</html>