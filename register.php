<?php
/**
 * Registration Page - Dark Theme
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 * Only public users (Viewer role) can register. Officers are registered by Admin.
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            try {
                // Get Viewer role ID (role_id = 4)
                $stmt = $pdo->prepare("SELECT role_id FROM user_roles WHERE role_name = 'Viewer'");
                $stmt->execute();
                $role = $stmt->fetch();
                
                if (!$role) {
                    $error = 'Registration role not found. Please contact administrator.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, full_name, phone, role_id, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$username, $email, $passwordHash, $fullName, $phone, $role['role_id']]);
                    
                    $success = 'Registration successful! You can now login.';
                    logAudit($pdo, $pdo->lastInsertId(), 'User Registration', "New user registered: {$username}", 'users', $pdo->lastInsertId());
                }
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
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
    <title>Register - CIRAS</title>
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
<body class="min-h-screen flex items-center justify-center p-4 relative z-10 py-12">
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
                <h1 class="text-3xl font-black text-white mb-2">Create Account</h1>
                <p class="text-gray-400">Register for CIRAS</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-900/30 border border-red-500/50 rounded-xl text-red-300 backdrop-blur-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-900/30 border border-green-500/50 rounded-xl text-green-300 backdrop-blur-sm">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                <div class="mt-2">
                    <a href="login.php" class="text-green-300 underline font-medium hover:text-green-200">Go to Login</a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <!-- Registration Form -->
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-user mr-2 text-blue-400"></i>Full Name *
                    </label>
                    <input type="text" name="full_name" required 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter your full name">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-user-tag mr-2 text-blue-400"></i>Username *
                    </label>
                    <input type="text" name="username" required 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Choose a username">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2 text-blue-400"></i>Email Address *
                    </label>
                    <input type="email" name="email" required 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter your email">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-phone mr-2 text-blue-400"></i>Phone (Optional)
                    </label>
                    <input type="text" name="phone" 
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter your phone number">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-400"></i>Password *
                    </label>
                    <input type="password" name="password" required minlength="6"
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Enter password (min 6 characters)">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-400"></i>Confirm Password *
                    </label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="input-dark w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Confirm your password">
                </div>
                
                <div class="bg-blue-900/20 border border-blue-500/30 rounded-xl p-3 backdrop-blur-sm">
                    <p class="text-xs text-blue-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Note:</strong> Public registration creates a Viewer account with read-only access. 
                        Officers and Administrators are registered by system administrators.
                    </p>
                </div>
                
                <button type="submit" 
                        class="btn-primary w-full text-white py-3.5 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all relative z-10">
                    <span class="relative z-10">
                        <i class="fas fa-user-plus mr-2"></i>Create Account
                    </span>
                </button>
            </form>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-400">
                    Already have an account? 
                    <a href="login.php" class="text-gradient font-semibold hover:underline">Login here</a>
                </p>
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
