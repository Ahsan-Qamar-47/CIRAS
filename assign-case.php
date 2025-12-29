<?php
/**
 * Assign Case to Officer
 * CIRAS - Cybercrime Incident Reporting & Analysis System
 */

$pageTitle = 'Assign Case';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Check permission
if (!hasPermission($pdo, $currentUser['user_id'], 'assign_cases')) {
    redirect('incidents.php', 'You do not have permission to assign cases', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incidentId = intval($_POST['incident_id'] ?? 0);
    $assignedTo = intval($_POST['assigned_to'] ?? 0);
    
    if ($incidentId == 0 || $assignedTo == 0) {
        setAlert('Invalid incident or officer selection', 'error');
        redirect('incidents.php');
    }
    
    try {
        // Get incident details
        $stmt = $pdo->prepare("SELECT incident_number, assigned_to FROM incidents WHERE incident_id = ?");
        $stmt->execute([$incidentId]);
        $incident = $stmt->fetch();
        
        if (!$incident) {
            setAlert('Incident not found', 'error');
            redirect('incidents.php');
        }
        
        // Update assignment
        $stmt = $pdo->prepare("UPDATE incidents SET assigned_to = ? WHERE incident_id = ?");
        $stmt->execute([$assignedTo, $incidentId]);
        
        // Get officer name
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$assignedTo]);
        $officer = $stmt->fetch();
        
        // Add timeline event
        $stmt = $pdo->prepare("
            INSERT INTO case_timeline (incident_id, user_id, event_type, event_description, event_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $incidentId, 
            $currentUser['user_id'], 
            'Case Assigned', 
            "Case assigned to {$officer['full_name']} by {$currentUser['full_name']}"
        ]);
        
        // Create notification
        createNotification($pdo, $assignedTo, 'New Case Assigned', 
            "You have been assigned to case {$incident['incident_number']}", 
            'info', $incidentId);
        
        // Log audit
        logAudit($pdo, $currentUser['user_id'], 'Assign Case', 
            "Assigned case {$incident['incident_number']} to {$officer['full_name']}", 
            'incidents', $incidentId);
        
        redirect('view-incident.php?id=' . $incidentId, 'Case assigned successfully', 'success');
    } catch (Exception $e) {
        setAlert('Error assigning case: ' . $e->getMessage(), 'error');
        redirect('incidents.php');
    }
} else {
    redirect('incidents.php');
}

