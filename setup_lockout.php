<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0");
    echo "Added failed_login_attempts column.\n";
} catch (PDOException $e) {
    echo "failed_login_attempts column likely already exists or validation error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN lockout_until DATETIME DEFAULT NULL");
    echo "Added lockout_until column.\n";
} catch (PDOException $e) {
    echo "lockout_until column likely already exists or validation error: " . $e->getMessage() . "\n";
}
?>
