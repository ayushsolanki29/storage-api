<?php
session_start();
require_once 'includes/functions.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Check for demo mode
// $is_demo = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], 'demo') !== false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Cloudinary - Lightweight Self-Hosted File Storage & Delivery</title>
    <meta name="description" content="Mini Cloudinary is a lightweight self-hosted file storage and delivery platform for images and documents. Get Cloudinary-like features with your own hosting.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-pattern {
            background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 55%);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-xl font-bold text-gray-900">Mini Cloudinary</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-blue-600 transition duration-150">Features</a>
                    <a href="#pricing" class="text-gray-600 hover:text-blue-600 transition duration-150">Pricing</a>
                    <a href="#api" class="text-gray-600 hover:text-blue-600 transition duration-150">API</a>
                    <a href="#faq" class="text-gray-600 hover:text-blue-600 transition duration-150">FAQ</a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-600 hover:text-blue-600 transition duration-150 hidden md:block">
                        Sign In
                    </a>
                    <a href="register.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-150 transform hover:scale-105 shadow-md">
                        Get Started Free
                    </a>
                    
                    <!-- Mobile menu button -->
                    <button class="md:hidden" id="mobile-menu-button">
                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
                <a href="#features" class="block px-3 py-2 text-gray-600 hover:text-blue-600">Features</a>
                <a href="#pricing" class="block px-3 py-2 text-gray-600 hover:text-blue-600">Pricing</a>
                <a href="#api" class="block px-3 py-2 text-gray-600 hover:text-blue-600">API</a>
                <a href="#faq" class="block px-3 py-2 text-gray-600 hover:text-blue-600">FAQ</a>
                <a href="login.php" class="block px-3 py-2 text-gray-600 hover:text-blue-600">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg text-white relative overflow-hidden">
        <div class="hero-pattern absolute inset-0"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div>
                    <h1 class="text-4xl md:text-6xl font-bold leading-tight">
                        Your Own
                        <span class="text-blue-200">Cloudinary</span>
                        Alternative
                    </h1>
                    <p class="text-xl md:text-2xl text-blue-100 mt-6 leading-relaxed">
                        Self-hosted file storage and delivery platform for images and documents. 
                        <span class="font-semibold">Full control, no vendor lock-in.</span>
                    </p>
                    
                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        <a href="register.php" class="bg-white text-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-50 transition duration-150 transform hover:scale-105 shadow-lg text-center">
                            <i class="fas fa-rocket mr-2"></i>
                            Start Free Today
                        </a>
                        <a href="#features" class="glass-effect text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:bg-opacity-20 transition duration-150 text-center">
                            <i class="fas fa-play-circle mr-2"></i>
                            See Features
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="mt-12 grid grid-cols-3 gap-8 text-center">
                        <div>
                            <div class="text-3xl font-bold">∞</div>
                            <div class="text-blue-200 text-sm">Self-Hosted</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold">100%</div>
                            <div class="text-blue-200 text-sm">Open Source</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold">$0</div>
                            <div class="text-blue-200 text-sm">Free Tier</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Visual -->
                <div class="relative">
                    <div class="bg-white rounded-2xl p-6 shadow-2xl animate-float">
                        <div class="bg-gray-800 rounded-lg p-4">
                            <!-- Code Example -->
                            <div class="font-mono text-sm text-green-400">
                                <div class="text-gray-400"># Upload image via API</div>
                                <div class="mt-2">
                                    <span class="text-blue-400">curl</span> -X POST \<br>
                                    <span class="ml-4">'<?= SITE_URL ?>/api/upload.php<span class="text-yellow-300">?api_key=YOUR_KEY</span>'</span> \<br>
                                    <span class="ml-4">-F <span class="text-green-400">'file=@image.jpg'</span></span>
                                </div>
                                <div class="mt-4 text-gray-400"># Response:</div>
                                <div class="mt-2 text-gray-300">{</div>
                                <div class="ml-4 text-gray-300">"original_url": "<span class="text-blue-300"><?= SITE_URL ?>/uploads/...</span>",</div>
                                <div class="ml-4 text-gray-300">"compressed_url": "<span class="text-blue-300"><?= SITE_URL ?>/uploads/...</span>",</div>
                                <div class="ml-4 text-gray-300">"thumbnail_url": "<span class="text-blue-300"><?= SITE_URL ?>/uploads/..."</span></div>
                                <div class="text-gray-300">}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating elements -->
                    <div class="absolute -top-4 -right-4 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-sm font-semibold shadow-lg animate-pulse">
                        <i class="fas fa-bolt mr-1"></i>Fast
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-green-400 text-green-900 px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                        <i class="fas fa-shield-alt mr-1"></i>Secure
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Wave divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1200 120" preserveAspectRatio="none" class="fill-current text-gray-50 w-full h-12">
                <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25"></path>
                <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5"></path>
                <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z"></path>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900">
                    Everything You Need for
                    <span class="text-blue-600">File Management</span>
                </h2>
                <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
                    A complete suite of tools to store, process, and deliver your files with enterprise-grade features at open-source prices.
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-cloud-upload-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Smart Upload</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Drag & drop interface, multiple file upload, progress tracking, and automatic organization by date and user.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Drag & drop support
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Bulk uploads
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Progress indicators
                        </li>
                    </ul>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-compress-arrows-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Image Optimization</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Automatic compression, thumbnail generation, and format optimization to reduce file sizes without quality loss.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Smart compression
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Multiple thumbnail sizes
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            WebP support
                        </li>
                    </ul>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-code text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">REST API</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Comprehensive API for integration with any application. Upload, list, delete, and manage files programmatically.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            RESTful design
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            JSON responses
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Rate limiting
                        </li>
                    </ul>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-tachometer-alt text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Usage Analytics</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Track storage usage, API calls, and bandwidth consumption with beautiful charts and real-time monitoring.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Real-time stats
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Usage alerts
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Export reports
                        </li>
                    </ul>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Security First</h3>
                    <p class="text-gray-600 leading-relaxed">
                        API key authentication, rate limiting, secure file storage, and protection against common vulnerabilities.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            API key security
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Rate limiting
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            File validation
                        </li>
                    </ul>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 transition-all duration-300">
                    <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-mobile-alt text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Responsive Dashboard</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Beautiful, mobile-friendly interface that works perfectly on desktop, tablet, and mobile devices.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Mobile optimized
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Dark mode ready
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            Fast loading
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section id="api" class="py-20 bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl md:text-5xl font-bold">
                        Developer-Friendly
                        <span class="text-blue-400">API</span>
                    </h2>
                    <p class="mt-6 text-xl text-gray-300 leading-relaxed">
                        Integrate file management into your applications with our simple, well-documented REST API. 
                        Support for all major programming languages and frameworks.
                    </p>
                    
                    <div class="mt-8 space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-400 mr-3 text-xl"></i>
                            <span class="text-gray-300">Simple authentication with API keys</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-400 mr-3 text-xl"></i>
                            <span class="text-gray-300">Comprehensive documentation</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-400 mr-3 text-xl"></i>
                            <span class="text-gray-300">Code examples for Node.js, Python, PHP</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check text-green-400 mr-3 text-xl"></i>
                            <span class="text-gray-300">Rate limiting and usage tracking</span>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex gap-4">
                        <a href="api-docs.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-150">
                            <i class="fas fa-book mr-2"></i>
                            API Documentation
                        </a>
                        <a href="register.php" class="border border-gray-600 text-gray-300 px-6 py-3 rounded-lg hover:bg-gray-800 transition duration-150">
                            <i class="fas fa-key mr-2"></i>
                            Get API Key
                        </a>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-2xl p-6 shadow-2xl">
                    <div class="flex space-x-2 mb-4">
                        <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                        <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                    </div>
                    <div class="font-mono text-sm">
                        <div class="text-green-400">// Node.js Upload Example</div>
                        <div class="mt-4 text-gray-400">const formData = new FormData();</div>
                        <div class="text-gray-400">formData.append('file', </div>
                        <div class="text-gray-400 ml-4">fs.createReadStream('image.jpg'));</div>
                        <div class="mt-4 text-gray-400">const response = await fetch(</div>
                        <div class="text-blue-300 ml-4">'<?= SITE_URL ?>/api/upload.php?api_key=KEY'</div>
                        <div class="text-gray-400 ml-4">, { method: 'POST', body: formData }</div>
                        <div class="text-gray-400">);</div>
                        <div class="mt-4 text-gray-400">const result = await response.json();</div>
                        <div class="text-green-400 mt-4">// Get: original, compressed, thumbnail URLs</div>
                        <div class="text-gray-400">console.log(result.file.compressed_url);</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900">
                    Simple, Transparent
                    <span class="text-blue-600">Pricing</span>
                </h2>
                <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
                    Start free and upgrade as you grow. No hidden fees, no surprise charges.
                </p>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 text-center hover:border-blue-500 transition duration-300">
                    <h3 class="text-2xl font-bold text-gray-900">Free</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">$0</span>
                        <span class="text-gray-600">/forever</span>
                    </div>
                    <p class="mt-4 text-gray-600">Perfect for personal projects and testing</p>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            1 GB Storage
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            1,000 API Requests/Month
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Basic Compression
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Thumbnail Generation
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Community Support
                        </li>
                    </ul>
                    
                    <a href="register.php" class="mt-8 block w-full bg-gray-100 text-gray-900 py-3 rounded-lg hover:bg-gray-200 transition duration-150 font-semibold">
                        Get Started Free
                    </a>
                </div>

                <!-- Pro Plan -->
                <div class="bg-blue-600 text-white rounded-2xl p-8 text-center transform scale-105 relative shadow-2xl">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-green-400 text-green-900 px-4 py-1 rounded-full text-sm font-semibold">
                            Most Popular
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold">Pro</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">$9.99</span>
                        <span class="text-blue-200">/month</span>
                    </div>
                    <p class="mt-4 text-blue-200">For growing businesses and applications</p>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            5 GB Storage
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            10,000 API Requests/Month
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            Advanced Compression
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            Multiple Thumbnail Sizes
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            Priority Support
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check mr-3"></i>
                            No Watermark
                        </li>
                    </ul>
                    
                    <a href="register.php" class="mt-8 block w-full bg-white text-blue-600 py-3 rounded-lg hover:bg-blue-50 transition duration-150 font-semibold">
                        Start Pro Trial
                    </a>
                </div>

                <!-- Business Plan -->
                <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 text-center hover:border-purple-500 transition duration-300">
                    <h3 class="text-2xl font-bold text-gray-900">Business</h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">$29.99</span>
                        <span class="text-gray-600">/month</span>
                    </div>
                    <p class="mt-4 text-gray-600">For high-traffic applications</p>
                    
                    <ul class="mt-8 space-y-4">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            20 GB Storage
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            50,000 API Requests/Month
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Maximum Compression
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Custom Thumbnails
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Priority Processing
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            Advanced Analytics
                        </li>
                    </ul>
                    
                    <a href="register.php" class="mt-8 block w-full bg-gray-100 text-gray-900 py-3 rounded-lg hover:bg-gray-200 transition duration-150 font-semibold">
                        Start Business Trial
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900">
                    Frequently Asked
                    <span class="text-blue-600">Questions</span>
                </h2>
                <p class="mt-6 text-xl text-gray-600">
                    Everything you need to know about Mini Cloudinary
                </p>
            </div>

            <div class="mt-12 space-y-6">
                <!-- FAQ 1 -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(1)">
                        <h3 class="text-lg font-semibold text-gray-900">Is Mini Cloudinary really free?</h3>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="faq-icon-1"></i>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-1">
                        Yes! Our free plan includes 1GB storage, 1,000 monthly API requests, and all core features including image compression and thumbnail generation. You can use it forever without any cost.
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(2)">
                        <h3 class="text-lg font-semibold text-gray-900">Can I self-host Mini Cloudinary?</h3>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="faq-icon-2"></i>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-2">
                        Absolutely! Mini Cloudinary is designed specifically for self-hosting. You can deploy it on any PHP 8.x hosting environment, including shared hosting like Hostinger.
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(3)">
                        <h3 class="text-lg font-semibold text-gray-900">What file types are supported?</h3>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="faq-icon-3"></i>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-3">
                        We support all common image formats (JPEG, PNG, GIF, WebP) and documents (PDF, DOC, DOCX). Image processing features are available for supported image formats.
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(4)">
                        <h3 class="text-lg font-semibold text-gray-900">How does the API work?</h3>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="faq-icon-4"></i>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-4">
                        Our REST API uses simple API key authentication. You can upload files, list your files, delete files, and check usage stats through well-documented endpoints with JSON responses.
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <button class="flex justify-between items-center w-full text-left" onclick="toggleFAQ(5)">
                        <h3 class="text-lg font-semibold text-gray-900">Is there a file size limit?</h3>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300" id="faq-icon-5"></i>
                    </button>
                    <div class="mt-4 text-gray-600 hidden" id="faq-content-5">
                        The free plan supports files up to 5MB, Pro plan up to 20MB, and Business plan up to 50MB. These limits can be customized for self-hosted instances.
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-16 text-center">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-900">Ready to get started?</h3>
                <p class="mt-4 text-xl text-gray-600">Join thousands of developers using Mini Cloudinary</p>
                <div class="mt-8 flex justify-center gap-4">
                    <a href="register.php" class="bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition duration-150 transform hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i>
                        Start Free Today
                    </a>
                    <a href="#features" class="border border-gray-300 text-gray-700 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-50 transition duration-150">
                        <i class="fas fa-question-circle mr-2"></i>
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div>
                    <div class="flex items-center">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <span class="ml-2 text-xl font-bold">Mini Cloudinary</span>
                    </div>
                    <p class="mt-4 text-gray-400">
                        A lightweight self-hosted file storage and delivery platform for modern web applications.
                    </p>
                </div>

                <!-- Product -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Product</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#features" class="hover:text-white transition duration-150">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition duration-150">Pricing</a></li>
                        <li><a href="#api" class="hover:text-white transition duration-150">API</a></li>
                        <li><a href="login.php" class="hover:text-white transition duration-150">Sign In</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="api-docs.php" class="hover:text-white transition duration-150">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition duration-150">Guides</a></li>
                        <li><a href="#" class="hover:text-white transition duration-150">Blog</a></li>
                        <li><a href="#faq" class="hover:text-white transition duration-150">FAQ</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Company</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition duration-150">About</a></li>
                        <li><a href="#" class="hover:text-white transition duration-150">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition duration-150">Privacy</a></li>
                        <li><a href="#" class="hover:text-white transition duration-150">Terms</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> Mini Cloudinary. All rights reserved. Made with ❤️ for the open source community.</p>
            </div>
        </div>
    </footer>

    <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });

    // FAQ toggle functionality
    function toggleFAQ(number) {
        const content = document.getElementById('faq-content-' + number);
        const icon = document.getElementById('faq-icon-' + number);
        
        content.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add scroll animation for features
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    });
    </script>
</body>
</html>