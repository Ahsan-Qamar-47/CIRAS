<?php
/**
 * Helper Functions
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate alert message
 */
function setAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear alert message
 */
function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

/**
 * Generate incident number
 */
function generateIncidentNumber($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM incidents WHERE YEAR(created_at) = ?");
    $stmt->execute([$year]);
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    return "CIR-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
}

/**
 * Auto-create priority levels if they don't exist
 */
function ensurePriorityLevels($pdo) {
    // This function ensures priority enum values exist
    // Priority is stored as enum in database, so this is mainly for validation
    $priorities = ['Low', 'Medium', 'High', 'Critical'];
    // Since priority is an enum, MySQL handles it automatically
    // This function is here for future use if we need to track priorities in a table
    return true;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M d, Y h:i A') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Get status badge color
 */
function getStatusColor($statusName) {
    $colors = [
        'New' => 'blue',
        'Under Investigation' => 'yellow',
        'Pending Review' => 'purple',
        'Resolved' => 'green',
        'Closed' => 'gray',
        'Escalated' => 'red'
    ];
    return $colors[$statusName] ?? 'gray';
}

/**
 * Get priority badge color
 */
function getPriorityColor($priority) {
    $colors = [
        'Low' => 'green',
        'Medium' => 'yellow',
        'High' => 'orange',
        'Critical' => 'red'
    ];
    return $colors[$priority] ?? 'gray';
}

/**
 * Log audit trail
 */
function logAudit($pdo, $userId, $actionType, $actionDescription, $tableName = null, $recordId = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action_type, action_description, table_name, record_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $actionType, $actionDescription, $tableName, $recordId, $ipAddress, $userAgent]);
}

/**
 * Create notification
 */
function createNotification($pdo, $userId, $title, $message, $type = 'info', $incidentId = null) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_incident_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $title, $message, $type, $incidentId]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

/**
 * Calculate file hash (SHA-256)
 */
function calculateFileHash($filePath) {
    if (file_exists($filePath)) {
        return hash_file('sha256', $filePath);
    }
    return null;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Check if user has permission
 */
function hasPermission($pdo, $userId, $permissionName) {
    // Get user role
    $stmt = $pdo->prepare("
        SELECT ur.role_name 
        FROM users u 
        INNER JOIN user_roles ur ON u.role_id = ur.role_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $roleName = $stmt->fetchColumn();

    if (!$roleName) return false;

    // RBAC Matrix
    $matrix = [
        'Admin' => [
            'create_incident', 'edit_any_incident', 'delete_incident',
            'view_all_incidents', 'view_incidents', 
            'upload_evidence', 'assign_cases', 
            'generate_reports', 'manage_users'
        ],
        'Officer' => [
            'create_incident', 'edit_assigned_incident',
            'view_assigned_incidents', 'view_incidents',
            'upload_evidence'
        ],
        'Analyst' => [
            'view_all_incidents', 'view_incidents',
            'generate_reports'
        ],
        'Viewer' => [
            'create_incident', 'view_my_incidents', 'view_incidents'
        ]
    ];

    $permissions = $matrix[$roleName] ?? [];
    return in_array($permissionName, $permissions);
}

/**
 * Check if user can edit a specific incident
 */
function canEditIncident($pdo, $userId, $incidentId) {
    if (hasPermission($pdo, $userId, 'edit_any_incident')) {
        return true;
    }

    if (hasPermission($pdo, $userId, 'edit_assigned_incident')) {
        // Check assignment
        $stmt = $pdo->prepare("SELECT assigned_to FROM incidents WHERE incident_id = ?");
        $stmt->execute([$incidentId]);
        $assignedTo = $stmt->fetchColumn();
        
        return $assignedTo == $userId;
    }

    return false;
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        setAlert($message, $type);
    }
    header("Location: $url");
    exit();
}

