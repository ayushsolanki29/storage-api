<?php
session_start();
require_once 'includes/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    } elseif (strlen($name) > 255) {
        $errors['name'] = 'Name must be less than 255 characters';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered';
        }
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number';
    }

    // Validate confirm password
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Terms agreement
    if (!isset($_POST['agree_terms'])) {
        $errors['agree_terms'] = 'You must agree to the terms and conditions';
    }

    // If no errors, register user
    if (empty($errors)) {
        $result = register($name, $email, $password);

        if ($result['success']) {
            $success = true;

            // Auto-login after registration
            if (login($email, $password)) {
                // Store API key in session for display
                $_SESSION['new_api_key'] = $result['api_key'];
                header('Location: registration-success.php');
                exit;
            } else {
                header('Location: login.php?registered=1');
                exit;
            }
        } else {
            $errors['general'] = $result['message'];
        }
    }
}

// Preserve form data after submission
$form_data = [
    'name' => $_POST['name'] ?? '',
    'email' => $_POST['email'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mini Cloudinary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .password-strength {
            transition: all 0.3s ease;
        }

        .strength-weak {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .strength-fair {
            background-color: #fffbeb;
            border-color: #fed7aa;
        }

        .strength-good {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        .strength-strong {
            background-color: #f0fdf4;
            border-color: #4ade80;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div>
            <div class="flex justify-center">
                <a href="index.php" class="flex items-center">
                    <div class="h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </div>
                </a>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    sign in to your existing account
                </a>
            </p>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Registration successful!</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Your account has been created. Redirecting to login...</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($errors['general'])): ?>
            <div class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Registration failed</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><?= htmlspecialchars($errors['general']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
    <!-- Registration Form -->
<form class="mt-8 space-y-6" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" x-data="{
    showPassword: false,
    showConfirmPassword: false,
    password: '',
    confirmPassword: '',
    passwordStrength: '',
    isFormValid: false,
    checkFormValidity() {
        const form = this.$el;
        this.isFormValid = form.checkValidity();
    },
    checkPasswordStrength() {
        const password = this.password;
        if (password.length === 0) {
            this.passwordStrength = '';
            return;
        }
        
        let strength = 0;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        if (strength <= 2) this.passwordStrength = 'weak';
        else if (strength <= 3) this.passwordStrength = 'fair';
        else if (strength <= 4) this.passwordStrength = 'good';
        else this.passwordStrength = 'strong';
        
        // Also check form validity when password changes
        this.checkFormValidity();
    }
}" @input="checkFormValidity()">
            <div class="rounded-md shadow-sm -space-y-px">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        value="<?= htmlspecialchars($form_data['name']) ?>"
                        class="appearance-none relative block w-full px-3 py-2 border <?= isset($errors['name']) ? 'border-red-300 placeholder-red-300' : 'border-gray-300 placeholder-gray-500' ?> rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="Enter your full name">
                    <?php if (isset($errors['name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Email Field -->
                <div class="mt-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="<?= htmlspecialchars($form_data['email']) ?>"
                        class="appearance-none relative block w-full px-3 py-2 border <?= isset($errors['email']) ? 'border-red-300 placeholder-red-300' : 'border-gray-300 placeholder-gray-500' ?> focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="Enter your email address">
                    <?php if (isset($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Password Field -->
                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            x-model="password"
                            @input="checkPasswordStrength()"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 pr-10 border <?= isset($errors['password']) ? 'border-red-300 placeholder-red-300' : 'border-gray-300 placeholder-gray-500' ?> focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Create a strong password">
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg x-show="!showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Password Strength Meter -->
                    <div x-show="passwordStrength" class="mt-2" style="display: none;">
                        <div class="flex items-center space-x-2 text-sm">
                            <span class="text-gray-600">Strength:</span>
                            <span x-text="passwordStrength.charAt(0).toUpperCase() + passwordStrength.slice(1)"
                                :class="{
                                      'text-red-600': passwordStrength === 'weak',
                                      'text-orange-600': passwordStrength === 'fair',
                                      'text-blue-600': passwordStrength === 'good',
                                      'text-green-600': passwordStrength === 'strong'
                                  }"></span>
                        </div>
                        <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-300"
                                :class="{
                                     'bg-red-500 w-1/4': passwordStrength === 'weak',
                                     'bg-orange-500 w-1/2': passwordStrength === 'fair',
                                     'bg-blue-500 w-3/4': passwordStrength === 'good',
                                     'bg-green-500 w-full': passwordStrength === 'strong'
                                 }"></div>
                        </div>
                    </div>

                    <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>

                    <!-- Password Requirements -->
                    <div class="mt-2 text-xs text-gray-600 space-y-1">
                        <p>Password must contain:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li :class="password.length >= 8 ? 'text-green-600' : 'text-gray-400'">At least 8 characters</li>
                            <li :class="/[A-Z]/.test(password) ? 'text-green-600' : 'text-gray-400'">One uppercase letter</li>
                            <li :class="/[a-z]/.test(password) ? 'text-green-600' : 'text-gray-400'">One lowercase letter</li>
                            <li :class="/[0-9]/.test(password) ? 'text-green-600' : 'text-gray-400'">One number</li>
                        </ul>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="mt-4">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            x-model="confirmPassword"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 pr-10 border <?= isset($errors['confirm_password']) ? 'border-red-300 placeholder-red-300' : 'border-gray-300 placeholder-gray-500' ?> rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="Confirm your password">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg x-show="!showConfirmPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showConfirmPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Password Match Indicator -->
                    <div x-show="password && confirmPassword" class="mt-1 text-sm" style="display: none;">
                        <span x-show="password === confirmPassword" class="text-green-600">✓ Passwords match</span>
                        <span x-show="password !== confirmPassword" class="text-red-600">✗ Passwords don't match</span>
                    </div>

                    <?php if (isset($errors['confirm_password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Terms Agreement -->
            <div class="flex items-center">
                <input
                    id="agree_terms"
                    name="agree_terms"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    <?= isset($_POST['agree_terms']) ? 'checked' : '' ?>>
                <label for="agree_terms" class="ml-2 block text-sm text-gray-900">
                    I agree to the
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Terms and Conditions</a>
                    and
                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Privacy Policy</a>
                </label>
            </div>
            <?php if (isset($errors['agree_terms'])): ?>
                <p class="text-sm text-red-600"><?= htmlspecialchars($errors['agree_terms']) ?></p>
            <?php endif; ?>

  
   <!-- Submit Button -->
<div>
    <button
        type="submit"
        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
        :class="{
            'opacity-50 cursor-not-allowed bg-blue-400': !isFormValid,
            'bg-blue-600 hover:bg-blue-700': isFormValid
        }"
        :disabled="!isFormValid">
        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </span>
        Create Account
    </button>
</div>

            <!-- Additional Links -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>

        <!-- Features Section -->
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-4">What you get with your free account:</h3>
            <ul class="space-y-2 text-sm text-blue-800">
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    1GB storage for your files
                </li>
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Image compression and thumbnails
                </li>
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    REST API access
                </li>
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    1,000 monthly API requests
                </li>
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Secure file storage
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Real-time password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            function validatePassword() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                // Clear previous errors
                let errorElement = passwordInput.parentElement.querySelector('.text-red-600');
                if (errorElement) {
                    errorElement.remove();
                }

                // Validate password requirements
                if (password.length > 0 && password.length < 8) {
                    showError(passwordInput, 'Password must be at least 8 characters long');
                } else if (password.length > 0 && !/[A-Z]/.test(password)) {
                    showError(passwordInput, 'Password must contain at least one uppercase letter');
                } else if (password.length > 0 && !/[a-z]/.test(password)) {
                    showError(passwordInput, 'Password must contain at least one lowercase letter');
                } else if (password.length > 0 && !/[0-9]/.test(password)) {
                    showError(passwordInput, 'Password must contain at least one number');
                }

                // Validate password match
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    showError(confirmPasswordInput, 'Passwords do not match');
                } else if (confirmPassword.length > 0) {
                    clearError(confirmPasswordInput);
                }
            }

            function showError(input, message) {
                clearError(input);
                const errorElement = document.createElement('p');
                errorElement.className = 'mt-1 text-sm text-red-600';
                errorElement.textContent = message;
                input.parentElement.appendChild(errorElement);
            }

            function clearError(input) {
                const errorElement = input.parentElement.querySelector('.text-red-600');
                if (errorElement) {
                    errorElement.remove();
                }
            }

            passwordInput.addEventListener('input', validatePassword);
            confirmPasswordInput.addEventListener('input', validatePassword);
        });
    </script>
</body>

</html>