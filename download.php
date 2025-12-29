<?php
/**
 * Secure File Download
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

require_once __DIR__ . '/includes/setup.php';

// Require login
requireLogin();

$evidenceId = intval($_GET['id'] ?? 0);

if (!$evidenceId) {
    die('Invalid file ID');
}

// Fetch evidence details
$stmt = $pdo->prepare("
    SELECT e.*, i.incident_id, i.assigned_to 
    FROM incident_evidence e
    JOIN incidents i ON e.incident_id = i.incident_id
    WHERE e.evidence_id = ?
");
$stmt->execute([$evidenceId]);
$evidence = $stmt->fetch();

if (!$evidence) {
    die('File not found');
}

// Check Permissions (FR-24: Only assigned officers or admins)
$canDownload = false;

// Admin and Analyst can download (Analyst needs to view global data)
if ($currentUser['role_name'] === 'Admin' || $currentUser['role_name'] === 'Analyst') {
    $canDownload = true;
}
// Officer can download if assigned
elseif ($currentUser['role_name'] === 'Officer') {
    if ($evidence['assigned_to'] == $currentUser['user_id']) {
        $canDownload = true;
    }
}

if (!$canDownload) {
    http_response_code(403);
    die('Access Denied: You do not have permission to download this evidence.');
}

// File path
$filePath = __DIR__ . '/assets/uploads/' . $evidence['file_path'];

if (!file_exists($filePath)) {
    die('File exists in database but not on server.');
}

// Log the download (FR-25)
logAudit(
    $pdo, 
    $currentUser['user_id'], 
    'Download Evidence', 
    "Downloaded evidence file: {$evidence['file_name']}", 
    'incident_evidence', 
    $evidenceId
);

// Serve the file
$mimeType = $evidence['mime_type'] ?: 'application/octet-stream';
$fileName = $evidence['file_name'];

header("Content-Type: $mimeType");
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"" . addslashes($fileName) . "\""); 
header("Content-Length: " . $evidence['file_size']);
readfile($filePath);
exit;
