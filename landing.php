<?php
/**
 * Landing Page - Dark Theme
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIRAS - Cybercrime Incident Reporting & Analysis System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(59, 130, 246, 0.5);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .icon-glow {
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
        }
        .text-gradient {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(59, 130, 246, 0.5);
            border-radius: 50%;
            animation: particle-float 8s infinite;
        }
        @keyframes particle-float {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }
    </style>
</head>
<body class="bg-slate-900 text-gray-100">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-effect">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500 rounded-lg blur-lg opacity-50"></div>
                        <div class="relative bg-gradient-to-br from-blue-600 to-indigo-600 p-3 rounded-lg">
                            <i class="fas fa-shield-alt text-2xl text-white"></i>
                        </div>
                    </div>
                    <div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">CIRAS</span>
                        <p class="text-xs text-gray-400">Cybercrime Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="px-6 py-2.5 rounded-lg font-medium text-gray-300 hover:text-white transition-colors">
                        Login
                    </a>
                    <a href="register.php" class="btn-primary px-6 py-2.5 rounded-lg font-semibold text-white shadow-lg hover:shadow-xl relative z-10">
                        <span class="relative z-10">Register</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen flex items-center justify-center pt-20 relative">
        <!-- Animated Particles -->
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 6s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 1s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="floating" style="margin-top: 70px; margin-bottom: 30px;">
                <div class="inline-block p-6 glass-effect rounded-2xl">
                    <i class="fas fa-shield-alt text-6xl text-gradient icon-glow"></i>
                </div>
            </div>
            <h1 class="text-6xl md:text-7xl font-black mb-6 leading-tight">
                <span class="text-white">Cybercrime Incident</span><br>
                <span class="text-gradient">Reporting & Analysis</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-12 max-w-3xl mx-auto leading-relaxed">
                Professional incident management platform for law enforcement and security teams. 
                Track, analyze, and resolve cybercrime incidents with advanced analytics.
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="register.php" class="btn-primary px-8 py-4 rounded-xl font-bold text-lg text-white shadow-2xl hover:shadow-blue-500/50 relative z-10 w-full sm:w-auto">
                    <span class="relative z-10 flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i>Get Started
                    </span>
                </a>
                <a href="login.php" class="btn-secondary px-8 py-4 rounded-xl font-semibold text-lg text-white w-full sm:w-auto">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
            </div>
            
            <!-- Stats -->
             
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto" style="margin-bottom: 30px">
                <div class="glass-effect p-6 rounded-xl">
                    <div class="text-4xl font-bold text-gradient mb-2">20+</div>
                    <div class="text-gray-400">Active Cases</div>
                </div>
                <div class="glass-effect p-6 rounded-xl">
                    <div class="text-4xl font-bold text-gradient mb-2">100%</div>
                    <div class="text-gray-400">Secure</div>
                </div>
                <div class="glass-effect p-6 rounded-xl">
                    <div class="text-4xl font-bold text-gradient mb-2">24/7</div>
                    <div class="text-gray-400">Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-slate-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-5xl font-black mb-4">
                    <span class="text-white">Powerful</span> <span class="text-gradient">Features</span>
                </h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    Everything you need to manage cybercrime incidents effectively
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card p-8 rounded-2xl">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mb-4 shadow-lg shadow-blue-500/50">
                            <i class="fas fa-exclamation-triangle text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-white">Incident Management</h3>
                        <p class="text-gray-400 leading-relaxed">
                            Comprehensive incident tracking and management system with full audit trail, 
                            status workflow, and priority management.
                        </p>
                    </div>
                    <div class="flex items-center text-blue-400 font-medium">
                        <span>Learn more</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
                
                <div class="feature-card p-8 rounded-2xl">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl mb-4 shadow-lg shadow-green-500/50">
                            <i class="fas fa-file-shield text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-white">Evidence Management</h3>
                        <p class="text-gray-400 leading-relaxed">
                            Secure evidence upload with SHA-256 hash verification, chain of custody logging, 
                            and comprehensive file management.
                        </p>
                    </div>
                    <div class="flex items-center text-green-400 font-medium">
                        <span>Learn more</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
                
                <div class="feature-card p-8 rounded-2xl">
                    <div class="mb-6">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl mb-4 shadow-lg shadow-purple-500/50">
                            <i class="fas fa-chart-line text-3xl text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-white">Analytics & Reports</h3>
                        <p class="text-gray-400 leading-relaxed">
                            Advanced analytics with interactive charts, real-time dashboards, 
                            and exportable reports in multiple formats.
                        </p>
                    </div>
                    <div class="flex items-center text-purple-400 font-medium">
                        <span>Learn more</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Features Grid -->
    <section class="py-24 bg-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-effect p-6 rounded-xl text-center">
                    <i class="fas fa-user-shield text-4xl text-blue-400 mb-4"></i>
                    <h4 class="font-bold text-white mb-2">Role-Based Access</h4>
                    <p class="text-sm text-gray-400">Secure permission system</p>
                </div>
                <div class="glass-effect p-6 rounded-xl text-center">
                    <i class="fas fa-clock text-4xl text-green-400 mb-4"></i>
                    <h4 class="font-bold text-white mb-2">Real-Time Updates</h4>
                    <p class="text-sm text-gray-400">Live notifications</p>
                </div>
                <div class="glass-effect p-6 rounded-xl text-center">
                    <i class="fas fa-search text-4xl text-purple-400 mb-4"></i>
                    <h4 class="font-bold text-white mb-2">Advanced Search</h4>
                    <p class="text-sm text-gray-400">Powerful filtering</p>
                </div>
                <div class="glass-effect p-6 rounded-xl text-center">
                    <i class="fas fa-mobile-alt text-4xl text-pink-400 mb-4"></i>
                    <h4 class="font-bold text-white mb-2">Responsive Design</h4>
                    <p class="text-sm text-gray-400">Works on all devices</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="glass-effect p-12 rounded-3xl">
                <h2 class="text-5xl font-black mb-6">
                    <span class="text-white">Ready to</span> <span class="text-gradient">Get Started?</span>
                </h2>
                <p class="text-xl text-gray-300 mb-10 leading-relaxed">
                    Join CIRAS today and start managing cybercrime incidents with the most advanced 
                    incident management system available.
                </p>
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="register.php" class="btn-primary px-10 py-4 rounded-xl font-bold text-lg text-white shadow-2xl hover:shadow-blue-500/50 relative z-10 w-full sm:w-auto">
                        <span class="relative z-10 flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2"></i>Create Account
                        </span>
                    </a>
                    <a href="login.php" class="btn-secondary px-10 py-4 rounded-xl font-semibold text-lg text-white w-full sm:w-auto">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 p-2 rounded-lg">
                            <i class="fas fa-shield-alt text-white"></i>
                        </div>
                        <span class="text-xl font-bold text-white">CIRAS</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Professional cybercrime incident management system for law enforcement and security teams.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="login.php" class="text-gray-400 hover:text-white transition">Login</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white transition">Register</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Documentation</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i>support@ciras.com</li>
                        <li><i class="fas fa-phone mr-2"></i>+1 (555) 123-4567</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> CIRAS. All rights reserved. | 
                    <span class="text-gradient">Cybercrime Incident Reporting & Analysis System</span>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
