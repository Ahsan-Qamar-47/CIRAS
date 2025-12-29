<?php
/**
 * Logout Page
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    // Log audit
    logAudit($pdo, $_SESSION['user_id'], 'Logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
redirect('index.php', 'You have been logged out successfully', 'success');

