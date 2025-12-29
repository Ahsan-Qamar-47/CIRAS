<?php
/**
 * Authentication Check
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('index.php', 'Please login to continue', 'warning');
}

// Session Timeout Check (20 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1200)) {
    session_unset();
    session_destroy();
    redirect('index.php', 'Session expired due to inactivity', 'warning');
}
$_SESSION['last_activity'] = time();

// Get current user information
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, ur.role_name 
    FROM users u 
    INNER JOIN user_roles ur ON u.role_id = ur.role_id 
    WHERE u.user_id = ? AND u.is_active = 1
");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    session_destroy();
    redirect('index.php', 'Your account has been deactivated', 'error');
}

// Update last login
$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$updateStmt->execute([$userId]);

