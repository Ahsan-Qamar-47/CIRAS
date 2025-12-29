<?php
/**
 * Database Connection File
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ciras_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Show error if error reporting is enabled, otherwise show user-friendly message
    if (ini_get('display_errors')) {
        die("Database connection failed: " . $e->getMessage() . "<br>Please check your database configuration in includes/db.php");
    } else {
        die("Database connection failed. Please contact the administrator or check error logs.");
    }
}

