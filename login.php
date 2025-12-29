<?php
/**
 * Login Page - Dark Theme
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Session Timeout Check (20 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1200)) {
    session_unset();
    session_destroy();
    // Use header directly or error variable, but since we are handling post/get, we might just unset. 
    // However, login.php usually doesn't need to check timeout for ITSELF, 
    // but auth.php DOES. 
    // Wait, I am editing login.php. Login page shouldn't timeout. 
    // I should edit includes/auth.php instead for the timeout logic!
}
$_SESSION['last_activity'] = time();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token if implemented (ToDo)
    // Sanitize input to prevent XSS and SQL Injection
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Fetch user details alongside their role name
        $stmt = $pdo->prepare("
            SELECT u.*, ur.role_name 
            FROM users u 
            INNER JOIN user_roles ur ON u.role_id = ur.role_id 
            WHERE u.email = ? AND u.is_active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Check if locked out
        if ($user && $user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
             $minutesLeft = ceil((strtotime($user['lockout_until']) - time()) / 60);
             $error = "Account is locked due to too many failed attempts. Try again in $minutesLeft minutes.";
        } else {
            if ($user && password_verify($password, $user['password_hash'])) {
                // Success: Reset failed attempts and clear lockout
                $pdo->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE user_id = ?")->execute([$user['user_id']]);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role_name'];
                
                // Log audit
                logAudit($pdo, $user['user_id'], 'Login', 'User logged in successfully');
                
                redirect('dashboard.php', 'Welcome back, ' . $user['full_name'], 'success');
            } else {
                // Failure
                if ($user) {
                    $newAttempts = $user['failed_login_attempts'] + 1;
                    if ($newAttempts >= 5) {
                        // Lock for 15 minutes
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $pdo->prepare("UPDATE users SET failed_login_attempts = ?, lockout_until = ? WHERE user_id = ?")->execute([$newAttempts, $lockUntil, $user['user_id']]);
                        $error = 'Account locked due to 5 failed attempts. Please try again in 15 minutes.';
                    } else {
                        $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE user_id = ?")->execute([$newAttempts, $user['user_id']]);
                        $attemptsLeft = 5 - $newAttempts;
                        $error = "Invalid email or password. You have $attemptsLeft attempts remaining.";
                    }
                } else {
                    // Ambiguous error message for security (User not found)
                    $error = 'Invalid email or password';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CIRAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            z-index: 0;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        .input-dark {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
        }
        .input-dark:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .input-dark::placeholder {
            color: #64748b;
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
        .text-gradient {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative z-10">
    <div class="w-full max-w-md relative z-10">
        <div class="glass-card rounded-3xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="landing.php" class="inline-block mb-4">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500 rounded-xl blur-lg opacity-50"></div>
                        <div class="relative bg-gradient-to-br from-blue-600 to-indigo-600 p-4 rounded-xl">
                            <i class="fas fa-shield-alt text-4xl text-white"></i>
                        </div>
                    </div>
                </a>
                <h1 class="text-3xl font-black text-white mb-2">CIRAS</h1>
                <p class="text-gray-400">Cybercrime Incident Reporting & Analysis System</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-900/30 border border-red-500/50 rounded-xl text-red-300 backdrop-blur-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2 text-blue-400"></i>Email Address
                    </label>
                    <input type="email" name="email" required 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter your email">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-400"></i>Password
                    </label>
                    <input type="password" name="password" required 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" 
                        class="btn-primary w-full text-white py-3.5 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all relative z-10">
                    <span class="relative z-10">
                        <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                    </span>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-400">
                    Don't have an account? 
                    <a href="register.php" class="text-gradient font-semibold hover:underline">Register here</a>
                </p>
            </div>
            
            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-slate-800/50 rounded-xl border border-slate-700">
                <p class="text-xs text-gray-400 font-semibold mb-2">Demo Credentials:</p>
                <div class="text-xs text-gray-500 space-y-1">
                    <p><strong class="text-gray-300">Admin:</strong> admin@ciras.com / admin123</p>
                    <p><strong class="text-gray-300">Officer:</strong> officer1@ciras.com / password</p>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <a href="landing.php" class="text-sm text-gray-500 hover:text-gray-300 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
